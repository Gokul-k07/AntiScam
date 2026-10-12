# ScamShield

A web-based digital-safety assistant that helps users assess suspicious URLs and messages, organize incident evidence, and find appropriate official reporting routes.

> **Status:** Pre-development. Repository and documentation baseline established. No application code has been written yet.

## Project documents

Full requirements, architecture, design, security rules, task board, and decision records are in [`Docs/`](Docs/):

| Document | Purpose |
|---|---|
| [PRD.md](Docs/PRD.md) | Product requirements, user roles, MVP scope, open decisions |
| [ARCHITECTURE.md](Docs/ARCHITECTURE.md) | Logical architecture, directory structure, database schema |
| [DESIGN.md](Docs/DESIGN.md) | UI/UX design principles and information architecture |
| [RULES.md](Docs/RULES.md) | Binding security and engineering rules |
| [TASKS.md](Docs/TASKS.md) | Milestone task board |
| [DECISION.md](Docs/DECISION.md) | Architecture decision records |
| [MEMORY.md](Docs/MEMORY.md) | Durable project context for AI coding agents |

## Verified environment (inspected 2026-10-12)

| Component | Version |
|---|---|
| PHP | 8.2.12 (CLI, ZTS, x64) |
| MariaDB | 10.4.32 (XAMPP) |
| Apache | 2.4.58 (XAMPP) |
| OS | Windows (XAMPP local development) |

PHP extensions confirmed: PDO, pdo_mysql, openssl, mbstring, fileinfo, mysqlnd.
GD extension status: not yet verified — check with `php -m` before any image-processing work.

## Local setup (steps not yet fully tested — verify each step)

> These steps describe the intended setup. They have not been end-to-end verified. Follow them carefully and report any discrepancies.

1. **Prerequisites:** Install [XAMPP](https://www.apachefriends.org/) with PHP 8.2+ and MariaDB. Start Apache and MySQL from the XAMPP Control Panel.

2. **Clone the repository:**
   ```
   git clone https://github.com/Gokul-k07/AntiScam.git C:\xampp\htdocs\AntiScam
   cd C:\xampp\htdocs\AntiScam
   ```

3. **Copy environment file:**
   ```
   copy .env.example .env
   ```
   Edit `.env` with your local database credentials. **Never commit `.env`.**

4. **Create the database** *(schema not yet written — see Milestone 2 in [TASKS.md](Docs/TASKS.md))*:
   ```sql
   CREATE DATABASE scamshield CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'scamshield_user'@'localhost' IDENTIFIED BY 'your_password';
   GRANT SELECT, INSERT, UPDATE, DELETE ON scamshield.* TO 'scamshield_user'@'localhost';
   ```

5. **Document-root strategy:** *(unresolved — see [DECISION.md](Docs/DECISION.md) ADR-009 pending)*
   The intended web root is `AntiScam/public/`. This requires configuring an Apache virtual host. Until this decision is finalised, do not place application files.

6. **Start the application:** *(no application code exists yet — see [TASKS.md](Docs/TASKS.md))*

## Security

This project follows strict security rules. See [RULES.md](Docs/RULES.md) for the complete list. Key points:
- All database queries use PDO prepared statements.
- Passwords are hashed with `password_hash()`.
- Evidence files are stored outside the web root.
- `.env` is gitignored — never commit credentials.

## Open decisions

Several product and architecture decisions are unresolved and must be made before the relevant features can be implemented. See [DECISION.md](Docs/DECISION.md) and the "Blockers" section of [TASKS.md](Docs/TASKS.md).

## License

*To be determined.*
