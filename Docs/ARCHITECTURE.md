# ScamShield — Architecture

- **Status:** Initial baseline, v0.1
- **Last reviewed:** 2026-10-12

## 1. Architectural approach

Use a small, modular PHP application with clear separation between HTTP handling, application services, risk rules, persistence, and views. Avoid introducing a large framework or complex infrastructure before the MVP needs it.

## 2. Logical architecture

```text
Browser
  |
  | HTTPS in any deployed environment; localhost during development
  v
PHP HTTP entry point / Router
  |
  +--> Authentication & Authorization
  |
  +--> Analysis Controller
  |       |
  |       +--> Input validation / normalization
  |       +--> URL Rule Analyzer
  |       +--> Message Rule Analyzer
  |       +--> Optional Threat Intelligence Adapter
  |       +--> Risk Aggregator
  |       +--> Explanation / Recommendations
  |
  +--> Incident Controller
  |       +--> Incident Service
  |       +--> Evidence Upload Service
  |       +--> Case Status / Audit Service
  |
  +--> Admin Controller (role protected)
          |
          v
     Application Services
          |
          +--> PDO repositories (prepared statements)
          +--> MySQL
          +--> Private evidence storage (outside public web root if possible)
```

## 3. Suggested directory structure

```text
AntiScam/
├── Docs/
│   ├── PRD.md
│   ├── ARCHITECTURE.md
│   ├── RULES.md
│   ├── DESIGN.md
│   ├── TASKS.md
│   ├── MEMORY.md
│   └── DECISION.md
├── public/                 # Web document root / front controller
│   ├── index.php
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── app/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Services/
│   │   ├── Analysis/
│   │   ├── Incidents/
│   │   └── Evidence/
│   ├── Repositories/
│   ├── Validators/
│   ├── Views/
│   └── Support/
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   ├── schema.sql
│   └── seeds/              # Only safe synthetic/demo data
├── storage/
│   ├── evidence/           # Must not be publicly served
│   └── logs/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Security/
├── .env.example
├── .gitignore
└── README.md
```

If Apache/XAMPP is configured to serve `C:\xampp\htdocs\AntiScam\` directly, do not assume that a `public/` folder is automatically the document root. Prefer configuring the virtual host/document root to `AntiScam/public`. If that is not feasible initially, use a carefully reviewed root entry point and explicitly deny web access to `app/`, `config/`, `database/`, `storage/`, and secret files. Do not put secrets or evidence in a publicly served directory.

## 4. Main modules

### Authentication
- Registration, login, logout.
- Password hashing with PHP password APIs.
- Session ID regeneration after login and privilege changes.
- Secure cookie settings appropriate to local/deployed mode.
- CSRF protection on state-changing requests.

### Analysis
- Input validation before rules run.
- Pure/deterministic rule functions where possible.
- Output consists of category, band/score, triggered rule IDs, explanations, recommendations, rule-set version, and optional threat-source status.
- Analysis never treats a negative feed result as proof that an item is safe.

### Threat intelligence
- Provider interface, for example `ThreatIntelProvider`.
- Adapter returns a normalized result: `match`, `no_match`, `unavailable`, or `not_checked`, plus source timestamp/reference where permitted.
- Do not invent a provider response.
- Do not send user content to an external provider without understanding data sharing and terms.
- Do not fetch arbitrary submitted URLs from the server.

### Incident and evidence
- Incident metadata stored in MySQL.
- Evidence files stored privately; database stores generated storage key, safe display name, MIME, size, hash if useful, uploader, and timestamps.
- Download goes through an authorization-checked controller.
- File content never becomes executable.

### Admin review
- Server-side role checks on every action.
- Status transition validation.
- Audit entries for status/permission changes.
- Admin review means review within ScamShield, not official law-enforcement investigation.

## 5. Proposed database entities

Exact column types and indexes should be finalized in `schema.sql` and tested against the installed MySQL/MariaDB version.

- `users`: id, display_name, email (unique), password_hash, role, status, created_at, updated_at.
- `analyses`: id, user_id nullable only if anonymous analysis is approved, input_type, protected/minimized input representation, risk_score, risk_band, rules_version, result_json, threat_status, created_at.
- `incidents`: id, public_reference (unique), user_id, incident_type, incident_at nullable, description, channel, status, assigned_admin_id nullable, created_at, updated_at.
- `evidence_files`: id, incident_id, uploaded_by, storage_key, original_name_safe, detected_mime, size_bytes, sha256 nullable, created_at.
- `incident_status_history`: id, incident_id, changed_by, old_status, new_status, reason, created_at.
- `audit_logs`: id, actor_user_id nullable, action, target_type, target_id, metadata_redacted, created_at.
- Optional later: `threat_checks` for provider responses and freshness metadata; avoid duplicate or excessive storage of submitted content.

## 6. Data-flow rules

1. Validate request size and content type.
2. Authenticate/authorize if the feature requires an account.
3. Analyze submitted content locally first.
4. Call external threat data only through a specific adapter and only when configured.
5. Aggregate signals without treating unknown as safe.
6. Store only what the product needs; do not retain full message content by default without a clear purpose.
7. Encode data when rendering HTML.
8. Authorize every report/evidence read on the server.
9. Record significant privileged changes without placing passwords, tokens, full message bodies, or file contents in logs.

## 7. Error handling and observability

- Show users a generic, useful error message.
- Write detailed diagnostics to a non-public log with secrets and sensitive content redacted.
- Fail closed for authorization and evidence access.
- If threat intelligence is unavailable, state that the external check could not be completed and return only the locally supported assessment.
- Do not silently convert API errors into “safe.”

## 8. Deployment boundary

Initial target: local XAMPP development. Public hosting is a later decision. Before any public deployment, verify HTTPS, PHP/MySQL versions, secret configuration, file storage, backups, rate limits, retention, error logging, and applicable legal/privacy requirements.

## External research references

- PHP PDO prepared statements: https://www.php.net/manual/en/pdo.prepared-statements.php
- PHP password hashing: https://www.php.net/manual/en/function.password-hash.php
- OWASP SQL Injection Prevention: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
- OWASP Session Management: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
- OWASP Secure Code Review: https://cheatsheetseries.owasp.org/cheatsheets/Secure_Code_Review_Cheat_Sheet.html
- OpenPhish feed details and terms: https://openphish.com/phishing_feeds.html and https://www.openphish.com/terms.html
- Indian National Cyber Crime Reporting Portal: https://www.cybercrime.gov.in/

These sources informed design choices but do not replace a security review, provider-term review, or tests of the actual implementation.
