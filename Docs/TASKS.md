# ScamShield — Task Board

- **Status:** MVP backend + UI complete (v1.0), 2026-10-13
- **Legend:** `[x]` complete and verified · `[ ]` not started · `[!]` blocked / needs decision

---

## Milestone 0 — Repository and environment
- [x] Inspect repository and record git status
- [x] Confirm XAMPP Apache and MySQL start successfully
- [x] Record PHP / MariaDB versions *(PHP 8.2.12, MariaDB 10.4.32, Apache 2.4.58)*
- [x] Confirm repository root and Docs/ path
- [x] .gitignore covers secrets, uploads, logs
- [x] README with verified setup instructions
- [x] Decide document-root strategy *(ADR-009: public/ via virtual host)*

## Milestone 1 — Application skeleton
- [x] Directory structure from ARCHITECTURE.md
- [x] Front controller, router, error handling (`public/index.php`, `Router.php`, `ErrorHandler.php`)
- [x] Configuration loader; secrets never committed (`config/env.php`, `config/app.php`)
- [x] Home page (HTTP 200) and 404 route verified
- [x] Health endpoint (`/health` returns JSON `{"status":"ok"}`)

## Milestone 2 — Database schema
- [x] `database/schema.sql` — tables: `users`, `analyses`, `incidents`, `evidence_files`, `incident_status_history`, `audit_logs`
- [x] PDO singleton with ERRMODE_EXCEPTION and emulated prepares disabled (`app/Support/Database.php`)
- [x] Least-privilege DB user documented in `.env.example`
- [ ] Schema applied to local MariaDB instance *(manual step — run `php database/setup.php` then `SOURCE schema.sql`)*

## Milestone 3 — Authentication and authorization
- [x] `app/Support/Session.php` — secure session bootstrap (HttpOnly, SameSite=Lax, custom name)
- [x] `app/Support/Csrf.php` — per-session CSRF token with rotation on use
- [x] `app/Middleware/AuthMiddleware.php` — requireAuth() and requireAdmin() helpers
- [x] `app/Controllers/AuthController.php` — register, login, logout
- [x] `app/Repositories/UserRepository.php` — findByEmail, findById, create, emailExists
- [x] Password hashing with `password_hash(PASSWORD_BCRYPT, cost:12)`; verified with `password_verify()`
- [x] Session regeneration after login (`session_regenerate_id(true)`)
- [x] CSRF validated on every state-changing POST
- [x] Auth views: `auth/register.php`, `auth/login.php`
- [x] Nav is auth-aware: shows login/register or username + logout + admin link
- [x] Audit entries written for register, login, login_failed, logout
- [ ] Account settings / password change *(not yet implemented)*

## Milestone 4 — Rule-based URL and message analysis
- [x] `app/Services/Analysis/UrlAnalysisService.php` — deterministic heuristic engine
  - URL rules: HTTP (no TLS), raw IP host, PSB brand typosquat, utility keyword, suspicious TLD, harvest query param, APK delivery, hyphen stacking
  - SMS rules: coercive urgency, PSB impersonation, KYC/credential harvest, embedded URL, DLT non-compliance, digital arrest, job scam
  - UPI rules: PIN-to-receive trap, VPA brand impersonation, coercive urgency
- [x] Risk bands: low (0–24), moderate (25–54), high (55–84), critical (85–100)
- [x] Every finding returns: trigger ID, tag, title, explanation, weight, severity, regulatory reference
- [x] "No match" never declared safe — explicitly stated in limitations-box partial
- [x] Server does NOT fetch submitted URLs (Rule §4.1 — airgap confirmed)
- [x] `app/Repositories/AnalysisRepository.php` — persists results; input capped at 500 chars excerpt
- [x] `app/Controllers/AnalysisController.php` — GET /analyze (form), POST /analyze (run + persist + render)
- [x] Smoke tested: 5 cases verified (APK→critical/92, empty→low/0, SBI SMS→high/83, UPI trap→moderate/50, official SBI URL→low/0)
- [x] Result page wired to real service output — all Stitch sample text replaced with dynamic data
- [x] "Log as Incident" button on result page links to `/incidents/create?from_analysis={id}` when logged in
- [ ] Analysis history page `/analyses` — DB and repository ready; route not yet wired

