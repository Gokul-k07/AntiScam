<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use App\Support\Logger;

/**
 * VirusTotalProvider
 *
 * Retrieves an existing VirusTotal URL report using the v3 REST API.
 *
 * ── What this class does ────────────────────────────────────────────────────
 *   - Calls GET https://www.virustotal.com/api/v3/urls/{id} with the
 *     unpadded URL-safe Base64 identifier of the URL as the path parameter.
 *   - Parses last_analysis_stats: malicious, suspicious, harmless, undetected, timeout.
 *   - Returns a VirusTotalResult value object.
 *   - Caches successful results (found / not_found) to storage/vt_cache/.
 *
 * ── What this class does NOT do ─────────────────────────────────────────────
 *   - Does NOT fetch or download the submitted URL (SSRF Rule §10).
 *   - Does NOT submit URLs for scanning (no POST /urls call).
 *   - Does NOT retry on rate-limit (HTTP 429) — it returns rateLimited() immediately.
 *   - Does NOT log or expose the API key.
 *   - Does NOT throw exceptions — all errors are returned as VirusTotalResult states.
 *
 * ── Configuration ────────────────────────────────────────────────────────────
 *   Required .env key: VIRUSTOTAL_API_KEY=<your key>
 *   Required .env key: THREAT_INTEL_PROVIDER=virustotal
 *   Optional .env key: VIRUSTOTAL_CACHE_TTL_SECONDS=3600
 *
 * ── Public API limits ────────────────────────────────────────────────────────
 *   4 requests/minute, 500 requests/day. This provider makes at most one
 *   request per lookup and caches results to stay well within limits.
 *
 * ── Security notes ───────────────────────────────────────────────────────────
 *   The API key is retrieved from $_ENV only. It is never stored as an
 *   instance property accessible from outside the constructor scope, and
 *   is passed only in the HTTP request header inside the private fetch() method.
 *   Logger::REDACTED_KEYS already covers 'api_key' — we additionally pass no
 *   context containing the key to Logger.
 */
class VirusTotalProvider implements ThreatIntelProviderInterface
{
    private const API_BASE_URL = 'https://www.virustotal.com/api/v3/urls/';
    private const TIMEOUT_SECONDS = 8;

    /** We log at 'info' level — never including the key. */
    private const LOG_PREFIX = 'VirusTotalProvider';

    private ReportCache $cache;

    public function __construct(?ReportCache $cache = null)
    {
        $this->cache = $cache ?? new ReportCache();
    }

    /**
     * Look up an existing VT report for $url.
     *
     * Returns VirusTotalResult::notConfigured() immediately if the provider
     * is not enabled or the API key is missing.
     *
     * @param string $url The URL to look up — must be a valid URL string.
     */
    public function lookup(string $url): VirusTotalResult
    {
        // ── Guard: provider not enabled ─────────────────────────────────
        $provider = strtolower(trim($_ENV['THREAT_INTEL_PROVIDER'] ?? 'none'));
        if ($provider !== 'virustotal') {
            return VirusTotalResult::notConfigured();
        }

        // ── Guard: API key absent ────────────────────────────────────────
        // Read the key only within this method scope.
        $apiKey = trim($_ENV['VIRUSTOTAL_API_KEY'] ?? '');
        if ($apiKey === '' || $apiKey === 'not_configured') {
            return VirusTotalResult::notConfigured();
        }

        // ── Guard: input sanity ──────────────────────────────────────────
        $url = trim($url);
        if ($url === '') {
            return VirusTotalResult::unavailable('empty URL');
        }

        // ── Cache hit ────────────────────────────────────────────────────
        $cached = $this->cache->get($url);
        if ($cached !== null) {
            Logger::info(self::LOG_PREFIX . ': cache hit', ['status' => $cached->status]);
            return $cached;
        }

        // ── Build URL identifier (unpadded URL-safe Base64) ──────────────
        $urlId = $this->buildUrlId($url);

        // ── Make the API request ─────────────────────────────────────────
        $result = $this->fetch($apiKey, $urlId);

        // ── Cache and return ─────────────────────────────────────────────
        $this->cache->put($url, $result);
        return $result;
    }

    // ── Private implementation ─────────────────────────────────────────────

    /**
     * Generate the VirusTotal URL identifier.
     *
     * From VT docs: The URL identifier is the unpadded URL-safe Base64
     * encoding of the URL. Standard Base64 uses +/ which must be replaced
     * with -_ for URL-safety, and the trailing = padding must be removed.
     */
    private function buildUrlId(string $url): string
    {
        return rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }

