# Tasks: Analysis Engine and VirusTotal Integration

## Status summary

All core implementation tasks are complete. The open items are integration tests requiring a live API key or a live database.

## Milestone 4 — Heuristic analysis

- [x] `UrlAnalysisService::analyze()` routes on `url` / `sms` / `upi`
- [x] 8 URL rules implemented and weighted
- [x] 7 SMS rules implemented (including recursive URL detection at 50% weight)
- [x] 3 UPI rules implemented
- [x] Risk bands (low/moderate/high/critical) with correct thresholds
- [x] Every finding includes: trigger ID, tag, title, body, weight, severity, ref
- [x] URL token anatomy built for annotated-url partial
- [x] Input capped at 4000 chars; sanitized (NUL stripped)
- [x] Server never fetches submitted URLs (SSRF safe — verified in code)
- [x] "No match" never declared safe — stated in summary and limitations-box
- [x] `AnalysisRepository::create()` persists result to `analyses` table
- [x] Analysis result passed to `url_result.php` view correctly
- [x] 5/5 smoke tests pass (command: `php tests\smoke_analysis.php`)

## Milestone 8 — VirusTotal integration

- [x] `ThreatIntelProviderInterface.php` created
- [x] `VirusTotalResult.php` — 5 named constructors, `toThreatStatus()` mapping
- [x] `VirusTotalProvider.php` — `lookup()` with guard conditions, `buildUrlId()`, `fetch()` via PHP stream_context, `parseSuccessResponse()`, `handleUnexpectedStatus()`, `extractHttpStatus()`
- [x] `ReportCache.php` — `get()` / `put()` with TTL, corrupt-entry handling, directory creation
- [x] `AnalysisController::analyze()` calls VT for URL type only; SMS/UPI excluded
- [x] `AnalysisRepository::create()` accepts and stores `$threatStatus`
- [x] `url_result.php` VT card renders all 5 states with correct caveats
- [x] 40/40 unit tests pass (command: `php tests\Unit\VirusTotalProviderTest.php`)
- [x] All PHP files pass syntax check

## Open items

- [ ] End-to-end integration test with live `VIRUSTOTAL_API_KEY` (requires .env configuration)
- [ ] Analysis history page `/analyses` — route not wired in `public/index.php`
- [ ] Additional threat-intelligence providers (URLhaus, Google Safe Browsing) — unimplemented