## Milestone 5 — Incident reports and evidence
- [x] `app/Services/Incidents/IncidentService.php` — create, validateCreate, changeStatus, allowedTransitions, generateReference
- [x] `app/Repositories/IncidentRepository.php` — create, findById, findByReference, listForUser, listAll, updateStatus, appendHistory, getHistory
- [x] `app/Controllers/IncidentController.php` — index, showCreate, create, show, uploadEvidence, downloadEvidence
- [x] Unique public reference format: `SS-YYYY-XXXXXX`
- [x] Status flow: submitted → under_review → info_needed ↔ under_review → closed
- [x] `app/Services/Incidents/EvidenceService.php` — upload with allowlist (JPG/PNG/GIF/PDF/TXT/CSV), server-side MIME detection, random storage keys, 10 MB limit, path-traversal prevention, SHA-256 hash
- [x] `app/Repositories/EvidenceRepository.php`
- [x] Evidence stored via `STORAGE_EVIDENCE_PATH` env var (defaults to `storage/evidence/` if not set)
- [x] Every download authorization-checked against incident ownership
- [x] Incident views: `incidents/index.php`, `incidents/create.php`, `incidents/show.php`
- [x] Cross-user access prevented: ownership checked server-side before every read/download
- [x] Incident disclaimer ("not an official complaint") present in every incident view

## Milestone 6 — Admin workflow
- [x] `app/Controllers/AdminController.php` — queue, detail, changeStatus, auditLog
- [x] `app/Repositories/AuditRepository.php` — log, listRecent, listForTarget
- [x] Every admin route calls `AuthMiddleware::requireAdmin()` (server-side role check)
- [x] Status changes require reason; history row written + audit entry
- [x] Admin views: `admin/queue.php` (status filter), `admin/detail.php` (CSRF status change), `admin/audit.php`
- [x] Audit log is append-only at application layer; no UPDATE/DELETE on `audit_logs`
- [ ] Evidence viewer for admin (can use existing incident detail page; direct evidence list not separately wired for admin yet)

## Milestone 7 — Stitch UI conversion
- [x] Stitch screens rebuilt as semantic PHP views (no Tailwind CDN, no inline styles, no Bootstrap)
- [x] `public/assets/css/tokens.css` — full design token CSS custom properties
- [x] `public/assets/css/base.css` — reset, typography, focus ring, grid, utilities
- [x] `public/assets/css/components.css` — all component styles
- [x] All three Stitch screens converted and wired to real backend data
- [x] Risk level conveyed by text + shape icon + position (accessibility)
- [x] 39/39 PHP files pass `php -l`

## Milestone 8 — Threat intelligence
- [!] Provider not yet selected — see DECISION.md ADR-008 (unresolved)
- [ ] ThreatIntelProvider interface
- [ ] Adapter returning normalized: match / no_match / unavailable / not_checked

## Blockers / open decisions
- [!] Anonymous analysis — currently persisted with `user_id = NULL`; decision on whether to allow unauthenticated analysis is pending
- [!] Evidence retention / deletion policy — not yet defined
- [!] Threat-intelligence provider and terms — ADR-008 unresolved
- [x] Apache document-root — ADR-009 resolved: serve `public/` via virtual host
- [x] PHP / MariaDB versions confirmed

## Manual setup steps required before first browser test
1. Create database and user:
   ```sql
   CREATE DATABASE scamshield CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'scamshield_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT SELECT,INSERT,UPDATE,DELETE ON scamshield.* TO 'scamshield_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
2. Apply schema: `mysql -u scamshield_user -p scamshield < database/schema.sql`
3. Copy `.env.example` to `.env` and fill in `DB_*` values and `STORAGE_EVIDENCE_PATH`
4. Configure XAMPP virtual host to serve `C:\xampp\htdocs\AntiScam\public\` as document root
5. Add `127.0.0.1 scamshield.local` to `C:\Windows\System32\drivers\etc\hosts` (run as Administrator)
6. Restart Apache

## What was NOT tested (requires live DB)
- PDO connection and all repository queries against MariaDB
- File upload / download through the evidence service
- Full HTTP request cycle (session, CSRF, redirect-after-post) in a browser
- Admin role assignment (must be set directly in DB: `UPDATE users SET role='admin' WHERE email='...'`)
