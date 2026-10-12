# Design: Project Foundation

## Overview

This design document covers the technical approach for establishing the ScamShield repository baseline and application skeleton. It corresponds to `Docs/ARCHITECTURE.md` sections 2 and 3, and to Milestones 0 and 1 in `Docs/TASKS.md`.

No feature logic is included. The output of this spec is a clean, secure, runnable skeleton — not a working product feature.

Reference: #[[file:Docs/ARCHITECTURE.md]]

## 1. Repository and Environment Baseline

### 1.1 .gitignore

A comprehensive `.gitignore` must prevent credentials, uploads, logs, IDE files, and OS artifacts from entering version control. Key exclusions:

- `.env` (real credentials — never committed)
- `storage/evidence/` (private uploads)
- `storage/logs/` (runtime logs)
- `vendor/` (Composer packages, if later added)
- IDE folders (`.idea/`, `.vscode/`)
- OS files (`Thumbs.db`, `.DS_Store`)
- PHP opcache and cache files

`.env.example` must NOT be gitignored — it is the committed placeholder template.

### 1.2 .env.example

Placeholder file only. Structure:

```
APP_ENV=local
APP_DEBUG=false
APP_KEY=changeme

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scamshield
DB_USERNAME=scamshield_user
DB_PASSWORD=changeme

STORAGE_EVIDENCE_PATH=/path/outside/webroot/evidence
```

No real values. Developers copy this to `.env` and fill in their own values.

## 2. Document-Root Strategy (Unresolved — Decision Required)

**Status: UNRESOLVED. Do not create `public/` or application entry points until this decision is made.**

Two options from `Docs/ARCHITECTURE.md`:

**Option A (recommended):** Configure XAMPP to serve `AntiScam/public/` as the document root via a virtual host. `app/`, `config/`, `database/`, and `storage/` are never web-accessible.

**Option B (temporary):** Serve from `AntiScam/` root with `.htaccess` deny rules on sensitive directories. Requires careful review and a migration plan to Option A.

This decision must be recorded as a new ADR in `Docs/DECISION.md` before any PHP files are placed.

## 3. Directory Skeleton

Once the document-root decision is made, create this structure (matching `Docs/ARCHITECTURE.md` §3):

```
AntiScam/
├── public/
│   └── index.php          ← front controller
├── app/
│   ├── Controllers/       ← .gitkeep
│   ├── Middleware/        ← .gitkeep
│   ├── Services/
│   │   ├── Analysis/      ← .gitkeep
│   │   ├── Incidents/     ← .gitkeep
│   │   └── Evidence/      ← .gitkeep
│   ├── Repositories/      ← .gitkeep
│   ├── Validators/        ← .gitkeep
│   ├── Views/             ← .gitkeep
│   └── Support/           ← .gitkeep
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   └── schema.sql         ← empty stub for now
├── storage/
│   ├── evidence/          ← gitignored, never web-served
│   └── logs/              ← gitignored
└── tests/
    ├── Unit/              ← .gitkeep
    ├── Integration/       ← .gitkeep
    └── Security/          ← .gitkeep
```

## 4. Configuration Loader

`config/app.php` and `config/database.php` read from environment variables or parse a local `.env` file using a minimal loader (no framework dependency required for MVP).

Pattern:
```php
// config/database.php
return [
    'host'     => $_ENV['DB_HOST']     ?? '127.0.0.1',
    'port'     => $_ENV['DB_PORT']     ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? null,
    'username' => $_ENV['DB_USERNAME'] ?? null,
    'password' => $_ENV['DB_PASSWORD'] ?? null,
];
```

If required values are absent, throw a clear exception — do not fall back to hardcoded credentials.

## 5. Front Controller (public/index.php)

A minimal front controller:
- Loads autoloader / bootstrap.
- Reads the request URI and method.
- Dispatches to the appropriate controller.
- Returns a 404 with a generic message for unrecognized routes.
- PHP errors and exceptions go to log; users see a generic message only.

No routing library required at MVP scale — a simple array-based or match-based dispatcher is sufficient.

## 6. PDO Database Connection

```php
// Pseudocode — actual implementation follows after .env and config/ exist
$pdo = new PDO(
    "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
    $config['username'],
    $config['password'],
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]
);
```

Connection is initialized once per request via a simple service locator or dependency injection. Credentials never appear in output or logs.

## 7. Error Handling

- `set_error_handler` and `set_exception_handler` in the bootstrap.
- Users see: generic message with a reference code.
- Log receives: full exception, file, line, trace — with secrets redacted.
- `display_errors = Off` in production; verify `php.ini` settings before any public deployment.

## 8. Security Considerations for Skeleton

- `storage/evidence/` and `storage/logs/` must be outside or access-denied from the web root.
- If Option B (root serving) is used temporarily, add `.htaccess` `Deny from all` to `app/`, `config/`, `database/`, `storage/`.
- No session or authentication logic in this spec — that is Milestone 3.

## 9. Open Items (do not implement until resolved)

| Item | Blocking |
|---|---|
| Document-root strategy (Option A vs B) | public/ creation, front controller placement |
| Anonymous analysis decision | analyses table design |
| Evidence allowlist and size limits | evidence upload feature |
| Retention policy | schema finalization |
| Threat-intel provider | analysis adapter |
