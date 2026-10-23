<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use App\Support\Logger;

/**
 * VirusTotalProvider — InfinityFree deployment version.
 *
 * IDENTICAL to local VirusTotalProvider.php except getCaBundle():
 *   Local version falls back to C:\xampp\apache\bin\curl-ca-bundle.crt which
 *   does not exist on Linux shared hosting. That XAMPP-specific fallback is
 *   removed here. InfinityFree runs PHP on Linux with OpenSSL compiled against
 *   a system CA bundle, so passing no cafile lets OpenSSL use its default (correct).
 *
 * Outbound HTTPS confirmed allowed on InfinityFree (not blocked by open_basedir;
 * network calls are exempt from open_basedir restrictions).
 *
 * Security rules unchanged:
 *  - Does NOT fetch the submitted URL (SSRF Rule §10).
 *  - Does NOT submit URLs for scanning (no POST /urls).
 *  - Does NOT log or expose the API key.
 *  - Returns VirusTotalResult value objects for all outcomes.
 *  - Caches found/not_found results in storage/vt_cache/.
 */
class VirusTotalProvider implements ThreatIntelProviderInterface
{
    private const API_BASE_URL    = 'https://www.virustotal.com/api/v3/urls/';
    private const TIMEOUT_SECONDS = 8;
    private const LOG_PREFIX      = 'VirusTotalProvider';

    private ReportCache $cache;

    public function __construct(?ReportCache $cache = null)
    {
        $this->cache = $cache ?? new ReportCache();
    }

    public function lookup(string $url): VirusTotalResult
    {
        // Guard: provider not enabled
        $provider = strtolower(trim($_ENV['THREAT_INTEL_PROVIDER'] ?? 'none'));
        if ($provider !== 'virustotal') {
            return VirusTotalResult::notConfigured();
        }

        // Guard: API key absent
        $apiKey = trim($_ENV['VIRUSTOTAL_API_KEY'] ?? '');
        if ($apiKey === '' || $apiKey === 'not_configured') {
            return VirusTotalResult::notConfigured();
        }

        // Guard: empty input
        $url = trim($url);
        if ($url === '') {
            return VirusTotalResult::unavailable('empty URL');
        }

        // Cache hit
        $cached = $this->cache->get($url);
        if ($cached !== null) {
            Logger::info(self::LOG_PREFIX . ': cache hit', ['status' => $cached->status]);
            return $cached;
        }

        $urlId  = $this->buildUrlId($url);
        $result = $this->fetch($apiKey, $urlId);
        $this->cache->put($url, $result);
        return $result;
    }

    private function buildUrlId(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    private function fetch(string $apiKey, string $urlId): VirusTotalResult
    {
        $endpoint = self::API_BASE_URL . rawurlencode($urlId);

        $context = stream_context_create([
            'http' => [
                'method'          => 'GET',
                'header'          => "x-apikey: {$apiKey}\r\nAccept: application/json\r\n",
                'timeout'         => self::TIMEOUT_SECONDS,
                'ignore_errors'   => true,
                'follow_location' => 0,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
                // No cafile override: InfinityFree Linux has a system CA bundle;
                // let OpenSSL use its compiled-in default. The XAMPP-specific
                // C:\xampp\apache\bin\curl-ca-bundle.crt fallback is intentionally
                // omitted — it does not exist on shared hosting.
            ],
        ]);

        $body = @file_get_contents($endpoint, false, $context);

        if ($body === false) {
            Logger::warning(self::LOG_PREFIX . ': HTTP request failed (network error or timeout)');
            return VirusTotalResult::unavailable('network error or timeout');
        }

        $httpStatus = $this->extractHttpStatus();

        return match(true) {
            $httpStatus === 404 => VirusTotalResult::notFound(),
            $httpStatus === 429 => VirusTotalResult::rateLimited(),
            $httpStatus === 200 => $this->parseSuccessResponse($body),
            default             => $this->handleUnexpectedStatus($httpStatus, $body),
        };
    }

    private function parseSuccessResponse(string $body): VirusTotalResult
    {
        try {
            $decoded = json_decode($body, true, 10, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            Logger::warning(self::LOG_PREFIX . ': invalid JSON in response');
            return VirusTotalResult::unavailable('invalid JSON response');
        }

        $attrs = $decoded['data']['attributes'] ?? null;
        if (!is_array($attrs)) {
            Logger::warning(self::LOG_PREFIX . ': unexpected response structure');
            return VirusTotalResult::unavailable('unexpected response structure');
        }

        $stats = $attrs['last_analysis_stats'] ?? null;
        if (!is_array($stats)) {
            Logger::warning(self::LOG_PREFIX . ': last_analysis_stats missing');
            return VirusTotalResult::unavailable('last_analysis_stats missing');
        }

        $lastAnalysisDate = isset($attrs['last_analysis_date'])
            ? (int)$attrs['last_analysis_date']
            : null;

        Logger::info(self::LOG_PREFIX . ': report found', [
            'malicious'  => (int)($stats['malicious']  ?? 0),
            'suspicious' => (int)($stats['suspicious'] ?? 0),
            'total'      => array_sum(array_values($stats)),
        ]);

        return VirusTotalResult::found(
            stats: [
                'malicious'  => (int)($stats['malicious']  ?? 0),
                'suspicious' => (int)($stats['suspicious'] ?? 0),
                'harmless'   => (int)($stats['harmless']   ?? 0),
                'undetected' => (int)($stats['undetected'] ?? 0),
                'timeout'    => (int)($stats['timeout']    ?? 0),
            ],
            lastAnalysisDate: $lastAnalysisDate,
        );
    }

    private function handleUnexpectedStatus(int $httpStatus, string $body): VirusTotalResult
    {
        $vtError = '';
        try {
            $decoded = json_decode($body, true, 4, JSON_THROW_ON_ERROR);
            $vtError = (string)($decoded['error']['code'] ?? '');
        } catch (\JsonException) {
        }

        Logger::warning(self::LOG_PREFIX . ': unexpected HTTP status', [
            'http_status' => $httpStatus,
            'vt_error'    => $vtError,
        ]);

        return VirusTotalResult::unavailable("HTTP {$httpStatus}");
    }

    private function extractHttpStatus(): int
    {
        global $http_response_header;

        if (!isset($http_response_header) || !is_array($http_response_header)) {
            return 0;
        }

        foreach ($http_response_header as $header) {
            if (preg_match('#^HTTP/\d+(?:\.\d+)?\s+(\d{3})#', $header, $m)) {
                return (int)$m[1];
            }
        }

        return 0;
    }
}
