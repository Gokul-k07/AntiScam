<?php

declare(strict_types=1);

namespace App\Services\Analysis;

/**
 * VirusTotalResult
 *
 * Immutable value object representing the outcome of a VirusTotal URL lookup.
 *
 * Possible statuses:
 *   found          — VT returned a report; stats fields are populated.
 *   not_found      — VT returned 404: no report exists for this URL.
 *   rate_limited   — VT returned 429: daily or per-minute quota exceeded.
 *   unavailable    — Network error, timeout, invalid JSON, or unexpected HTTP status.
 *   not_configured — VIRUSTOTAL_API_KEY is absent or THREAT_INTEL_PROVIDER is not 'virustotal'.
 *
 * SECURITY NOTE:
 *   - This object is safe to pass to views. It contains no API keys or secrets.
 *   - "not_found" and zero detections are NOT equivalent to "safe."
 *     Always display the appropriate caveat when showing this data to users.
 */
final class VirusTotalResult
{
    // ── Status constants ───────────────────────────────────────────────────
    public const STATUS_FOUND          = 'found';
    public const STATUS_NOT_FOUND      = 'not_found';
    public const STATUS_RATE_LIMITED   = 'rate_limited';
    public const STATUS_UNAVAILABLE    = 'unavailable';
    public const STATUS_NOT_CONFIGURED = 'not_configured';

    private function __construct(
        /** One of the STATUS_* constants above. */
        public readonly string $status,

        /**
         * Number of antivirus engines that flagged the URL as malicious.
         * null when status is not 'found'.
         */
        public readonly ?int $malicious,

        /**
         * Number of antivirus engines that flagged the URL as suspicious.
         * null when status is not 'found'.
         */
        public readonly ?int $suspicious,

        /**
         * Number of engines that returned harmless.
         * null when status is not 'found'.
         */
        public readonly ?int $harmless,

        /**
         * Number of engines that returned undetected.
         * null when status is not 'found'.
         */
        public readonly ?int $undetected,

        /**
         * Number of engines that timed out.
         * null when status is not 'found'.
         */
        public readonly ?int $timeout,

        /**
         * Total engines that participated in this scan.
         * Computed as malicious + suspicious + harmless + undetected + timeout.
         * null when status is not 'found'.
         */
        public readonly ?int $totalEngines,

        /**
         * Unix timestamp of the last analysis (last_analysis_date from VT).
         * null when not available.
         */
        public readonly ?int $lastAnalysisDate,

        /**
         * Whether this result was served from local disk cache rather than
         * a fresh API call.
         */
        public readonly bool $fromCache,

        /**
         * Human-readable message describing the outcome or any error.
         * Safe to display in logs (contains no secrets).
         */
        public readonly string $message,
    ) {}

    // ── Named constructors ─────────────────────────────────────────────────

    /**
     * A report was found and parsed successfully.
     *
     * @param array{malicious:int,suspicious:int,harmless:int,undetected:int,timeout:int} $stats
     */
    public static function found(array $stats, ?int $lastAnalysisDate, bool $fromCache = false): self
    {
        $malicious  = (int)($stats['malicious']  ?? 0);
        $suspicious = (int)($stats['suspicious'] ?? 0);
        $harmless   = (int)($stats['harmless']   ?? 0);
        $undetected = (int)($stats['undetected'] ?? 0);
        $timeout    = (int)($stats['timeout']    ?? 0);
        $total      = $malicious + $suspicious + $harmless + $undetected + $timeout;

        return new self(
            status:           self::STATUS_FOUND,
            malicious:        $malicious,
            suspicious:       $suspicious,
            harmless:         $harmless,
            undetected:       $undetected,
            timeout:          $timeout,
            totalEngines:     $total,
            lastAnalysisDate: $lastAnalysisDate,
            fromCache:        $fromCache,
            message:          "Report found. {$malicious} malicious, {$suspicious} suspicious of {$total} engines.",
        );
    }

    /** VT returned HTTP 404 — no report exists for this URL. */
    public static function notFound(): self
    {
        return new self(
            status:           self::STATUS_NOT_FOUND,
            malicious:        null,
            suspicious:       null,
            harmless:         null,
            undetected:       null,
            timeout:          null,
            totalEngines:     null,
            lastAnalysisDate: null,
            fromCache:        false,
            message:          'No VirusTotal report exists for this URL. Absence of a report is not evidence of safety.',
        );
    }

    /** VT returned HTTP 429 — quota or rate limit exceeded. */
    public static function rateLimited(): self
    {
        return new self(
            status:           self::STATUS_RATE_LIMITED,
            malicious:        null,
            suspicious:       null,
            harmless:         null,
            undetected:       null,
            timeout:          null,
            totalEngines:     null,
            lastAnalysisDate: null,
            fromCache:        false,
            message:          'VirusTotal API quota or rate limit reached. External reputation check skipped.',
        );
    }

    /**
     * Network error, timeout, unexpected HTTP status, or JSON decode failure.
     *
     * @param string $reason Short safe description (no secrets).
     */
    public static function unavailable(string $reason = ''): self
    {
        return new self(
            status:           self::STATUS_UNAVAILABLE,
            malicious:        null,
            suspicious:       null,
            harmless:         null,
            undetected:       null,
            timeout:          null,
            totalEngines:     null,
            lastAnalysisDate: null,
            fromCache:        false,
            message:          'VirusTotal check unavailable' . ($reason !== '' ? ": {$reason}" : '.'),
        );
    }

    /** API key is missing or provider is not enabled in configuration. */
    public static function notConfigured(): self
    {
        return new self(
            status:           self::STATUS_NOT_CONFIGURED,
            malicious:        null,
            suspicious:       null,
            harmless:         null,
            undetected:       null,
            timeout:          null,
            totalEngines:     null,
            lastAnalysisDate: null,
            fromCache:        false,
            message:          'External reputation check is not configured.',
        );
    }

    // ── Convenience helpers ────────────────────────────────────────────────

    /** Returns true only when a report was successfully retrieved and parsed. */
    public function isFound(): bool
    {
        return $this->status === self::STATUS_FOUND;
    }

    /** Returns true when external reputation data is absent for any reason. */
    public function isUnavailable(): bool
    {
        return $this->status !== self::STATUS_FOUND;
    }

    /**
     * Maps this result to the analyses.threat_status ENUM value.
     *
     * Schema ENUM: 'not_checked' | 'no_match' | 'match' | 'unavailable'
     */
    public function toThreatStatus(): string
    {
        return match($this->status) {
            self::STATUS_FOUND => ($this->malicious > 0 || $this->suspicious > 0)
                ? 'match'
                : 'no_match',
            self::STATUS_NOT_CONFIGURED => 'not_checked',
            default                     => 'unavailable',
        };
    }
}
