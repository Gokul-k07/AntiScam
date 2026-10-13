<?php

declare(strict_types=1);

namespace App\Services\Analysis;

/**
 * UrlAnalysisService
 *
 * Deterministic, airgapped heuristic analyzer for suspicious URLs and messages.
 *
 * SECURITY RULES FOLLOWED:
 *  - Rule 10: Does NOT fetch the submitted URL or any external server.
 *  - Rule 16: "No threat-feed match" means only no match was found — never "safe."
 *  - Input is validated and sanitized before processing; it is NEVER passed to SQL
 *    or a shell command in this service.
 *
 * OUTPUT: An array with the following shape:
 *   [
 *     'input'          => string,        // original (truncated for safety)
 *     'input_type'     => string,        // 'url' | 'sms' | 'upi'
 *     'risk_level'     => string,        // 'critical' | 'high' | 'moderate' | 'low'
 *     'score'          => int,           // 0–100 cumulative weight
 *     'verdict'        => string,        // short human label
 *     'summary'        => string,        // plain-language explanation
 *     'findings'       => array,         // see Finding shape below
 *     'url_tokens'     => array|null,    // parsed URL anatomy (null for SMS)
 *     'audit_log'      => array,         // raw rule triggers and weights
 *   ]
 *
 * Finding shape:
 *   [
 *     'number'    => int,
 *     'trigger'   => string,    // e.g. 'RULE_NO_TLS_V1'
 *     'tag'       => string,    // e.g. '[TRIGGER: CLEARTEXT_TRANSPORT]'
 *     'title'     => string,
 *     'body'      => string,
 *     'weight'    => int,
 *     'severity'  => string,    // 'high' | 'medium' | 'low'
 *     'ref'       => string,    // reference (regulation / heuristic rule)
 *   ]
 *
 * Token shape (url_tokens):
 *   ['value' => string, 'label' => string, 'risk' => string] // risk: safe|warning|danger|neutral
 */
class UrlAnalysisService
{
    /** Maximum input length the service will process (characters). */
    private const MAX_INPUT_LENGTH = 4000;

    /** Score thresholds for risk levels. */
    private const THRESHOLD_CRITICAL  = 85;
    private const THRESHOLD_HIGH      = 55;
    private const THRESHOLD_MODERATE  = 25;

    /**
     * Indian Public Sector Bank brand keywords used in typosquatting.
     * Only used for LOCAL pattern matching — no external lookup.
     */
    private const PSB_KEYWORDS = [
        'sbi', 'sbiyono', 'sbionline', 'hdfc', 'icici', 'axis', 'kotak',
        'pnb', 'bob', 'canara', 'union', 'ubi', 'uco', 'iob', 'allahabad',
        'boi', 'bankofbaroda', 'syndicate', 'vijaya', 'dena',
    ];

    /** Known utility / govt brand keywords phishers impersonate. */
    private const UTILITY_KEYWORDS = [
        'discom', 'bijli', 'electricity', 'bses', 'tata-power', 'msedcl',
        'uppcl', 'tangedco', 'tneb', 'bescom', 'wbsedcl', 'kseb',
        'npci', 'upi', 'bhim', 'paytm', 'phonepe', 'googlepay', 'gpay',
        'fastag', 'income-tax', 'incometax', 'itr', 'gst', 'epfo',
        'aadhaar', 'uid', 'pan', 'kyc',
    ];

    /** Coercive urgency phrases common in Indian scams. */
    private const URGENCY_PHRASES = [
        'frozen within', 'account freeze', 'account block', 'account suspend',
        'immediate', 'immediately', 'urgent', 'urgently', 'disconnect tonight',
        'disconnected tonight', 'legal action', 'arrest', 'warrant',
        'do not ignore', 'last chance', 'final notice', '2 hours', '24 hours',
        '48 hours', 'today only',
    ];

    /** Credential harvest keywords. */
    private const CREDENTIAL_PHRASES = [
        'update kyc', 'update your kyc', 'kyc update', 'verify kyc',
        'pan verification', 'pan update', 'aadhaar verification',
        'update aadhaar', 'netbanking', 'net banking', 'login password',
        'otp', 'pin', 'upi pin',
    ];

    /** Illegal-job / advance-fee scam phrases. */
    private const JOB_SCAM_PHRASES = [
        'earn per day', 'earn per hour', 'work from home',
        'part time job', 'hotel review', 'youtube review',
        'telegram group', 'whatsapp group', 'like and earn',
        'prepaid task', 'vip task', 'rate and earn',
    ];

