# ScamShield — Architecture

- **Status:** v1.1 — updated after full MVP implementation (Milestones 0–8)
- **Last reviewed:** 2026-10-14

## 1. Architectural approach

ScamShield uses a custom MVC skeleton in PHP 8.2 with no external framework. Separation between HTTP handling, domain services, persistence, and views is explicit and enforced by directory structure and naming conventions. The project targets local XAMPP development; public deployment is a later decision.

## 2. Logical architecture

```
Browser
  │
  │  HTTP (scamshield.local — Apache vhost → public/ only)
  ▼
public/index.php          ← Front controller; bootstraps app, defines all routes
  │
  ├── app/Support/Router.php       ← Array-based dispatcher; dynamic pattern handler
  ├── app/Support/Bootstrap.php    ← Loads env, config, autoloader, error handler, session
  ├── app/Support/ErrorHandler.php ← Converts errors/exceptions → log + generic user message
  │
  ├── GET /             → HomeController
  ├── GET /health       → HealthController (JSON status check)
  ├── GET|POST /analyze → AnalysisController
  │       │
  │       ├── UrlAnalysisService   ← Local heuristic engine (airgapped, no outbound calls)
  │       ├── VirusTotalProvider   ← VT v3 GET /urls/{id} (URL type only, optional)
  │       │       └── ReportCache  ← Flat-file SHA-256-keyed cache (storage/vt_cache/)
  │       └── AnalysisRepository  ← Persists result + threat_status to analyses table
  │
  ├── Auth routes       → AuthController + UserRepository
  ├── /incidents/*      → IncidentController
  │       ├── IncidentService     ← Create, validate, status transitions, reference gen
  │       ├── EvidenceService     ← Upload validation, MIME check, random key, SHA-256
  │       └── IncidentRepository + EvidenceRepository
  └── /admin/*          → AdminController (requireAdmin middleware)
          └── AuditRepository     ← Append-only audit log
```

## 3. Directory structure

See `.kiro/steering/structure.md` for the full verified tree. Key boundaries:

| Directory | Web-accessible | Notes |
|---|---|---|
| `public/` | Yes (document root) | Index, .htaccess, CSS/JS/images |
| `app/` | No | All PHP classes |
| `config/` | No | env.php, app.php, database.php |
| `database/` | No | schema.sql, setup.php |
| `storage/evidence/` | No | Private upload files |
| `storage/vt_cache/` | No | VT report cache (SHA-256 keyed JSON) |
| `storage/logs/` | No | app.log, php_errors.log |
| `Docs/` | No | Project documentation |
| `.env` | No | Local secrets (gitignored) |

## 4. Module descriptions

### Authentication (`AuthController`, `UserRepository`, `Session`, `Csrf`)
- Registration, login, logout.
- Passwords: `password_hash(PASSWORD_BCRYPT, ['cost' => 12])` / `password_verify()`.
- Session ID regenerated after login via `session_regenerate_id(true)`.
- CSRF tokens on every state-changing POST — validated server-side via `Csrf::validate()`.
- All private routes enforce authorization server-side, not just by hiding UI.

### Analysis (`AnalysisController`, `UrlAnalysisService`, `VirusTotalProvider`)

**Local heuristic engine (`UrlAnalysisService`):**

Input types: `url`, `sms`, `upi`. Max input: 4000 characters.

URL rules (8 total):

| Rule ID | Signal | Weight |
|---|---|---|
| RULE_NO_TLS_V1 | HTTP (no TLS) | 15 |
| RULE_RAW_IP_TARGET | Raw IPv4/IPv6 host | 35 |
| RULE_PSB_KEYWORD_SLUG | Indian PSB brand in non-official host/path | 20 |
| RULE_UTIL_KEYWORD | Govt/utility keyword on unofficial domain | 12 |
| RULE_SUSPICIOUS_TLD | High-abuse TLD (.xyz, .tk, .ml, etc.) | 8 |
| RULE_HARVEST_QUERY | Sensitive query parameter (token, otp, auth…) | 8 |
| RULE_APK_DELIVERY | .apk in path or query | 30 |
| RULE_HYPHEN_STACKING | 3+ hyphens in domain (brand stacking) | 10 |

SMS rules (7 total): coercive urgency, PSB impersonation, KYC/credential harvest, embedded URL (analysed recursively at 50% weight), DLT non-compliance (mobile sender), digital arrest, job scam.

UPI rules (3 total): PIN-to-receive reversal trap, VPA brand impersonation, coercive urgency.

Risk bands: low (0–24) · moderate (25–54) · high (55–84) · critical (85–100).

Rules version stored with every result: `1.4`.

