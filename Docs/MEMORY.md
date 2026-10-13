# ScamShield — Project Memory for AI Agents

- **Status:** Updated after Phase 1 implementation, v0.2
- **Last reviewed:** 2026-10-12

This file preserves durable project context across coding sessions. It is not a diary of every edit. Update it when architecture, conventions, project state, or important constraints change.

## Project identity
- Project name: ScamShield (repository/folder name: `AntiScam`).
- Local project root: `C:\xampp\htdocs\AntiScam\`.
- Documentation folder: `C:\xampp\htdocs\AntiScam\Docs\`.
- Main development environment: Antigravity.
- Local stack: XAMPP, PHP 8+, MySQL Community Edition, HTML/CSS/JavaScript, Bootstrap.
- Version control: Git and GitHub.
- Budget target: prioritize free tools and avoid mandatory paid AI APIs.

## Product definition
ScamShield is a digital-safety assistant that assesses potential scam indicators, explains them, helps users organize incident evidence, and guides them toward appropriate reporting. It does not prove guilt, replace police, or guarantee detection.

## Initial scope
- URL analysis using local deterministic rules.
- Message analysis using local deterministic rules.
- Explainable preliminary risk result.
- User accounts and analysis history.
- Incident reports, private evidence storage, case status tracking.
- Admin review workflow and audit log.
- Optional threat-intelligence adapter after current terms and behavior are verified.

## Technical principles
- Prefer a small modular PHP application over unnecessary complexity.
- Use PDO prepared statements, password hashing, session security, CSRF protections, contextual output escaping, and server-side authorization.
- Keep secrets out of Git.
- Keep evidence outside the public web root and authorize every download.
- Do not fetch arbitrary user-submitted URLs from the backend.
- “No threat-feed match” means only no match in the source checked, not safe.
- Treat rules as heuristics; expose the triggered reasons and limitations.
- Use synthetic data for tests and demos.

## AI coding workflow
1. Inspect files and Git state before editing.
2. Plan small changes.
3. Do not reset or overwrite user work.
4. Test changes and report exact results.
5. Do not commit/push unless asked.
6. Update docs for meaningful changes, not each minor edit.
7. Mark tasks complete only when verified.
8. If uncertain, state what evidence/action is needed instead of guessing.

## Verified environment (inspected and confirmed 2026-10-12)

| Component | Version | Status |
|---|---|---|
| PHP | 8.2.12 (CLI, ZTS, x64) | Running |
| MariaDB | 10.4.32 (XAMPP) | Running (mysqld process) |
| Apache | 2.4.58 (XAMPP) | Running (2 httpd processes) |
| OS | Windows, PowerShell | — |

PHP extensions confirmed present: PDO, pdo_mysql, openssl, mbstring, fileinfo, mysqlnd.
GD extension: **absent** — do not use image functions without first enabling the extension.

## Current project state (after Phase 1 — 2026-10-12)

The application skeleton has been implemented and verified:

- Repository: `https://github.com/Gokul-k07/AntiScam.git`, branch `main`.
- All created files are untracked (not yet committed) — commit/push is at the user's discretion.
- `.env` is gitignored; `.env.example` is committed with placeholders only.
- Local `.env` uses XAMPP root credentials (no password) for development.

**Verified working (HTTP tests confirmed):**
- `http://scamshield.local/` → 200 OK, ScamShield landing page with Bootstrap 5, safety disclaimer, feature cards.
- `http://scamshield.local/health` → 200 OK, JSON `{"status":"ok"}` with all checks passing including DB.
- `http://scamshield.local/nonexistent` → 404 with custom page.
- `http://scamshield.local/.env` → 403 Forbidden.
- `scamshield` database created in MariaDB via `database/setup.php`.
- PDO connection: `SELECT 1` passes; MariaDB 10.4.32 confirmed.
- All 18 PHP files: syntax-checked clean with `php -l`.

**Pending — requires manual step:**
- `scamshield.local` hosts file entry (`127.0.0.1 scamshield.local`) needs to be added to
  `C:\Windows\System32\drivers\etc\hosts` with Administrator elevation.
  Until then, use `curl --resolve` or test via direct IP with a Host header for verification.
  The vhost config in `httpd-vhosts.conf` is in place and working — only DNS resolution is missing.

## Document-root strategy
Option A implemented: Apache virtual host at `scamshield.local` serves `AntiScam/public/` only.
- `app/`, `config/`, `database/`, `storage/`, `.env`, `Docs/` are never web-accessible.
- Recorded as ADR-009 in `Docs/DECISION.md`.

## Important open decisions (still unresolved)
- Anonymous analysis vs login-required analysis.
- Evidence file allowlist and size limits.
- Data retention and deletion policy.
- Threat-intelligence provider and terms (ADR-008 pending).

## Change log
- 2026-10-12: Created initial documentation baseline. No application implementation verified.
- 2026-10-12: Phase 1 complete — skeleton implemented and verified. See ADR-009.
