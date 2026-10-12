# ScamShield — Project Memory for AI Agents

- **Status:** Initial baseline, v0.1
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

## Current known state
- User says the GitHub repository has been created.
- User says XAMPP is installed.
- User says the project has not been started.
- User intends to use Antigravity and other AI tools.
- Exact repository URL, current files, Git state, PHP version, database version, and XAMPP service status have not yet been independently inspected.

## Important unknowns
- Is the GitHub repository empty, or does it contain a README/license/gitignore?
- Has the repository already been cloned to the stated local path?
- Are Apache and MySQL currently running?
- What exact PHP and MySQL/MariaDB versions are installed?
- Which document-root strategy will be used?
- What evidence file types, data retention, and anonymous-analysis policy will be selected?

## Change log
- 2026-10-12: Created initial documentation baseline. No application implementation has been verified.