**VirusTotal provider (`VirusTotalProvider`):**
- Calls `GET https://www.virustotal.com/api/v3/urls/{id}` via PHP stream wrappers (no curl).
- URL identifier: `rtrim(strtr(base64_encode($url), '+/', '-_'), '=')` — unpadded URL-safe Base64 as documented by VT.
- Parses `data.attributes.last_analysis_stats`: malicious, suspicious, harmless, undetected, timeout.
- Five result states: `found`, `not_found`, `rate_limited`, `unavailable`, `not_configured`.
- SMS/UPI text is **never** sent to VT.
- No automatic URL submission (no POST to VT).
- No retry on 429. Timeout: 8 seconds.
- API key read from `$_ENV['VIRUSTOTAL_API_KEY']` only — never logged or stored in properties.

**Report cache (`ReportCache`):**
- Stores `found` and `not_found` results only (transient errors are not cached).
- Cache files: `storage/vt_cache/{sha256(url)}.json`.
- Default TTL: 3600 seconds (overridden by `VIRUSTOTAL_CACHE_TTL_SECONDS`).

### Incident management (`IncidentController`, `IncidentService`, `EvidenceService`)
- Incidents require authenticated users.
- Public reference format: `SS-YYYY-XXXXXX` (6 random hex chars, uppercase).
- Status flow: `submitted` → `under_review` → `info_needed` ↔ `under_review` → `closed`.
- Status changes require an explicit reason; a history row is appended and an audit entry is written.

Evidence upload validation:
- Allowlist: jpg/jpeg, png, gif, pdf, txt, csv.
- Max size: 10 MB.
- Server-side MIME verification via `fileinfo`.
- Storage key: random hex (bin2hex(random_bytes(…))), never the original filename.
- SHA-256 of content stored for integrity.
- Files stored at `STORAGE_EVIDENCE_PATH` (outside web root).
- Download requires server-side ownership check.

### Admin review (`AdminController`, `AuditRepository`)
- Every admin route calls `AuthMiddleware::requireAdmin()` — role check in DB.
- Audit log is append-only at application level.
- Admin detail page shows incident info, evidence metadata, and status change form (CSRF protected).

## 5. Database schema

Six tables in `database/schema.sql`:

```
users              — id, display_name, email, password_hash, role ENUM(user|admin), status, timestamps
analyses           — id, user_id, input_type, input_excerpt(500), risk_score, risk_band, rules_version, result_json, threat_status ENUM(not_checked|no_match|match|unavailable), created_at
incidents          — id, public_reference CHAR(12), user_id, incident_type, incident_at, description, channel, status ENUM(submitted|under_review|info_needed|closed), assigned_admin_id, analysis_id, timestamps
evidence_files     — id, incident_id, uploaded_by, storage_key, original_name, detected_mime, size_bytes, sha256, created_at
incident_status_history — id, incident_id, changed_by, old_status, new_status, reason, created_at
audit_logs         — id, actor_user_id, action, target_type, target_id, metadata_json, ip_address, created_at
```

All foreign keys use InnoDB; charset utf8mb4_unicode_ci throughout.

## 6. Request lifecycle (POST /analyze)

```
1. Bootstrap: env loaded, autoloader registered, session started
2. CSRF token validated
3. Input sanitized: NUL stripped, truncated to 4000 chars
4. UrlAnalysisService::analyze($payload, $inputType) → $result array
5. If input_type === 'url':
     VirusTotalProvider::lookup($url) → $vtResult
   Else:
     VirusTotalResult::notConfigured() (SMS/UPI never leaves the server)
6. AnalysisRepository::create($result, $userId, $vtResult->toThreatStatus())
7. Render analysis/url_result.php with $result + $vtResult
```

## 7. Security controls summary

| Control | Implementation |
|---|---|
| SQL injection | PDO prepared statements everywhere |
| Password storage | bcrypt cost 12 via password_hash() |
| Session fixation | session_regenerate_id(true) after login |
| CSRF | Per-session token, validated on every POST |
| SSRF | No outbound fetch of user-submitted URLs; VT call uses fixed endpoint only |
| XSS | htmlspecialchars() on all output, ENT_QUOTES, UTF-8 |
| Path traversal | Evidence storage key is random; original filename stripped of path components |
| Upload abuse | Allowlist + fileinfo MIME check + size limit + random storage key |
| Secret leakage | .env gitignored; Logger redacts api_key, secret, token, password, key |
| Audit trail | Append-only audit_logs table; no UPDATE/DELETE at app level |

## 8. Error handling

- `ErrorHandler::register()` catches all PHP errors and uncaught exceptions.
- Users see a generic message with an incident code.
- Detailed diagnostics go to `storage/logs/app.log` (secrets redacted by `Logger`).
- `APP_DEBUG=false` in production prevents display_errors.
- Threat-intelligence failure never crashes analysis — all VT outcomes are value objects, not exceptions.

## 9. Deployment boundary

Current target: local XAMPP at `http://scamshield.local/`. Public deployment requires HTTPS, reviewed PHP/MariaDB versions, secret rotation, rate limiting, and a backup strategy. All are post-MVP decisions.
