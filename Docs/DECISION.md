# ScamShield — Architecture Decision Records (ADR)

- **Status:** v1.1 — updated after full MVP implementation
- **Last reviewed:** 2026-10-14

Record decisions that materially affect architecture, security, product scope, or maintenance. Do not add an ADR for every minor code change. If a decision is superseded, keep the old record and add a new one.

---

## ADR-001 — Start as a PHP/MySQL web application

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** The project must satisfy a PHP + MySQL learning/project requirement and should be feasible to build and test locally.
- **Decision:** Build a local-first web application using PHP 8.2, MariaDB 10.4 through XAMPP, and custom HTML/CSS/JS (no Bootstrap CDN for final UI — custom design system from Stitch).
- **Note:** The original ADR referenced Bootstrap; the final implementation uses a custom three-file CSS system (tokens.css / base.css / components.css) derived from Stitch-exported designs.
- **Consequences:** The first version can analyze user-submitted data but cannot automatically inspect all device activity. Browser/mobile clients are later projects.
- **Revisit when:** The web MVP is tested and the need for device-level protection is clearly defined.

---

## ADR-002 — Deterministic rules before external AI

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** Paid APIs should not be a requirement, and initial detection needs to be transparent and testable.
- **Decision:** Implement a versioned PHP rule engine (`UrlAnalysisService`) for URL, SMS, and UPI indicators first. Keep any future AI provider behind a separate interface.
- **Consequences:** The first analyzer is heuristic (rules version 1.4). Its performance must be measured against labeled examples. Rule weights and risk bands are provisional until evaluated.
- **Revisit when:** A useful labeled evaluation set exists and a candidate AI model can be assessed for quality, privacy, latency, and cost.

---

## ADR-003 — Explain uncertainty; never equate absence of a match with safety

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** Threat feeds are incomplete and heuristic checks can produce false negatives.
- **Decision:** Results show the triggered indicators, source status, and limitations. "No match" is not "safe," and no result proves a person is a criminal.
- **Consequences:** Every result page includes a limitations box. "No rules matched" explicitly warns that this does not mean the link is safe. VirusTotal zero detections carry a mandatory "not evidence of safety" caveat.
- **Revisit when:** Risk bands are calibrated using a documented evaluation process; the core principle remains.

---

## ADR-004 — Do not fetch arbitrary submitted URLs from the PHP server

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** Fetching a submitted URL can expose the server to SSRF, internal-network probing, redirect abuse, and unsafe content.
- **Decision:** URL analysis parses locally and calls only the VirusTotal v3 API at a fixed, known endpoint using the URL's identifier (not the URL itself). No other outbound fetching.
- **Consequences:** Some signals (live page content) are not available. VirusTotal integration uses `GET /api/v3/urls/{id}` — the submitted URL bytes are used only to compute the Base64 identifier locally.
- **Revisit when:** A concrete use case justifies remote retrieval and a hardened isolated fetch service is designed.

---

## ADR-005 — Private evidence storage with authorized downloads

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** Reports may contain sensitive personal and financial evidence.
- **Decision:** Store evidence outside the public web root at `STORAGE_EVIDENCE_PATH`; use random server-generated storage keys and an authorization-checked download handler.
- **Consequences:** Direct static file links are not used. Every download calls `IncidentController::downloadEvidence()` which verifies ownership.
- **Revisit when:** The deployment platform imposes a different secure storage architecture.

---

## ADR-006 — Official reporting remains a separate process

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** ScamShield is not a law-enforcement system, and no authorized integration has been established.
- **Decision:** Generate an incident summary and guide users to current official channels (1930, cybercrime.gov.in). Do not claim a complaint was submitted unless a supported integration confirms it.
- **Consequences:** A ScamShield case reference (`SS-YYYY-XXXXXX`) is explicitly distinct from an official complaint acknowledgement. Every incident view carries this disclaimer.
- **Revisit when:** An official, permitted integration is documented and its eligibility, security, and legal requirements are verified.

