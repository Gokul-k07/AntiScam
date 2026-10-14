# Requirements Document

## Introduction

This spec covers Milestones 4 and 8 of ScamShield:
- **Milestone 4** — Rule-based heuristic analysis of URLs, SMS/messages, and UPI payment strings.
- **Milestone 8** — Optional VirusTotal v3 external reputation lookup for URL-type analyses.

Both milestones are **fully implemented** as of 2026-10-14. This spec documents the accepted requirements against which the implementation was built and tested.

## Glossary

- **Heuristic engine:** `UrlAnalysisService` — deterministic, versioned rule set; no machine learning.
- **Airgapped analysis:** The heuristic engine never makes outbound network calls; all analysis is local.
- **VT provider:** `VirusTotalProvider` — retrieves existing VT URL reports; never submits/scans URLs.
- **VT result:** `VirusTotalResult` — immutable value object with status and engine-count fields.
- **Report cache:** `ReportCache` — flat-file cache in `storage/vt_cache/`; SHA-256 keyed JSON.
- **Risk band:** low (0–24) / moderate (25–54) / high (55–84) / critical (85–100).
- **Threat status:** DB ENUM written to `analyses.threat_status`: `not_checked`, `no_match`, `match`, `unavailable`.

## Requirements

### Requirement 1: URL Heuristic Analysis

**User Story:** As a user, I want to submit a suspicious URL and receive an immediate, explainable risk assessment so I can decide whether to interact with it.

#### Acceptance Criteria

1. WHEN a URL is submitted, THEN `UrlAnalysisService::analyze($payload, 'url')` is called — no outbound fetch of the submitted URL occurs (SSRF Rule §4.1).
2. WHEN a URL is submitted, THEN up to 8 deterministic rules are evaluated; each triggered rule returns: trigger ID, tag, title, body, weight, severity, and a regulatory/advisory reference.
3. WHEN the URL uses HTTP (not HTTPS), THEN `RULE_NO_TLS_V1` fires with weight 15.
4. WHEN the host is a raw IPv4 or IPv6 address, THEN `RULE_RAW_IP_TARGET` fires with weight 35.
5. WHEN the URL contains a known Indian PSB brand keyword on a non-official domain, THEN `RULE_PSB_KEYWORD_SLUG` fires with weight 20.
6. WHEN the URL contains a government/utility keyword on an unofficial domain, THEN `RULE_UTIL_KEYWORD` fires with weight 12.
7. WHEN the host ends in a high-abuse TLD (.xyz, .tk, .ml, .ga, .cf, .gq, .top, .club, .work, .loan, .win), THEN `RULE_SUSPICIOUS_TLD` fires with weight 8.
8. WHEN the query string contains a sensitive parameter name (auth, token, session, key, pass, verify, otp, id), THEN `RULE_HARVEST_QUERY` fires with weight 8.
9. WHEN the path or query contains `.apk`, THEN `RULE_APK_DELIVERY` fires with weight 30.
10. WHEN the domain contains 3 or more hyphens, THEN `RULE_HYPHEN_STACKING` fires with weight 10.
11. WHEN the total score reaches or exceeds 85, THEN risk_level is `critical`.
12. WHEN the total score is 55–84, THEN risk_level is `high`.
13. WHEN the total score is 25–54, THEN risk_level is `moderate`.
14. WHEN the total score is 0–24 OR no rules match, THEN risk_level is `low`.
15. WHEN no rules match, THEN the summary states that the absence of matches is NOT evidence of safety.
16. WHEN a URL is submitted, THEN `url_tokens` (scheme, host, path, query) are returned with risk labels for display.
17. WHEN input exceeds 4000 characters, THEN it is truncated before processing.

#### Verified test evidence

| Case | Input | Expected | Actual (2026-10-14) |
|---|---|---|---|
| APK phishing URL | `http://192.168.1.5/login/verify-kyc.apk` | critical / 92 / 4 findings | ✅ PASS |
| Empty input | `"   "` | low / 0 / 0 findings | ✅ PASS |
| Official SBI URL | `https://onlinesbi.sbi/personal/login.html` | low / 0 / 0 findings | ✅ PASS |

