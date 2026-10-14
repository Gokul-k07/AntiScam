# ScamShield — Project Memory for AI Coding Agents

- **Status:** v1.1 — updated after full MVP + VirusTotal integration
- **Last reviewed:** 2026-10-14

This file preserves durable project context across coding sessions. Update it when architecture, conventions, project state, or important constraints change.

---

## Project identity

- **Project name:** ScamShield (repository/folder: `AntiScam`)
- **Local project root:** `C:\xampp\htdocs\AntiScam\`
- **Documentation folder:** `C:\xampp\htdocs\AntiScam\Docs\`
- **Version control:** Git + GitHub (`https://github.com/Gokul-k07/AntiScam.git`)
- **Design tool:** Stitch (exported screens in `design/stitch/`)
- **Development environment:** XAMPP on Windows (local only; not publicly deployed)

---

## Verified environment (confirmed 2026-10-14)

| Component | Version | Status |
|---|---|---|
| PHP | 8.2.12 (CLI, ZTS, x64) | Confirmed |
| MariaDB | 10.4.32 (XAMPP) | Confirmed |
| Apache | 2.4.58 (XAMPP) | Confirmed |
| OS | Windows, shell: PowerShell | — |

PHP extensions confirmed present: PDO, pdo_mysql, openssl, mbstring, fileinfo, mysqlnd.
GD extension: **absent** — do not use image functions without enabling it first.

---

## Current project state (after all milestones — 2026-10-14)

All eight milestones are complete. The application is a fully implemented local MVP.

**Implemented and verified:**
- Repository, environment, configuration, document-root strategy (ADR-009)
- Database schema: 6 tables (`users`, `analyses`, `incidents`, `evidence_files`, `incident_status_history`, `audit_logs`)
- Authentication: registration, login, logout, CSRF, session security, role-based authorization
- Rule-based analysis: 8 URL rules, 7 SMS rules, 3 UPI rules, risk bands 0–100
- VirusTotal v3 integration: `VirusTotalProvider`, `VirusTotalResult`, `ReportCache`, `ThreatIntelProviderInterface`
- Incident reports: create, status workflow, reference generation (`SS-YYYY-XXXXXX`)
- Evidence: upload (jpg/jpeg/png/gif/pdf/txt/csv, 10 MB max), download, MIME validation
- Admin review: queue, detail, status change with reason, append-only audit log
- UI: Stitch-derived custom CSS (no Bootstrap CDN), three-file CSS system
- Tests: 5/5 smoke tests, 40/40 VirusTotalProviderTest pass (2026-10-14)

**Manual steps still required (not automated):**
- Database setup (`php database/setup.php` + `schema.sql` import)
- Virtual host configuration + hosts file entry
- `.env` population (DB credentials, STORAGE_EVIDENCE_PATH, optional VIRUSTOTAL_API_KEY)
- Admin role: `UPDATE users SET role='admin' WHERE email='...'`

**Not yet tested (requires live DB/network):**
- PDO repository queries against MariaDB in browser
- File upload/download end-to-end
- VirusTotal live API call (offline tests pass)
- Full browser request cycle (session, CSRF)

---

## Document-root strategy

Apache virtual host at `scamshield.local` serves `AntiScam/public/` only.
`app/`, `config/`, `database/`, `storage/`, `.env`, `Docs/` are never web-accessible.
Recorded as ADR-009 in `Docs/DECISION.md`.

---

## Technology conventions (enforce these)

- No Composer packages — PHP built-ins only
- No Bootstrap CDN — custom CSS in `public/assets/css/` (tokens → base → components)
- No curl — HTTPS via PHP stream wrappers (`stream_context_create` + `file_get_contents`)
- No framework routing — custom `Router` in `app/Support/Router.php`
- PDO prepared statements on every DB query — no SQL concatenation ever
- All output: `htmlspecialchars(ENT_QUOTES, 'UTF-8')`
- Passwords: `password_hash(PASSWORD_BCRYPT, ['cost'=>12])`
- Session regeneration: `session_regenerate_id(true)` after login

---

## VirusTotal integration (implemented — ADR-008 resolved)

| Key | Value |
|---|---|
| Endpoint | `GET https://www.virustotal.com/api/v3/urls/{id}` |
| Identifier | unpadded URL-safe Base64 of the URL |
| API key env var | `VIRUSTOTAL_API_KEY` (read from `$_ENV` only) |
| Enable env var | `THREAT_INTEL_PROVIDER=virustotal` |
| Cache TTL env var | `VIRUSTOTAL_CACHE_TTL_SECONDS` (default 3600) |
| Cache location | `storage/vt_cache/{sha256(url)}.json` |
| Public limits | 4 req/min, 500/day |
| SMS/UPI | **Never** sent to VT |
| Auto-submit | **No** — only existing reports retrieved |

Provider class: `app/Services/Analysis/VirusTotalProvider.php`
Cache class: `app/Services/Analysis/ReportCache.php`
Value object: `app/Services/Analysis/VirusTotalResult.php`
Interface: `app/Services/Analysis/ThreatIntelProviderInterface.php`

---

## Active routes

| Method | Path | Handler |
|---|---|---|
| GET | / | HomeController::index |
| GET | /health | HealthController::index |
| GET | /analyze | AnalysisController::index |
| POST | /analyze | AnalysisController::analyze |
| GET | /register | AuthController::showRegister |
| POST | /register | AuthController::register |
| GET | /login | AuthController::showLogin |
| POST | /login | AuthController::login |
| GET/POST | /logout | AuthController::logout |
| GET | /incidents | IncidentController::index |
| GET | /incidents/create | IncidentController::showCreate |
| POST | /incidents/create | IncidentController::create |
| GET | /incidents/{ref} | IncidentController::show |
| POST | /incidents/{ref}/evidence | IncidentController::uploadEvidence |
| GET | /evidence/{id}/download | IncidentController::downloadEvidence |
| GET | /admin/incidents | AdminController::queue |
| GET | /admin/incidents/{ref} | AdminController::detail |
| POST | /admin/incidents/{ref}/status | AdminController::changeStatus |
| GET | /admin/audit | AdminController::auditLog |

---

## Open decisions (pending)

- Anonymous analysis policy (user_id = NULL in analyses table — final policy not decided)
- Evidence retention and deletion policy
- Analysis history page `/analyses` (DB/repo ready; route not wired)
- Account settings / password change (not implemented)

---

## Change log

| Date | Change |
|---|---|
| 2026-10-12 | Initial documentation baseline; no application code |
| 2026-10-12 | Phase 1 complete: skeleton, routing, health endpoint. ADR-009 resolved. |
| 2026-10-13 | Milestones 2–7: full MVP with auth, analysis, incidents, evidence, admin, Stitch UI |
| 2026-10-14 | Milestone 8: VirusTotal integration. 40/40 tests pass. ADR-008 resolved. |
| 2026-10-14 | Full documentation audit: all Docs/, steering files, specs, README updated to v1.1 |
