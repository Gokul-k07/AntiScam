---
inclusion: auto
---

# ScamShield — Technology Stack and Constraints

## Verified environment (last confirmed 2026-10-14)

| Component | Version | Status |
|---|---|---|
| PHP | 8.2.12 (CLI, ZTS, x64) | Confirmed |
| MariaDB | 10.4.32 (XAMPP) | Confirmed |
| Apache | 2.4.58 (XAMPP) | Confirmed |
| OS | Windows, shell: PowerShell | — |

PHP extensions confirmed present: PDO, pdo_mysql, openssl, mbstring, fileinfo, mysqlnd.
PHP GD extension: **absent** — do not use image functions without enabling the extension first.

## Stack

- Backend: PHP 8.2 (no large framework — custom MVC skeleton)
- Database: MariaDB 10.4 via PDO prepared statements only
- Frontend: custom HTML/CSS (no Bootstrap CDN), vanilla JavaScript
- CSS: three-file system — `tokens.css`, `base.css`, `components.css`
- Fonts: Space Grotesk + IBM Plex Sans + IBM Plex Mono via Google Fonts `@import`
- Version control: Git + GitHub
- Local server: XAMPP (Apache 2.4, MariaDB 10.4)

## Document-root strategy (resolved — ADR-009)

Apache virtual host serves `C:/xampp/htdocs/AntiScam/public/` as the document root at `http://scamshield.local/`.
`app/`, `config/`, `database/`, `storage/`, `.env`, and `Docs/` are not web-accessible.

## Dependency notes

No Composer packages are used. All dependencies are PHP built-ins:
- PDO + pdo_mysql for database access
- openssl (TLS) for VirusTotal HTTPS calls via `file_get_contents`/stream_context
- fileinfo for server-side MIME detection on uploads
- mbstring for multi-byte string operations

## Hard constraints

- PDO prepared statements for ALL user-controlled values — no SQL concatenation.
- `password_hash()` / `password_verify()` for all passwords. Cost factor: 12.
- Session ID regenerated after login and privilege changes.
- CSRF tokens on every state-changing form (POST). Validated server-side.
- Server-side authorization on every private resource — not just hidden UI controls.
- Output escaped for context: `htmlspecialchars()` for HTML, proper encoding elsewhere.
- Secrets kept out of Git. `.env` is gitignored. `.env.example` contains placeholders only.
- Do NOT fetch arbitrary user-submitted URLs from the PHP server (SSRF Rule §10).
- Evidence stored outside the public web root (`storage/evidence/`).
- No paid AI API required.

## VirusTotal integration (implemented — Milestone 8)

- `THREAT_INTEL_PROVIDER=virustotal` enables the integration.
- `VIRUSTOTAL_API_KEY=<key>` is required (read from `$_ENV` only, never logged).
- `VIRUSTOTAL_CACHE_TTL_SECONDS=3600` is optional (default 1 hour).
- Public API limits: 4 requests/minute, 500/day.
- Only URL analyses trigger VT lookups. SMS/UPI text is never sent to VT.
- Provider is fully optional: heuristic analysis works independently if not configured.

Full architecture document:

#[[file:Docs/ARCHITECTURE.md]]
