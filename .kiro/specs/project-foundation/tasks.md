# Tasks: Project Foundation

## Overview
Milestone 0 (repository/environment) and Milestone 1 (application skeleton) implementation tasks. Each task corresponds to requirements and design in this spec.

Complete tasks in order. Do not mark a task complete without evidence (exact command output or verified file existence).

## Task 1: Confirm and Record Environment

- [ ] Run `php --version` and record exact output in `Docs/MEMORY.md`.
- [ ] Run `php -m` and confirm PDO, pdo_mysql, openssl, mbstring, fileinfo are all present. Record results.
- [ ] Run `php -m | findstr gd` (Windows) and record whether GD is present or absent.
- [ ] Confirm MariaDB version: `C:\xampp\mysql\bin\mysql.exe --version`. Record output.
- [ ] Confirm Apache version: `C:\xampp\apache\bin\httpd.exe -v`. Record output.
- [ ] Confirm Apache and MySQL processes are running (XAMPP Control Panel or `Get-Process`).
- [ ] Record all findings in `Docs/MEMORY.md` under "Verified environment."

**Acceptance:** All versions and extension statuses recorded in MEMORY.md with evidence. (PHP 8.2.12, MariaDB 10.4.32, Apache 2.4.58 already confirmed — document them formally.)

## Task 2: Review and Finalize .gitignore

- [ ] Verify `.gitignore` exists and covers: `.env`, `storage/evidence/`, `storage/logs/`, `vendor/`, IDE folders, OS files.
- [ ] Confirm `.env.example` is NOT gitignored.
- [ ] Run `git status` and verify no secret or credential file is untracked or staged.
- [ ] If any gap is found, update `.gitignore` and re-verify.

**Acceptance:** `git status` shows no secrets; `.env.example` is trackable; `storage/` subdirs are ignored.

## Task 3: Verify .env.example

- [ ] Confirm `.env.example` exists with placeholder values only.
- [ ] Confirm no real credentials, API keys, or passwords appear in `.env.example`.
- [ ] Copy `.env.example` to `.env` locally and fill in real local development values (do not commit `.env`).

**Acceptance:** `.env.example` committed with placeholders; local `.env` exists but is gitignored.

## Task 4: Decide Document-Root Strategy

- [ ] Review XAMPP virtual host configuration options.
- [ ] Choose Option A (virtual host serving `public/`) or Option B (root serving with deny rules).
- [ ] Record the decision as ADR-009 in `Docs/DECISION.md`.
- [ ] If Option A: configure the XAMPP virtual host and verify `http://scamshield.local` or similar resolves.
- [ ] If Option B: document the temporary arrangement and migration plan.

**Acceptance:** Decision recorded in DECISION.md; chosen arrangement verified to work before any PHP files are placed.

**Blocker:** Do not start Task 5 until this task is complete.

## Task 5: Create Directory Skeleton

*Blocked by Task 4.*

- [ ] Create `public/` (if Option A or B decided).
- [ ] Create `app/Controllers/`, `app/Middleware/`, `app/Services/Analysis/`, `app/Services/Incidents/`, `app/Services/Evidence/`, `app/Repositories/`, `app/Validators/`, `app/Views/`, `app/Support/`.
- [ ] Create `config/`, `database/`, `storage/evidence/`, `storage/logs/`, `tests/Unit/`, `tests/Integration/`, `tests/Security/`.
- [ ] Add `.gitkeep` files to empty directories that must be tracked.
- [ ] Verify `storage/evidence/` and `storage/logs/` appear in `.gitignore`.
- [ ] If Option B: add `.htaccess` deny rules to `app/`, `config/`, `database/`, `storage/`.

**Acceptance:** Directory structure matches `Docs/ARCHITECTURE.md` §3; sensitive dirs are inaccessible from the web.

## Task 6: Create Configuration Loader

*Blocked by Task 5.*

- [ ] Create `config/app.php` — reads APP_ENV, APP_DEBUG, APP_KEY from environment.
- [ ] Create `config/database.php` — reads DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD from environment.
- [ ] Create a minimal `.env` loader bootstrap (reads `.env` file into `$_ENV` if not already set).
- [ ] Verify that missing required values throw a clear exception — no hardcoded credentials fallback.
- [ ] Verify credentials do not appear in any output.

**Acceptance:** Config files exist; local test shows values loaded from `.env`; missing value throws exception.

## Task 7: Create Front Controller

*Blocked by Task 4 (document-root strategy).*

- [ ] Create `public/index.php` (or root `index.php` if Option B).
- [ ] Bootstrap: load autoloader/config, set error handler, set exception handler.
- [ ] Add minimal routing: known routes dispatch to stubs; unknown routes return HTTP 404 with generic message.
- [ ] Verify `display_errors` is off or set from config — never expose stack traces to users.
- [ ] Test: load home route in browser → expected stub response; load unknown route → 404 generic message.

**Acceptance:** Front controller handles requests; 404 tested; no stack traces visible to browser.

## Task 8: Verify PDO Database Connection

*Blocked by Tasks 4, 5, 6.*

- [ ] Create local MariaDB database `scamshield` and a development user with limited privileges.
- [ ] Update local `.env` with real database credentials (do not commit).
- [ ] Add PDO connection initialization to the bootstrap or a `Support/Database.php` file.
- [ ] Use `PDO::ERRMODE_EXCEPTION`, `PDO::FETCH_ASSOC`, `PDO::ATTR_EMULATE_PREPARES => false`.
- [ ] Write a smoke test: execute `SELECT 1` and verify it succeeds.
- [ ] Test connection failure: use wrong credentials; verify generic error shown, details logged.

**Acceptance:** Smoke test passes with exact output recorded; failure case produces generic user message only.

## Task 9: Update Project Documentation

- [ ] Update `Docs/MEMORY.md` with verified environment details and current project state.
- [ ] Update `Docs/TASKS.md` Milestone 0 and Milestone 1 items to reflect actual completion status.
- [ ] If document-root decision was made in Task 4, add ADR-009 to `Docs/DECISION.md`.
- [ ] Update `README.md` with verified setup steps (only what has been confirmed to work).

**Acceptance:** All documentation reflects actual verified state — no aspirational or unverified claims.

## Open Decisions (Blocking Later Milestones)

These must be resolved before the features they block can be designed or implemented:

| Decision | Blocking | Recorded in |
|---|---|---|
| Document-root strategy | Tasks 5, 7 | DECISION.md (ADR-009 needed) |
| Anonymous analysis vs login-required | Analyses schema, FR-01 | DECISION.md |
| Evidence file allowlist and size limits | FR-06, Milestone 5 | DECISION.md |
| Data retention and deletion policy | Schema, FR-06, FR-07 | DECISION.md |
| Threat-intelligence provider and terms | FR-01 optional, Milestone 7 | DECISION.md (ADR-008 follow-up) |