### Requirement 2: SMS and Message Text Analysis

**User Story:** As a user, I want to paste SMS or chat text and see which scam patterns it contains.

#### Acceptance Criteria

1. WHEN SMS text is submitted, THEN `UrlAnalysisService::analyze($payload, 'sms')` is called.
2. WHEN the text contains a coercive urgency phrase (e.g., "account freeze", "2 hours", "legal action"), THEN `RULE_COERCIVE_URGENCY` fires with weight 20.
3. WHEN the text contains a PSB brand keyword (e.g., "sbi", "hdfc"), THEN `RULE_PSB_IMPERSONATION` fires with weight 20.
4. WHEN the text contains a KYC or credential harvest phrase (e.g., "update kyc", "otp"), THEN `RULE_KYC_HARVEST` fires with weight 20.
5. WHEN the text contains an embedded HTTP/HTTPS URL, THEN `analyzeUrl()` is called recursively on that URL with 50% weight applied to its score.
6. WHEN the text originates from a 10-digit mobile number (not a DLT alphanumeric header), THEN `RULE_DLT_NONCOMPLIANCE` fires with weight 15.
7. WHEN the text contains courier/arrest scam phrases (e.g., "digital arrest", "narcotics", "cbcid"), THEN `RULE_DIGITAL_ARREST` fires with weight 25.
8. WHEN the text contains job-scam phrases (e.g., "work from home", "prepaid task", "telegram group"), THEN `RULE_JOB_SCAM` fires with weight 18.
9. WHEN SMS text is submitted, THEN the text is NEVER sent to VirusTotal or any external API.

#### Verified test evidence

| Case | Input | Expected | Actual (2026-10-14) |
|---|---|---|---|
| SBI KYC SMS with embedded URL | "Dear SBI user… Update KYC at http://sbi-kyc-update-portal.in/login within 2 hours." | high / 83 / 6 findings | ✅ PASS |

### Requirement 3: UPI and Payment String Analysis

**User Story:** As a user, I want to paste a UPI string or payment request message and see if it shows reversal-trap or impersonation patterns.

#### Acceptance Criteria

1. WHEN UPI text is submitted, THEN `UrlAnalysisService::analyze($payload, 'upi')` is called.
2. WHEN the text contains a PIN-to-receive phrase (e.g., "enter upi pin to receive", "pin to accept"), THEN `RULE_UPI_REVERSE_TRICK` fires with weight 50.
3. WHEN the VPA in the text contains a PSB brand keyword, THEN `RULE_VPA_BRAND_IMPERSONATION` fires with weight 25.
4. WHEN the text contains a coercive urgency phrase in a UPI context, THEN `RULE_UPI_COERCIVE_URGENCY` fires with weight 15.
5. WHEN UPI text is submitted, THEN the text is NEVER sent to VirusTotal or any external API.
6. WHEN no rules match, THEN the summary explicitly states that UPI PIN is only required to authorize debits, never to receive money.
7. QR image decoding is NOT implemented — text input only.

#### Verified test evidence

| Case | Input | Expected | Actual (2026-10-14) |
|---|---|---|---|
| UPI reverse trick | "Enter your UPI PIN to receive refund of Rs 4999" | moderate / 50 / 1 finding | ✅ PASS |

### Requirement 4: VirusTotal External Reputation Lookup

**User Story:** As a user, I want to see VirusTotal engine counts alongside the heuristic result so I have external reputation context when evaluating a URL.

#### Acceptance Criteria

