# ScamShield — Task Board

- **Status:** Updated after Phase 1 implementation, v0.2
- **Last reviewed:** 2026-10-12

Legend: `[ ]` not started, `[x]` complete and verified, `[!]` blocked or requires a decision. Do not mark a task complete without evidence.

## Milestone 0 — Repository and environment
- [x] Inspect repository and record current `git status`. *(git status: clean on main; Docs/ untracked)*
- [x] Confirm XAMPP Apache and MySQL start successfully. *(both running as processes)*
- [x] Record PHP and MySQL/MariaDB versions. *(PHP 8.2.12, MariaDB 10.4.32, Apache 2.4.58)*
- [x] Confirm repository root and `Docs/` path. *(`C:\xampp\htdocs\AntiScam\`, `Docs/` present)*
- [x] Add/review `.gitignore` for secrets, uploads, logs, caches and local configuration. *(`.gitignore` created and verified)*
- [x] Create README with verified setup instructions only. *(README.md updated; unverified steps labelled)*
- [x] Decide whether Apache will serve `public/` as document root or use a reviewed temporary local arrangement. *(Option A: virtual host — see ADR-009)*

## Milestone 1 — Application skeleton
- [x] Create directory structure from `ARCHITECTURE.md`. *(all dirs created with .gitkeep files)*
- [x] Add front controller/router and basic error handling. *(`public/index.php`, `app/Support/Router.php`, `app/Support/ErrorHandler.php` — HTTP 404 verified)*
- [x] Add configuration loader; never commit real secrets. *(`config/env.php`, `config/app.php`, `config/database.php` — `.env` gitignored)*
- [x] Verify local home page and a not-found route. *(HTTP 200 home page verified; HTTP 404 for unknown routes verified)*
- [x] Add initial smoke test/checklist. *(`/health` endpoint returns JSON `{"status":"ok"}` with all checks passing; DB SELECT 1 verified)*

## Milestone 2 — Database
- [ ] Finalize schema for users, analyses, incidents, evidence metadata, status history and audit logs.
- [ ] Create database and least-privilege development credentials where practical.
- [ ] Connect with PDO and exception mode.
- [ ] Test successful connection and expected failure behavior.
- [ ] Add indexes, foreign keys and appropriate unique constraints.
- [ ] Add only synthetic seed data.

## Milestone 3 — Authentication and authorization
- [ ] Registration with validation and unique email handling.
- [ ] Secure login/logout and session regeneration.
- [ ] Password hashing and verification.
- [ ] CSRF protection for state-changing forms.
- [ ] Server-side role and object-ownership checks.
- [ ] Tests for unauthorized access and session behavior.

## Milestone 4 — Rule-based analysis
- [ ] Define URL rules and version identifier.
- [ ] Define message rules and version identifier.
- [ ] Define documented score aggregation and risk bands.
- [ ] Return triggered rule IDs and explanations.
- [ ] Add synthetic positive, negative and ambiguous test examples.
- [ ] Test malformed inputs, Unicode, empty input and length limits.
- [ ] Ensure “no match” never means “safe.”
- [ ] Ensure server does not fetch arbitrary submitted URLs.
- [ ] Add analysis result UI and safety copy.
- [ ] Persist analysis history only according to the privacy decision.

## Milestone 5 — Incident reports and evidence
- [ ] Finalize report fields and retention policy.
- [ ] Create incident form and server-side validation.
- [ ] Generate unique non-sensitive report reference.
- [ ] Implement valid status transitions.
- [ ] Add upload allowlist, size limit, MIME/signature validation and random filenames.
- [ ] Store evidence outside public web root where possible.
- [ ] Implement authorization-checked downloads.
- [ ] Test cross-user access, path traversal, oversized files and invalid types.
- [ ] Add status history and audit records.

## Milestone 6 — Admin workflow
- [ ] Implement admin role protection on every route/action.
- [ ] Build report queue and detail page.
- [ ] Add status changes with reason.
- [ ] Record meaningful privileged actions.
- [ ] Test ordinary-user denial and audit completeness.

## Milestone 7 — External data and optional AI
- [ ] Evaluate threat provider coverage, current docs, terms, limits and privacy.
- [ ] Implement provider interface and explicit unavailable/error states.
- [ ] Test match, no-match, unavailable and timeout behavior.
- [ ] Document external data sharing before enabling it.
- [ ] Only add an AI provider/local model after cost, privacy and quality are evaluated.
- [ ] Compare performance against a labeled synthetic/curated test set; do not claim accuracy without measurement.

## Milestone 8 — Release readiness
- [ ] Run unit, integration, manual and security-oriented tests.
- [ ] Review logs for secrets and unnecessary personal data.
- [ ] Verify backup/restore and deletion behavior.
- [ ] Review privacy notice and limitations.
- [ ] Confirm official-reporting guidance is accurate and current.
- [ ] Create screenshots/demo using synthetic data.
- [ ] Update README and project documentation.
- [ ] Decide whether deployment is appropriate; do not publish real incident data.

## Blockers / pending decisions
- [!] Anonymous analysis vs login-required analysis.
- [!] Evidence file allowlist and size limits.
- [!] Retention/deletion policy.
- [!] Threat-intelligence provider and terms.
- [x] Apache document-root approach. *(resolved: Option A — ADR-009 in DECISION.md)*
- [x] Exact installed PHP/MySQL versions. *(resolved: PHP 8.2.12, MariaDB 10.4.32)*

## Pending manual step
- [ ] Add `127.0.0.1   scamshield.local` to `C:\Windows\System32\drivers\etc\hosts` with Administrator elevation so the site resolves in a browser at `http://scamshield.local/`.

## Current next action
Milestone 2 — finalize and implement the database schema (users, analyses, incidents, evidence_files, incident_status_history, audit_logs). Resolve open decisions (anonymous analysis, evidence policy, retention) before designing the schema.
