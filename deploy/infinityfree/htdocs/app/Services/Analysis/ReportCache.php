<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use App\Support\Logger;

/**
 * ReportCache
 *
 * Simple flat-file cache for VirusTotal URL reports.
 *
 * - Cache files are stored in {storage_root}/vt_cache/.
 * - Each cache entry is a JSON file keyed by SHA-256 of the URL.
 * - Default TTL: 3600 seconds (1 hour), configurable via VIRUSTOTAL_CACHE_TTL_SECONDS.
 * - The URL itself is never stored in the filename or path.
 * - Stale or corrupt cache entries are silently discarded.
 *
 * SECURITY:
 *   - The cache directory is intentionally inside storage/ (not web-accessible).
 *   - Cache keys are SHA-256 hashes — the original URL is not recoverable from them.
 *   - No API keys or secrets are stored in cache entries.
 */
class ReportCache
{
    private string $cacheDir;
    private int    $ttl;

    public function __construct()
    {
        // Resolve the storage root directory.
        // If STORAGE_EVIDENCE_PATH is configured and not a placeholder, derive the
        // storage root as its parent directory.  Otherwise fall back to the project's
        // storage/ directory relative to this file's location.
        $evidencePath = $_ENV['STORAGE_EVIDENCE_PATH'] ?? '';
        if (
            $evidencePath !== ''
            && strpos($evidencePath, 'changeme') === false
            && strpos($evidencePath, 'not_configured') === false
        ) {
            $storageRoot = rtrim(dirname($evidencePath), '/\\');
        } else {
            // Fallback: three levels up from app/Services/Analysis/ → project root,
            // then into storage/.
            $storageRoot = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage';
        }

        $this->cacheDir = $storageRoot . DIRECTORY_SEPARATOR . 'vt_cache';
        $this->ttl      = (int)($_ENV['VIRUSTOTAL_CACHE_TTL_SECONDS'] ?? 3600);
        if ($this->ttl < 0) {
            $this->ttl = 3600;
        }
    }

    /**
     * Return a cached VirusTotalResult for $url, or null if absent / expired.
     */
    public function get(string $url): ?VirusTotalResult
    {
        $path = $this->pathFor($url);

        if (!file_exists($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        try {
            $data = json_decode($raw, true, 4, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            @unlink($path); // discard corrupt entry
            return null;
        }

        if (!isset($data['cached_at']) || (time() - (int)$data['cached_at']) > $this->ttl) {
            @unlink($path);
            return null;
        }

        return $this->hydrate($data);
    }

    /**
     * Store a VirusTotalResult in the cache.
     * Only caches STATUS_FOUND and STATUS_NOT_FOUND results.
     * Rate-limit and unavailable states are not cached so the next request retries.
     */
    public function put(string $url, VirusTotalResult $result): void
    {
        if (!in_array($result->status, [
            VirusTotalResult::STATUS_FOUND,
            VirusTotalResult::STATUS_NOT_FOUND,
        ], true)) {
            return; // Don't cache transient errors.
        }

        if (!$this->ensureDirectory()) {
            return;
        }

        $data = [
            'cached_at'        => time(),
            'status'           => $result->status,
            'malicious'        => $result->malicious,
            'suspicious'       => $result->suspicious,
            'harmless'         => $result->harmless,
            'undetected'       => $result->undetected,
            'timeout'          => $result->timeout,
            'total_engines'    => $result->totalEngines,
            'last_analysis_date' => $result->lastAnalysisDate,
        ];

        $path = $this->pathFor($url);

        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        @file_put_contents($path, $json, LOCK_EX);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Return the cache file path for a URL.
     * The filename is a SHA-256 hash — the original URL is not recoverable.
     */
    private function pathFor(string $url): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . hash('sha256', $url) . '.json';
    }

    /** Create the cache directory if it does not exist. Returns false on failure. */
    private function ensureDirectory(): bool
    {
        if (is_dir($this->cacheDir)) {
            return true;
        }

        $created = @mkdir($this->cacheDir, 0700, true);
        if (!$created) {
            Logger::warning('ReportCache: could not create cache directory');
        }
        return $created;
    }

    /** Re-hydrate a VirusTotalResult from a stored array. */
    private function hydrate(array $data): VirusTotalResult
    {
        if ($data['status'] === VirusTotalResult::STATUS_NOT_FOUND) {
            return VirusTotalResult::notFound();
        }

        if ($data['status'] === VirusTotalResult::STATUS_FOUND) {
            return VirusTotalResult::found(
                stats: [
                    'malicious'  => (int)($data['malicious']  ?? 0),
                    'suspicious' => (int)($data['suspicious'] ?? 0),
                    'harmless'   => (int)($data['harmless']   ?? 0),
                    'undetected' => (int)($data['undetected'] ?? 0),
                    'timeout'    => (int)($data['timeout']    ?? 0),
                ],
                lastAnalysisDate: isset($data['last_analysis_date']) ? (int)$data['last_analysis_date'] : null,
                fromCache: true,
            );
        }

        return VirusTotalResult::unavailable('unrecognised cached status');
    }
}