1. WHEN `THREAT_INTEL_PROVIDER=virustotal` and `VIRUSTOTAL_API_KEY` is set, THEN `VirusTotalProvider::lookup($url)` is called after heuristics, for URL-type analyses only.
2. WHEN `THREAT_INTEL_PROVIDER` is not `virustotal` or the key is empty, THEN `VirusTotalResult::notConfigured()` is returned immediately — no network call is made.
3. WHEN the URL identifier is computed, THEN it MUST be `rtrim(strtr(base64_encode($url), '+/', '-_'), '=')` (unpadded URL-safe Base64).
4. WHEN a VT report exists, THEN the response HTTP 200 is parsed: `data.attributes.last_analysis_stats` fields `malicious`, `suspicious`, `harmless`, `undetected`, `timeout` are extracted.
5. WHEN VT returns HTTP 404, THEN `VirusTotalResult::notFound()` is returned.
6. WHEN VT returns HTTP 429, THEN `VirusTotalResult::rateLimited()` is returned — no retry.
7. WHEN a network error, timeout (8 s), invalid JSON, or unexpected HTTP status occurs, THEN `VirusTotalResult::unavailable()` is returned — heuristic result is unaffected.
8. WHEN VT is not configured, THEN the VT card is hidden from the result view.
9. WHEN VT returns any state, THEN the heuristic score and findings are UNAFFECTED.
10. WHEN a found result is displayed, THEN a mandatory caveat states: engine counts are external evidence; zero detections are not evidence of safety.
11. WHEN a not_found result is displayed, THEN a mandatory caveat states: absence is not evidence of safety.
12. WHEN any VT result is displayed, THEN it is clearly labelled as "External Reputation" and distinct from the heuristic verdict.

### Requirement 5: VirusTotal Report Caching

**User Story:** As a developer, I want VT results cached locally so that repeated lookups for the same URL do not consume the public API quota unnecessarily.

#### Acceptance Criteria

1. WHEN a `found` or `not_found` result is obtained, THEN it is cached in `storage/vt_cache/{sha256(url)}.json`.
2. WHEN a `rate_limited` or `unavailable` result is obtained, THEN it is NOT cached.
3. WHEN a cached result exists and has not exceeded the TTL, THEN it is returned without a network call; `fromCache=true` is set on the result.
4. WHEN a cached result exceeds the TTL (default 3600 s), THEN it is discarded and a fresh API call is made.
5. WHEN a cache file contains corrupt JSON, THEN it is silently deleted and null is returned.
6. WHEN `VIRUSTOTAL_CACHE_TTL_SECONDS` is set in `.env`, THEN that value overrides the default.

### Requirement 6: VirusTotal Security and Privacy Constraints

**User Story:** As a security-conscious developer, I want the VirusTotal integration to handle the API key and user data safely so that secrets are never exposed and private message content never leaves the server.

#### Acceptance Criteria

1. WHEN `VirusTotalProvider` makes an API call, THEN `VIRUSTOTAL_API_KEY` is passed ONLY as an HTTP request header (`x-apikey`) — never logged, never stored in an object property, never echoed.
2. WHEN the VT API is called, THEN TLS peer verification is enabled (`verify_peer=true`, `verify_peer_name=true`).
3. WHEN SMS or UPI input is submitted, THEN it is NEVER passed to `VirusTotalProvider::lookup()`.
4. WHEN logging VT results, THEN no API key or user-submitted URL content appears in log entries.

#### Verified test evidence (2026-10-14)

| Test group | Cases | Result |
|---|---|---|
| VirusTotalResult named constructors | 24 | ✅ 24/24 PASS |
| toThreatStatus() mapping | 7 | ✅ 7/7 PASS |
| URL identifier algorithm (round-trip) | 5 | ✅ 5/5 PASS |
| Provider guard conditions | 4 | ✅ 4/4 PASS |
| ReportCache read/write/TTL/corrupt/expiry | 10 | ✅ 10/10 PASS |
| **Total** | **50** | **✅ 50/50 PASS** |

> Note: The unit tests above are all offline (no network). Live API behavior (HTTP 200/404/429 responses) requires a real `VIRUSTOTAL_API_KEY` in `.env` — not yet tested end-to-end.

## Open Items

| Item | Status |
|---|---|
| Integration test with live VT API key | Not done — requires VIRUSTOTAL_API_KEY |
| Analysis history page `/analyses` | Not done — route not wired |
| Additional providers (URLhaus, GSB) | Not done — terms not yet reviewed |
