<?php

declare(strict_types=1);

namespace App\Services\Analysis;

/**
 * ThreatIntelProviderInterface
 *
 * Contract for external threat-intelligence providers.
 *
 * IMPORTANT: Implementations MUST NOT:
 *   - Fetch or download the submitted URL itself (SSRF Rule §10).
 *   - Log, echo, or expose the API key in any output.
 *   - Throw exceptions that bubble up and break local heuristic analysis.
 *   - Retry aggressively on rate-limit or quota errors.
 *   - Send message text, UPI IDs, or evidence file contents to the provider.
 *
 * Implementations MUST:
 *   - Return VirusTotalResult (or a compatible value object) describing the
 *     outcome: found, not_found, rate_limited, unavailable, or not_configured.
 *   - Treat a missing report or zero detections as "no data" — never "safe."
 */
interface ThreatIntelProviderInterface
{
    /**
     * Look up an existing reputation report for a URL.
     *
     * Only the URL string is sent to the provider — in the form of its
     * standardised identifier — not the full request context or any user data.
     *
     * @param  string $url  The URL to look up (must be a URL, not SMS text)
     * @return VirusTotalResult
     */
    public function lookup(string $url): VirusTotalResult;
}
