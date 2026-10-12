---
inclusion: auto
---

# ScamShield — Technology Stack and Constraints

## Verified environment (inspected 2026-10-12)
- PHP: 8.2.12 (CLI, ZTS, x64)
- MariaDB: 10.4.32 (XAMPP)
- Apache: 2.4.58 (XAMPP, running)
- PHP extensions confirmed: PDO, pdo_mysql, openssl, mbstring, fileinfo, mysqlnd
- PHP GD extension: not yet confirmed — verify with `php -m | findstr gd` before any image processing
- OS: Windows, shell: PowerShell

## Stack
- Backend: PHP 8+ (no large framework for MVP)
- Database: MySQL/MariaDB via PDO prepared statements only
- Frontend: HTML, CSS, JavaScript, Bootstrap
- Version control: Git + GitHub
- Local server: XAMPP

## Hard constraints
- PDO prepared statements for all user-controlled values — no SQL concatenation.
- `password_hash()` / `password_verify()` for all passwords.
- Session ID regenerated after login and privilege changes.
- CSRF tokens on every state-changing form.
- Server-side authorization on every private resource — not just hidden UI.
- Output escaped for context (HTML, attributes, URLs).
- Secrets kept out of Git at all times.
- Do not fetch arbitrary user-submitted URLs from the PHP server.
- Evidence stored outside the public web root where possible.
- No paid AI API required for MVP.

## Unresolved
- Document-root strategy: XAMPP currently serves AntiScam/ root directly. A public/ subdirectory with virtual host configuration is planned but not yet decided or implemented.

Full architecture document:

#[[file:Docs/ARCHITECTURE.md]]
