# Implementation Plan

## Overview
Milestone 0 (repository/environment) and Milestone 1 (application skeleton) implementation tasks. Each task corresponds to requirements and design in this spec.

Complete tasks in order. Do not mark a task complete without evidence (exact command output or verified file existence).

## Task 1: Confirm and Record Environment

- [x] Run `php --version` and record exact output in `Docs/MEMORY.md`. *(PHP 8.2.12)*
- [x] Run `php -m` and confirm PDO, pdo_mysql, openssl, mbstring, fileinfo are all present. Record results. *(all confirmed)*
- [x] Run `php -m | findstr gd` (Windows) and record whether GD is present or absent. *(GD absent — noted in MEMORY.md)*
- [x] Confirm MariaDB version: `C:\xampp\mysql\bin\mysql.exe --version`. Record output. *(10.4.32-MariaDB)*
- [x] Confirm Apache version: `C:\xampp\apache\bin\httpd.exe -v`. Record output. *(2.4.58)*
- [x] Confirm Apache and MySQL processes are running (XAMPP Control Panel or `Get-Process`). *(both confirmed running)*
- [x] Record all findings in `Docs/MEMORY.md` under "Verified environment." *(done)*

**Acceptance:** PASSED — all versions and extension statuses recorded in MEMORY.md with evidence.

## Task 2: Review and Finalize .gitignore

- [x] Verify `.gitignore` exists and covers: `.env`, `storage/evidence/`, `storage/logs/`, `vendor/`, IDE folders, OS files. *(verified)*
- [x] Confirm `.env.example` is NOT gitignored. *(`git check-ignore` confirms not ignored)*
- [x] Run `git status` and verify no secret or credential file is untracked or staged. *(clean)*
- [x] If any gap is found, update `.gitignore` and re-verify. *(no gaps found)*

**Acceptance:** PASSED.

## Task 3: Verify .env.example

- [x] Confirm `.env.example` exists with placeholder values only. *(confirmed)*
- [x] Confirm no real credentials, API keys, or passwords appear in `.env.example`. *(confirmed — all values are `changeme_*` or `none`)*
- [x] Copy `.env.example` to `.env` locally and fill in real local development values (do not commit `.env`). *(`.env` created; gitignored)*

**Acceptance:** PASSED.

## Task 4: Decide Document-Root Strategy

- [x] Review XAMPP virtual host configuration options. *(done — `httpd-vhosts.conf` inspected)*
- [x] Choose Option A (virtual host serving `public/`) or Option B (root serving with deny rules). *(Option A chosen)*
- [x] Record the decision as ADR-009 in `Docs/DECISION.md`. *(done)*
- [x] If Option A: configure the XAMPP virtual host and verify `http://scamshield.local` or similar resolves. *(vhost config appended; verified via `curl --resolve`; hosts file entry pending manual admin step)*

**Acceptance:** PASSED (vhost serving correct content). One manual step remains: add `127.0.0.1 scamshield.local` to Windows hosts file with Administrator elevation.

## Task 5: Create Directory Skeleton

- [x] Create `public/` directory.
- [x] Create all `app/` subdirectories.
- [x] Create `config/`, `database/`, `storage/evidence/`, `storage/logs/`, `tests/` subdirectories.
- [x] Add `.gitkeep` files to empty tracked directories.
- [x] Verify `storage/evidence/` and `storage/logs/` appear in `.gitignore`. *(confirmed)*

**Acceptance:** PASSED — structure matches `Docs/ARCHITECTURE.md` §3.

## Task 6: Create Configuration Loader

- [x] Create `config/app.php`. *(reads APP_ENV, APP_DEBUG, APP_KEY from environment)*
- [x] Create `config/database.php`. *(reads DB_* from environment; throws on missing DB_DATABASE)*
- [x] Create `config/env.php` — minimal `.env` loader. *(reads .env into $_ENV; throws RuntimeException if .env missing)*
- [x] Verify that missing required values throw a clear exception — no hardcoded credentials fallback. *(database.php throws RuntimeException if DB_DATABASE is empty)*
- [x] Verify credentials do not appear in any output. *(confirmed)*

**Acceptance:** PASSED.

## Task 7: Create Front Controller

- [x] Create `public/index.php`.
- [x] Bootstrap: load Autoloader, config, register ErrorHandler.
- [x] Add minimal routing via `app/Support/Router.php`.
- [x] Verify `display_errors` controlled by APP_DEBUG via `Bootstrap::init()`.
- [x] Test: home route → 200 OK; unknown route → 404 with custom page. *(both verified via curl)*

**Acceptance:** PASSED.

## Task 8: Verify PDO Database Connection

- [x] `scamshield` database created via `database/setup.php`. *(output: `[OK] Database created or already exists`)*
- [x] PDO connection: `SELECT 1` passes. *(verified: `DB connection: PASS (SELECT 1 returned 1)`)*
- [x] `/health` endpoint returns `{"status":"ok"}` with database check passing. *(confirmed)*
- [ ] Create least-privilege `scamshield_user@localhost` — skipped; DB_APP_PASSWORD not set. Add to .env to enable.

**Acceptance:** PASSED for core connection. Least-privilege user creation deferred to Milestone 2.

## Task 9: Update Project Documentation

- [x] Update `Docs/MEMORY.md` with verified environment details and current project state. *(done)*
- [x] Update `Docs/TASKS.md` Milestone 0 and Milestone 1 items. *(done)*
- [x] ADR-009 added to `Docs/DECISION.md`. *(done)*
- [x] `README.md` updated with verified environment table and setup guidance. *(done in Phase 0)*

**Acceptance:** PASSED.

## Open Decisions (Blocking Later Milestones)

These must be resolved before the features they block can be designed or implemented:

| Decision | Blocking | Recorded in |
|---|---|---|
| Document-root strategy | Tasks 5, 7 | DECISION.md (ADR-009 needed) |
| Anonymous analysis vs login-required | Analyses schema, FR-01 | DECISION.md |
| Evidence file allowlist and size limits | FR-06, Milestone 5 | DECISION.md |
| Data retention and deletion policy | Schema, FR-06, FR-07 | DECISION.md |
| Threat-intelligence provider and terms | FR-01 optional, Milestone 7 | DECISION.md (ADR-008 follow-up) |