    /** Fake courier / digital-arrest phrases. */
    private const COURIER_SCAM_PHRASES = [
        'fedex', 'dhl', 'customs seized', 'narcotics', 'drugs found',
        'digital arrest', 'skype call', 'join skype', 'cbcid', 'cbi',
        'cyber cell', 'police officer', 'video call compulsory',
    ];

    /** UPI-specific scam phrases. */
    private const UPI_SCAM_PHRASES = [
        'enter upi pin to receive', 'scan to receive', 'pin to accept',
        'cashback qr', 'refund qr', 'enter pin to claim', 'approved refund',
        'army official verification', 'pin to deposit',
    ];

    /**
     * Analyse a payload string (URL or free-text message).
     *
     * @param string $payload   Raw user input
     * @param string $inputType 'url' | 'sms' | 'upi'
     * @return array            Analysis result (see class docblock)
     */
    public function analyze(string $payload, string $inputType = 'url'): array
    {
        // ── Input sanitization ──────────────────────────────────────────────
        $inputType = $this->normalizeType($inputType);
        $payload   = $this->sanitize($payload);

        if ($payload === '') {
            return $this->emptyResult($payload, $inputType);
        }

        // ── Route to the right analysis path ───────────────────────────────
        if ($inputType === 'url') {
            return $this->analyzeUrl($payload);
        }

        if ($inputType === 'upi') {
            return $this->analyzeUpi($payload);
        }

        // Default: SMS / message text
        return $this->analyzeSms($payload);
    }

    /* ======================================================================
       URL ANALYSIS
       ====================================================================== */

    private function analyzeUrl(string $payload): array
    {
        $findings = [];
        $score    = 0;
        $tokens   = [];
        $auditLog = [];

        // Extract URL from payload (in case it's embedded in a message)
        $url = $this->extractUrl($payload) ?? $payload;

        // Parse with PHP's built-in (no external fetch)
        $parsed = parse_url($url);
        $scheme = strtolower((string)($parsed['scheme'] ?? ''));
        $host   = strtolower((string)($parsed['host']   ?? ''));
        $path   = (string)($parsed['path']  ?? '');
        $query  = (string)($parsed['query'] ?? '');

        // ── Build URL token anatomy ─────────────────────────────────────
        $tokens = $this->buildTokens($scheme, $host, $path, $query);

        // ── Rule 1: Unencrypted HTTP ────────────────────────────────────
        if ($scheme === 'http') {
            $w = 15;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_NO_TLS_V1',
                'tag'      => '[TRIGGER: CLEARTEXT_TRANSPORT]',
                'title'    => 'Cleartext transport protocol (http://)',
                'body'     => 'Any data entered into this page is transmitted without encryption and can be intercepted. Scheduled commercial banks in India are mandated by RBI cyber security framework to enforce TLS 1.3 encryption across all customer-facing endpoints.',
                'weight'   => $w,
                'severity' => 'medium',
                'ref'      => 'RBI Master Direction on Digital Payment Security Controls, Section 4.2',
            ];
            $auditLog[] = ['id' => 'RULE_NO_TLS_V1', 'label' => 'TRANSPORT_CLEAR_TEXT_PENALTY', 'weight' => $w];
        }