---

## ADR-007 — No destructive repository operations without approval

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** AI coding tools may overwrite files or reset Git history.
- **Decision:** Inspect `git status` and existing files before changes. Do not reset, delete, overwrite unrelated files, commit, or push without user authorization.
- **Consequences:** Work begins with inspection and a plan, not blind scaffolding.
- **Revisit when:** No change required; this is a standing rule.

---

## ADR-008 — VirusTotal selected as threat-intelligence provider

- **Status:** Accepted (resolved 2026-10-14; previously "Proposed")
- **Date resolved:** 2026-10-14
- **Context:** A community feed or API may have limitations, access conditions, coverage gaps, or terms that do not fit the intended use. ADR-008 was initially proposed in a "pending evaluation" state.
- **Decision:** Use the VirusTotal v3 Public API (`GET /api/v3/urls/{id}`) to retrieve existing URL reports. The public API is free for registered users, permits non-commercial/educational use, and is well-documented. Only URL-type inputs trigger a lookup; SMS and UPI text are never sent to VT.
- **Implementation:**
  - `app/Services/Analysis/VirusTotalProvider.php` — HTTP client using PHP stream wrappers; no curl dependency.
  - `app/Services/Analysis/VirusTotalResult.php` — immutable value object; 5 statuses: `found`, `not_found`, `rate_limited`, `unavailable`, `not_configured`.
  - `app/Services/Analysis/ReportCache.php` — flat-file cache in `storage/vt_cache/`; SHA-256-keyed JSON; configurable TTL.
  - `app/Services/Analysis/ThreatIntelProviderInterface.php` — provider contract.
- **Required `.env` variables:**
  - `THREAT_INTEL_PROVIDER=virustotal`
  - `VIRUSTOTAL_API_KEY=<your_key>`
  - `VIRUSTOTAL_CACHE_TTL_SECONDS=3600` (optional; default 3600)
- **Constraints:**
  - Public API: 4 requests/minute, 500/day. Cache prevents repeated lookups.
  - No automatic URL submission (no POST); only retrieves existing reports.
  - "Not found" and zero detections are not evidence of safety.
  - Provider is fully optional; heuristic analysis works independently if not configured.
- **Note on OpenPhish:** The earlier research note (below) remains relevant. OpenPhish is NOT integrated and is not planned for integration without explicit term verification.
- **Revisit when:** Quota limitations become a problem under production load, or a different/additional provider with better coverage is evaluated.

### Research note — OpenPhish remains unselected

- **Status:** Pending provider evaluation — no code integration exists
- **Finding:** OpenPhish community feed terms restrict use to personal use only. Integration into any public-facing or commercial service would require explicit permission.
- **Decision:** OpenPhish is NOT integrated. If evaluated in the future, current official terms must be reviewed and documented before implementation.
- **Sources:** https://openphish.com/phishing_feeds.html ; https://www.openphish.com/terms.html

---

## ADR-009 — Document-root strategy: Option A (virtual host serving public/)

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** XAMPP's default setup serves `C:\xampp\htdocs` at `localhost`, which would expose the entire `AntiScam\` repository tree if accessed via `localhost/AntiScam/`.
- **Decision:** Configure an Apache virtual host for `scamshield.local` with `DocumentRoot` set to `C:/xampp/htdocs/AntiScam/public`. All application code, configuration, and storage remain outside `public/`.
- **Consequences:**
  - `http://scamshield.local/` serves only `public/index.php` and `public/assets/`.
  - `app/`, `config/`, `database/`, `storage/`, `.env`, and `Docs/` are unreachable over HTTP.
  - `public/.htaccess` routes all non-file/non-directory requests through `public/index.php`.
  - Windows hosts entry (`127.0.0.1 scamshield.local`) requires Administrator elevation.
- **Revisit when:** The project moves to a hosted server.