    /**
     * Perform the HTTPS GET to the VirusTotal API.
     *
     * Uses PHP stream wrappers (file_get_contents with stream_context) to
     * avoid a hard curl dependency, while still using TLS (openssl extension
     * is confirmed present in the project's verified environment).
     *
     * The API key is used only here, passed as an HTTP header, and never stored.
     *
     * @param string $apiKey  The VT API key (used only in HTTP headers, never logged).
     * @param string $urlId   The unpadded URL-safe Base64 URL identifier.
     */
    private function fetch(string $apiKey, string $urlId): VirusTotalResult
    {
        $endpoint = self::API_BASE_URL . rawurlencode($urlId);

        $context = stream_context_create([
            'http' => [
                'method'           => 'GET',
                'header'           => "x-apikey: {$apiKey}\r\nAccept: application/json\r\n",
                'timeout'          => self::TIMEOUT_SECONDS,
                'ignore_errors'    => true,  // lets us read the body on 4xx responses
                'follow_location'  => 0,     // no redirects — VT API does not redirect
            ],
            'ssl'  => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
                'cafile'           => $this->getCaBundle(),
            ],
        ]);

        $body = @file_get_contents($endpoint, false, $context);

        if ($body === false) {
            Logger::warning(self::LOG_PREFIX . ': HTTP request failed (network error or timeout)');
            return VirusTotalResult::unavailable('network error or timeout');
        }

        // Extract HTTP status code from the response headers PHP populates in $http_response_header.
        $httpStatus = $this->extractHttpStatus();

        return match(true) {
            $httpStatus === 404 => VirusTotalResult::notFound(),
            $httpStatus === 429 => VirusTotalResult::rateLimited(),
            $httpStatus === 200 => $this->parseSuccessResponse($body),
            default             => $this->handleUnexpectedStatus($httpStatus, $body),
        };
    }

    /**
     * Parse a successful (HTTP 200) response body.
     */
    private function parseSuccessResponse(string $body): VirusTotalResult
    {
        try {
            $decoded = json_decode($body, true, 10, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
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
            Logger::warning(self::LOG_PREFIX . ': last_analysis_stats missing from response');
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

    /**
     * Handle any HTTP status that is not 200, 404, or 429.
     *
     * Common cases: 401 (bad key), 400 (bad request), 500 (server error).
     * We map all of these to "unavailable" to avoid breaking heuristic results.
     */
    private function handleUnexpectedStatus(int $httpStatus, string $body): VirusTotalResult
    {
        // Attempt to extract VT's own error code for logging (never for display).
        $vtError = '';
        try {
            $decoded = json_decode($body, true, 4, JSON_THROW_ON_ERROR);
            $vtError = (string)($decoded['error']['code'] ?? '');
        } catch (\JsonException) {
            // Body is not JSON — that's fine.
        }

        Logger::warning(self::LOG_PREFIX . ': unexpected HTTP status', [
            'http_status' => $httpStatus,
            'vt_error'    => $vtError,
            // Note: we never include the api_key here.
        ]);

        return VirusTotalResult::unavailable("HTTP {$httpStatus}");
    }

    /**
     * Extract the HTTP response status code from PHP's $http_response_header global.
     *
     * After a file_get_contents() call with a stream context, PHP populates the
     * global $http_response_header with the response headers as an array. The first
     * entry is always the status line (e.g. "HTTP/1.1 200 OK").
     *
     * Returns 0 if the status cannot be determined.
     */
    private function extractHttpStatus(): int
    {
        // $http_response_header is a PHP superglobal populated by file_get_contents.
        // We access it via the global keyword to keep this method testable.
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

    /**
     * Return the path to the CA bundle for TLS verification.
     *
     * Prefers the path configured in php.ini (openssl.cafile / curl.cainfo).
     * Falls back to the XAMPP-bundled cacert.pem which is known to exist on
     * the project's verified Windows/XAMPP environment.
     */
    private function getCaBundle(): string
    {
        // PHP may already be configured with a valid CA bundle.
        $iniPaths = [
            ini_get('openssl.cafile'),
            ini_get('curl.cainfo'),
        ];
        foreach ($iniPaths as $path) {
            if (is_string($path) && $path !== '' && file_exists($path)) {
                return $path;
            }
        }

        // Common XAMPP Windows location.
        $xamppCa = 'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt';
        if (file_exists($xamppCa)) {
            return $xamppCa;
        }

        // Let OpenSSL use its compiled-in default.
        return '';
    }
}