        // ── Rule 2: Raw IP address as host ──────────────────────────────
        if ($host !== '' && $this->isRawIp($host)) {
            $w = 35;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_RAW_IP_TARGET',
                'tag'      => '[TRIGGER: RAW_IP_HOST]',
                'title'    => 'Host is a bare IP address, not a registered domain name',
                'body'     => 'Legitimate institutions operate on registered, verified domain names (e.g., sbi.co.in). Attackers use raw IP addresses to bypass automated domain reputation lists and DNS takedown procedures.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'Host Type: IPv4/IPv6 Raw Target (Non-Canonical Resolution)',
            ];
            $auditLog[] = ['id' => 'RULE_RAW_IP_TARGET', 'label' => 'CANONICAL_DOMAIN_BYPASS_ATTEMPT', 'weight' => $w];
        }

        // ── Rule 3: PSB keyword in host or path ─────────────────────────
        $fullUrl       = strtolower($url);
        $matchedBrand  = $this->matchKeywords($fullUrl, self::PSB_KEYWORDS);
        if ($matchedBrand !== null && $host !== '' && !$this->isOfficialDomain($host)) {
            $w = 20;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_PSB_KEYWORD_SLUG',
                'tag'      => '[TRIGGER: BRAND_IMPERSONATION]',
                'title'    => 'Brand name impersonation in path slug or hostname',
                'body'     => 'The string \'' . htmlspecialchars($matchedBrand, ENT_QUOTES, 'UTF-8') . '\' is associated with a known Indian financial institution. Phishers concatenate protected brand keywords into the URL path or subdomain to deceive mobile users whose browsers truncate long URLs.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'Keyword Match: Protected Financial Trademark [' . strtoupper((string)$matchedBrand) . ']',
            ];
            $auditLog[] = ['id' => 'RULE_PSB_KEYWORD_SLUG', 'label' => 'IMPERSONATION_TARGET_' . strtoupper((string)$matchedBrand), 'weight' => $w];
        }

        // ── Rule 4: Utility/govt keyword outside official domain ─────────
        $matchedUtil = $this->matchKeywords($fullUrl, self::UTILITY_KEYWORDS);
        if ($matchedUtil !== null && $host !== '' && !$this->isOfficialDomain($host) && $matchedBrand === null) {
            $w = 12;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_UTIL_KEYWORD',
                'tag'      => '[TRIGGER: UTILITY_BRAND_IMPERSONATION]',
                'title'    => 'Government or utility brand keyword on unofficial domain',
                'body'     => 'The URL contains a keyword (\'' . htmlspecialchars($matchedUtil, ENT_QUOTES, 'UTF-8') . '\') associated with an official government service or utility, but is not served from a known official domain. This is a common phishing pattern for DISCOM, NPCI, and tax authority impersonation.',
                'weight'   => $w,
                'severity' => 'medium',
                'ref'      => 'Pattern: GOVT/UTILITY_BRAND_IN_NON_OFFICIAL_HOST',
            ];
            $auditLog[] = ['id' => 'RULE_UTIL_KEYWORD', 'label' => 'UTILITY_BRAND_HOSTNAME_MISMATCH', 'weight' => $w];
        }

        // ── Rule 5: Suspicious TLD ───────────────────────────────────────
        if ($host !== '' && $this->hasSuspiciousTld($host)) {
            $w = 8;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_SUSPICIOUS_TLD',
                'tag'      => '[TRIGGER: HIGH_RISK_TLD]',
                'title'    => 'Top-level domain associated with high phishing rates',
                'body'     => 'This URL uses a top-level domain that is statistically over-represented in phishing campaigns targeting Indian users. Official Indian banking and government portals use .gov.in, .nic.in, .co.in, .org.in, .sbi, or similar official registrations.',
                'weight'   => $w,
                'severity' => 'low',
                'ref'      => 'Pattern: HIGH_ABUSE_TLD_REGISTRY',
            ];
            $auditLog[] = ['id' => 'RULE_SUSPICIOUS_TLD', 'label' => 'HIGH_ABUSE_TLD', 'weight' => $w];
        }

        // ── Rule 6: Tokenized harvest query parameter ────────────────────
        if ($query !== '' && preg_match('/(?:auth|token|session|key|pass|verify|otp|id)=/i', $query)) {
            $w = 8;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_HARVEST_QUERY',
                'tag'      => '[TRIGGER: SENSITIVE_QUERY_PARAMETER]',
                'title'    => 'Tokenized session or credential query parameter',
                'body'     => 'Dynamic session query strings pre-seeded with sensitive parameter names (token, auth, otp, session) are commonly used by phishing syndicates in bulk SMS campaigns to correlate which recipient clicked the bait.',
                'weight'   => $w,
                'severity' => 'low',
                'ref'      => 'Correlation Entropy: High (Pre-allocated Campaign Parameter)',
            ];
            $auditLog[] = ['id' => 'RULE_HARVEST_QUERY', 'label' => 'RECIPIENT_TRACKING_CORRELATION', 'weight' => $w];
        }

        // ── Rule 7: .apk in path ─────────────────────────────────────────
        if (stripos($path . $query, '.apk') !== false) {
            $w = 30;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_APK_DELIVERY',
                'tag'      => '[TRIGGER: MALICIOUS_APP_DELIVERY]',
                'title'    => 'Android APK file served from non-official domain',
                'body'     => 'This URL points to an Android APK file. Legitimate applications are distributed exclusively through the official Google Play Store or trusted enterprise MDM systems. APKs shared via SMS links or WhatsApp are a primary remote access trojan (RAT) delivery mechanism in India.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'Pattern: UNSOLICITED_APK_DELIVERY — MHA Advisory 2023',
            ];
            $auditLog[] = ['id' => 'RULE_APK_DELIVERY', 'label' => 'ANDROID_RAT_DELIVERY_VECTOR', 'weight' => $w];
        }

        // ── Rule 8: Excessive hyphens in domain (brand stacking) ─────────
        if ($host !== '' && substr_count($host, '-') >= 3) {
            $w = 10;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_HYPHEN_STACKING',
                'tag'      => '[TRIGGER: BRAND_KEYWORD_STACKING]',
                'title'    => 'Excessive hyphen-delimited brand keyword stacking in domain',
                'body'     => 'Phishing domains commonly concatenate 3 or more brand keywords separated by hyphens (e.g., sbi-kyc-update-portal.in) to simulate legitimacy while evading automated URL block lists.',
                'weight'   => $w,
                'severity' => 'medium',
                'ref'      => 'Pattern: HYPHEN_BRAND_STACK_OBFUSCATION',
            ];
            $auditLog[] = ['id' => 'RULE_HYPHEN_STACKING', 'label' => 'DOMAIN_BRAND_STACK', 'weight' => $w];
        }

        $score = min(100, $score);

        return [
            'input'      => $payload,
            'input_type' => 'url',
            'risk_level' => $this->scoreToLevel($score),
            'score'      => $score,
            'verdict'    => $this->scoreToVerdict($score),
            'summary'    => $this->buildUrlSummary($findings, $host, $scheme),
            'findings'   => $findings,
            'url_tokens' => $tokens,
            'audit_log'  => $auditLog,
        ];
    }

    /* ======================================================================
       SMS / FREE-TEXT ANALYSIS
       ====================================================================== */

    private function analyzeSms(string $payload): array
    {
        $findings = [];
        $score    = 0;
        $auditLog = [];
        $lower    = strtolower($payload);

        // ── Rule: Coercive urgency window ───────────────────────────────
        $matchedUrgency = $this->matchKeywords($lower, self::URGENCY_PHRASES);
        if ($matchedUrgency !== null) {
            $w = 20;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_COERCIVE_URGENCY',
                'tag'      => '[TRIGGER: COERCIVE_URGENCY]',
                'title'    => 'Urgency coercion window detected',
                'body'     => 'Text contains a coercive urgency phrase (\'' . htmlspecialchars($matchedUrgency, ENT_QUOTES, 'UTF-8') . '\'). RBI Master Direction explicitly mandates prior written notice via registered email and post before bank account suspension. Banks never enforce instantaneous digital freezes.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'RBI Master Direction, Section on Account Management — Customer Notice Period',
            ];
            $auditLog[] = ['id' => 'RULE_COERCIVE_URGENCY', 'label' => 'COERCIVE_URGENCY_WINDOW', 'weight' => $w];
        }

        // ── Rule: PSB impersonation in text ─────────────────────────────
        $matchedBrand = $this->matchKeywords($lower, self::PSB_KEYWORDS);
        if ($matchedBrand !== null) {
            $w = 20;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_PSB_IMPERSONATION',
                'tag'      => '[TRIGGER: PSB_IMPERSONATION]',
                'title'    => 'Public sector bank brand spoofing',
                'body'     => 'Text uses brand identity associated with a scheduled Indian commercial bank (\'' . htmlspecialchars($matchedBrand, ENT_QUOTES, 'UTF-8') . '\'). Authentic transactional SMS from banks must carry a registered 6-character alphanumeric TRAI DLT header (e.g., VK-SBIINB). Mobile number senders are never legitimate bank origination points.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'TRAI Telecom Commercial Communications Customer Preference Regulations (TCCCPR) 2018',
            ];
            $auditLog[] = ['id' => 'RULE_PSB_IMPERSONATION', 'label' => 'PUBLIC_SECTOR_BANK_SPOOF', 'weight' => $w];
        }

        // ── Rule: KYC/credential harvest keyword ─────────────────────────
        $matchedCred = $this->matchKeywords($lower, self::CREDENTIAL_PHRASES);
        if ($matchedCred !== null) {
            $w = 20;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_KYC_HARVEST',
                'tag'      => '[TRIGGER: KYC_CREDENTIAL_HARVEST]',
                'title'    => 'KYC or credential siphon demand detected',
                'body'     => 'The payload demands immediate KYC or credential verification (\'' . htmlspecialchars($matchedCred, ENT_QUOTES, 'UTF-8') . '\'). Periodic re-KYC for scheduled banks is carried out strictly inside authorized mobile applications or through RBI Video-KYC (V-CIP) portals — never via third-party redirect URLs.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'RBI Master Direction on KYC, 2016 (updated 2022) — Section 38',
            ];
            $auditLog[] = ['id' => 'RULE_KYC_HARVEST', 'label' => 'CREDENTIAL_SIPHON_DEMAND', 'weight' => $w];
        }

        // ── Rule: Contains embedded URL ──────────────────────────────────
        $embeddedUrl = $this->extractUrl($payload);
        if ($embeddedUrl !== null) {
            $urlResult = $this->analyzeUrl($embeddedUrl);
            if ($urlResult['score'] > 0) {
                $bonusScore = (int)round($urlResult['score'] * 0.5);
                $score += $bonusScore;
                // Merge URL findings (renumbered)
                foreach ($urlResult['findings'] as $f) {
                    $f['number'] = count($findings) + 1;
                    $f['tag']    = '[URL] ' . $f['tag'];
                    $findings[]  = $f;
                }
                foreach ($urlResult['audit_log'] as $a) {
                    $auditLog[] = $a;
                }
            }
        }

        // ── Rule: DLT header non-compliance (10-digit mobile sender) ─────
        if (preg_match('/(?:^|\s)(\+91[\s-]?\d{10}|\+91\d{10}|91\d{10}|[789]\d{9})/', $payload)) {
            $w = 15;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_DLT_NONCOMPLIANCE',
                'tag'      => '[TRIGGER: DLT_HEADER_ABSENT]',
                'title'    => 'Sender originates from a private mobile number (DLT non-compliant)',
                'body'     => 'TRAI mandates all commercial transactional SMS to carry a 6-character registered alphanumeric sender header. A private 10-digit mobile number is never a legitimate bank, government, or utility SMS origination point.',
                'weight'   => $w,
                'severity' => 'medium',
                'ref'      => 'TRAI TCCCPR 2018 — DLT Principal Entity Registration',
            ];
            $auditLog[] = ['id' => 'RULE_DLT_NONCOMPLIANCE', 'label' => 'PRIVATE_MOBILE_SENDER_VIOLATION', 'weight' => $w];
        }

        // ── Rule: Courier / digital arrest ───────────────────────────────
        $matchedCourier = $this->matchKeywords($lower, self::COURIER_SCAM_PHRASES);
        if ($matchedCourier !== null) {
            $w = 25;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_DIGITAL_ARREST',
                'tag'      => '[TRIGGER: DIGITAL_ARREST_PATTERN]',
                'title'    => 'Fake customs / digital arrest scam pattern',
                'body'     => 'Payload matches known courier parcel scam or digital arrest pattern (\'' . htmlspecialchars($matchedCourier, ENT_QUOTES, 'UTF-8') . '\'). Indian Police, Customs, or the CBI never conduct interrogations or issue arrest notices over WhatsApp or Skype video calls.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'MHA Advisory on Digital Arrest Scams — I4C Cyber Dost 2024',
            ];
            $auditLog[] = ['id' => 'RULE_DIGITAL_ARREST', 'label' => 'FAKE_CUSTODY_EXTORTION', 'weight' => $w];
        }

        // ── Rule: Job scam ───────────────────────────────────────────────
        $matchedJob = $this->matchKeywords($lower, self::JOB_SCAM_PHRASES);
        if ($matchedJob !== null) {
            $w = 18;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_JOB_SCAM',
                'tag'      => '[TRIGGER: ADVANCE_FEE_JOB_SCAM]',
                'title'    => 'Part-time job / advance fee scam pattern',
                'body'     => 'Payload matches a task-based advance fee scam (\'' . htmlspecialchars($matchedJob, ENT_QUOTES, 'UTF-8') . '\'). These operations send small initial payouts (₹150–₹300) to build trust, then demand large prepaid deposits for &ldquo;VIP tasks&rdquo; — money that is never recovered.',
                'weight'   => $w,
                'severity' => 'medium',
                'ref'      => 'CERT-In Advisory CI-2023-OT-071 — Work-From-Home Advance Fee Fraud',
            ];
            $auditLog[] = ['id' => 'RULE_JOB_SCAM', 'label' => 'ADVANCE_FEE_TASK_SCAM', 'weight' => $w];
        }

        $score = min(100, $score);

        return [
            'input'      => $payload,
            'input_type' => 'sms',
            'risk_level' => $this->scoreToLevel($score),
            'score'      => $score,
            'verdict'    => $this->scoreToVerdict($score),
            'summary'    => $this->buildSmsSummary($findings),
            'findings'   => $findings,
            'url_tokens' => $embeddedUrl !== null ? ($urlResult['url_tokens'] ?? null) : null,
            'audit_log'  => $auditLog,
        ];
    }

    /* ======================================================================
       UPI ANALYSIS
       ====================================================================== */

    private function analyzeUpi(string $payload): array
    {
        $findings = [];
        $score    = 0;
        $auditLog = [];
        $lower    = strtolower($payload);

        // ── Rule: UPI PIN request on receive ─────────────────────────────
        $matchedUpi = $this->matchKeywords($lower, self::UPI_SCAM_PHRASES);
        if ($matchedUpi !== null) {
            $w = 50;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_UPI_REVERSE_TRICK',
                'tag'      => '[TRIGGER: UPI_PIN_REVERSAL_TRAP]',
                'title'    => 'UPI PIN requested to receive money — critical reversal trap',
                'body'     => 'NPCI infrastructure fundamental: a UPI PIN is entered ONLY to authorize debits from your account. You are NEVER asked for your PIN to receive funds. This is the primary UPI reverse charge scam targeting OLX and Quikr sellers.',
                'weight'   => $w,
                'severity' => 'high',
                'ref'      => 'NPCI UPI Product Specification v2.1 — Section 6.3: PIN Entry Flow',
            ];
            $auditLog[] = ['id' => 'RULE_UPI_REVERSE_TRICK', 'label' => 'UPI_DEBIT_DISGUISED_AS_CREDIT', 'weight' => $w];
        }

        // ── Rule: Suspicious VPA handle format ────────────────────────────
        if (preg_match('/[a-z0-9.+_-]+@[a-z]+/i', $payload, $vpaMatch)) {
            $vpa    = strtolower($vpaMatch[0]);
            $handle = explode('@', $vpa);
            // Flag if VPA username contains brand keyword
            $matchedBrand = $this->matchKeywords($vpa, self::PSB_KEYWORDS);
            if ($matchedBrand !== null && count($handle) === 2) {
                $w = 25;
                $score += $w;
                $findings[] = [
                    'number'   => count($findings) + 1,
                    'trigger'  => 'RULE_VPA_BRAND_IMPERSONATION',
                    'tag'      => '[TRIGGER: VPA_BRAND_SPOOF]',
                    'title'    => 'UPI VPA impersonates a bank brand',
                    'body'     => 'The VPA (' . htmlspecialchars($vpa, ENT_QUOTES, 'UTF-8') . ') contains a protected bank brand keyword. Legitimate bank VPAs use the format name@bankpsp (e.g., username@oksbi, username@hdfcbank) — not brand keywords as the recipient username.',
                    'weight'   => $w,
                    'severity' => 'high',
                    'ref'      => 'NPCI VPA Registration Policy — Protected Brand Name Restriction',
                ];
                $auditLog[] = ['id' => 'RULE_VPA_BRAND_IMPERSONATION', 'label' => 'VPA_BRAND_USERNAME_SPOOF', 'weight' => $w];
            }
        }

        // ── Rule: Urgency in UPI context ──────────────────────────────────
        $matchedUrgency = $this->matchKeywords($lower, self::URGENCY_PHRASES);
        if ($matchedUrgency !== null) {
            $w = 15;
            $score += $w;
            $findings[] = [
                'number'   => count($findings) + 1,
                'trigger'  => 'RULE_UPI_COERCIVE_URGENCY',
                'tag'      => '[TRIGGER: COERCIVE_URGENCY]',
                'title'    => 'Coercive urgency in UPI payment context',
                'body'     => 'This payload combines a UPI payment request with a coercive urgency phrase. Legitimate payment requests do not impose time pressure or account freeze threats.',
                'weight'   => $w,
                'severity' => 'medium',
                'ref'      => 'RBI Consumer Protection Guidelines — Unsolicited Payment Demands',
            ];
            $auditLog[] = ['id' => 'RULE_UPI_COERCIVE_URGENCY', 'label' => 'COERCIVE_PAYMENT_DEMAND', 'weight' => $w];
        }

        $score = min(100, $score);

        return [
            'input'      => $payload,
            'input_type' => 'upi',
            'risk_level' => $this->scoreToLevel($score),
            'score'      => $score,
            'verdict'    => $this->scoreToVerdict($score),
            'summary'    => $this->buildUpiSummary($findings),
            'findings'   => $findings,
            'url_tokens' => null,
            'audit_log'  => $auditLog,
        ];
    }

    /* ======================================================================
       HELPERS
       ====================================================================== */

    private function sanitize(string $input): string
    {
        $input = trim($input);
        if (mb_strlen($input) > self::MAX_INPUT_LENGTH) {
            $input = mb_substr($input, 0, self::MAX_INPUT_LENGTH);
        }
        return $input;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, ['url', 'sms', 'upi'], true) ? $type : 'sms';
    }

    /**
     * Extract the first URL from a text string.
     * Does NOT make any network request. Pure string parsing only.
     */
    private function extractUrl(string $text): ?string
    {
        if (preg_match('#(https?://[^\s"\'<>]+)#i', $text, $m)) {
            return $m[1];
        }
        // Also match bare domains that look like phishing URLs
        if (preg_match('#((?:www\.)?[a-z0-9][\w-]+(?:\.[a-z]{2,})+(?:/[^\s"\'<>]*)?)#i', $text, $m)) {
            // Only return if it has a path that looks like a login/KYC page
            if (preg_match('#/(?:login|kyc|update|verify|account|secure|auth)#i', $m[1])) {
                return $m[1];
            }
        }
        return null;
    }

    private function isRawIp(string $host): bool
    {
        // IPv4
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }
        // IPv6
        if (filter_var(trim($host, '[]'), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }
        return false;
    }

    /** @param string[] $keywords */
    private function matchKeywords(string $haystack, array $keywords): ?string
    {
        foreach ($keywords as $kw) {
            if (str_contains($haystack, $kw)) {
                return $kw;
            }
        }
        return null;
    }

    /**
     * Return true if the host is a known official Indian banking/govt domain.
     * This is a SAFELIST used only to suppress false-positive findings.
     */
    private function isOfficialDomain(string $host): bool
    {
        $officialDomains = [
            'onlinesbi.sbi', 'sbi.co.in', 'sbiyono.sbi',
            'hdfcbank.com', 'icicibank.com', 'axisbank.com',
            'kotakbank.com', 'pnbindia.in', 'bankofbaroda.in',
            'canararobeco.in', 'canarabank.com',
            'cybercrime.gov.in', 'npci.org.in', 'rbi.org.in',
            'sachet.rbi.org.in', 'incometax.gov.in', 'uidai.gov.in',
            'trai.gov.in', 'india.gov.in', 'mha.gov.in',
        ];
        $host = ltrim($host, 'www.');
        foreach ($officialDomains as $d) {
            if ($host === $d || str_ends_with($host, '.' . $d)) {
                return true;
            }
        }
        return false;
    }

    private function hasSuspiciousTld(string $host): bool
    {
        $suspiciousTlds = ['.xyz', '.tk', '.ml', '.ga', '.cf', '.gq', '.top', '.club', '.work', '.loan', '.win'];
        foreach ($suspiciousTlds as $tld) {
            if (str_ends_with($host, $tld)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build URL anatomy tokens for display in the annotated-url partial.
     * @return array<int, array{value:string, label:string, risk:string}>
     */
    private function buildTokens(string $scheme, string $host, string $path, string $query): array
    {
        $tokens = [];
        $n = 1;

        if ($scheme !== '') {
            $risk     = ($scheme === 'https') ? 'safe' : 'warning';
            $label    = ($scheme === 'http') ? "[{$n}: UNENCRYPTED]" : "[{$n}: ENCRYPTED TLS]";
            $tokens[] = ['value' => $scheme . '://', 'label' => $label, 'risk' => $risk];
            $n++;
        }

        if ($host !== '') {
            $isIp = $this->isRawIp($host);
            if ($isIp) {
                $tokens[] = ['value' => $host, 'label' => "[{$n}: RAW IP HOST]", 'risk' => 'danger'];
            } elseif ($this->matchKeywords(strtolower($host), array_merge(self::PSB_KEYWORDS, self::UTILITY_KEYWORDS)) !== null
                      && !$this->isOfficialDomain($host)) {
                $tokens[] = ['value' => $host, 'label' => "[{$n}: LOOKALIKE BRAND]", 'risk' => 'warning'];
            } else {
                $tokens[] = ['value' => $host, 'label' => "[{$n}: HOSTNAME]", 'risk' => 'neutral'];
            }
            $n++;
        }

        if ($path !== '' && $path !== '/') {
            $pathRisk = 'neutral';
            if ($this->matchKeywords(strtolower($path), array_merge(self::PSB_KEYWORDS, self::UTILITY_KEYWORDS)) !== null) {
                $pathRisk = 'warning';
            }
            if (stripos($path, '.apk') !== false) {
                $pathRisk = 'danger';
            }
            $tokens[] = ['value' => $path, 'label' => "[{$n}: PATH]", 'risk' => $pathRisk];
            $n++;
        }

        if ($query !== '') {
            $qRisk = preg_match('/(?:auth|token|session|key|pass|verify|otp|id)=/i', $query) ? 'warning' : 'neutral';
            $tokens[] = ['value' => '?' . $query, 'label' => "[{$n}: QUERY]", 'risk' => $qRisk];
        }

        return $tokens;
    }

    private function scoreToLevel(int $score): string
    {
        if ($score >= self::THRESHOLD_CRITICAL) return 'critical';
        if ($score >= self::THRESHOLD_HIGH)     return 'high';
        if ($score >= self::THRESHOLD_MODERATE) return 'moderate';
        return 'low';
    }

    private function scoreToVerdict(int $score): string
    {
        if ($score >= self::THRESHOLD_CRITICAL) return 'High-Confidence Phishing / Credential Harvest';
        if ($score >= self::THRESHOLD_HIGH)     return 'Suspicious — Multiple High-Risk Indicators';
        if ($score >= self::THRESHOLD_MODERATE) return 'Moderate Concern — Review Carefully';
        return 'Low Immediate Risk — Remain Cautious';
    }

    private function buildUrlSummary(array $findings, string $host, string $scheme): string
    {
        if (empty($findings)) {
            return 'No high-confidence phishing indicators matched by local rules. This does not mean the link is safe — novel attack domains created recently may not match known patterns. Never enter credentials on an unrecognised page.';
        }
        $count = count($findings);
        $base  = "Heuristic analysis matched {$count} indicator" . ($count === 1 ? '' : 's') . '.';
        if ($this->isRawIp($host)) {
            $base .= ' The host is a raw IP address — a strong indicator of a malicious or hastily-deployed phishing server.';
        }
        if ($scheme === 'http') {
            $base .= ' The link uses unencrypted HTTP, meaning any data you enter will be exposed in transit.';
        }
        return $base . ' Do not open this link or enter any credentials.';
    }

    private function buildSmsSummary(array $findings): string
    {
        if (empty($findings)) {
            return 'No high-confidence scam indicators matched by local rules. This does not mean the message is safe. If you received a financial demand via SMS from an unknown sender, treat it with extreme caution.';
        }
        $count = count($findings);
        return "Heuristic analysis matched {$count} indicator" . ($count === 1 ? '' : 's') . '. The message shows characteristics consistent with phishing or social engineering attempts targeting Indian users. Do not act on demands in this message without independently verifying through the official channel.';
    }

    private function buildUpiSummary(array $findings): string
    {
        if (empty($findings)) {
            return 'No high-confidence UPI scam indicators matched by local rules. Always verify the recipient VPA carefully before authorizing any UPI payment. Remember: you never need to enter your UPI PIN to receive money.';
        }
        return 'This UPI payload shows one or more indicators of a reversal trap or impersonation scam. Do not enter your UPI PIN. Block the sender and report to your UPI app provider and 1930.';
    }

    private function emptyResult(string $payload, string $inputType): array
    {
        return [
            'input'      => $payload,
            'input_type' => $inputType,
            'risk_level' => 'low',
            'score'      => 0,
            'verdict'    => 'No input provided',
            'summary'    => 'Nothing was submitted. Please paste a URL, SMS message, or UPI string.',
            'findings'   => [],
            'url_tokens' => null,
            'audit_log'  => [],
        ];
    }
}
