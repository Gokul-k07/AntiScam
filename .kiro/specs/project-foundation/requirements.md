# Requirements: Project Foundation

## Introduction

This spec covers Milestone 0 (repository and environment setup) and Milestone 1 (application skeleton) for ScamShield. It establishes the verified environment, secure repository configuration, and minimal PHP application structure needed before any feature development begins.

No application features are implemented in this spec. The spec is considered complete when the skeleton exists, the environment is confirmed, and the repository is in a clean, secure state.

## Glossary

- **Document root**: The directory Apache serves as the web root. Currently unresolved: `AntiScam/` directly vs. `AntiScam/public/` with a virtual host.
- **Front controller**: A single `index.php` entry  point that routes all requests.
- **PDO**: PHP Data Objects — the required database abstraction layer.
- **.env**: Local environment file containing real credentials. Must never be committed.
- **.env.example**: Placeholder-only template committed to the repository.

## Requirements

### Requirement 1: Verified Environment

**User Story:** As a developer, I want the PHP, MariaDB, and Apache versions confirmed and recorded so that all future implementation decisions are grounded in the actual installed environment.

#### Acceptance Criteria

1. WHEN environment checks are run, THEN PHP version ≥ 8.0 is confirmed and recorded in `Docs/MEMORY.md`.
2. WHEN environment checks are run, THEN MariaDB/MySQL version is confirmed and recorded in `Docs/MEMORY.md`.
3. WHEN environment checks are run, THEN Apache version is confirmed and recorded in `Docs/MEMORY.md`.
4. WHEN environment checks are run, THEN the presence of PDO, pdo_mysql, openssl, mbstring, and fileinfo extensions is confirmed.
5. IF the GD extension is absent, THEN this is noted as a known gap before any image-processing feature is planned.
6. WHEN environment checks are run, THEN the results are reported with exact command output — not inferred or assumed.

### Requirement 2: Secure Repository Configuration

**User Story:** As a developer, I want a `.gitignore` that prevents secrets, credentials, uploads, logs, and generated files from being committed so that sensitive data never enters version control.

#### Acceptance Criteria

1. WHEN `.gitignore` exists, THEN `.env` is listed and will not be tracked by Git.
2. WHEN `.gitignore` exists, THEN `.env.example` is NOT ignored and remains trackable.
3. WHEN `.gitignore` exists, THEN `storage/evidence/`, `storage/logs/`, and any uploads directory are excluded from tracking.
4. WHEN `.gitignore` exists, THEN common PHP runtime artifacts (composer cache, IDE files, OS files) are excluded.
5. WHEN `.env.example` exists, THEN it contains only placeholder values — no real credentials, keys, or passwords.
6. WHEN a developer runs `git status` after setup, THEN no secret or credential file appears as untracked or staged.

### Requirement 3: Document-Root Strategy Decision

**User Story:** As a developer, I want the Apache document-root strategy decided and documented before any PHP files are created so that file placement and access controls are correct from the start.

#### Acceptance Criteria

1. WHEN the document-root strategy is decided, THEN either (a) `AntiScam/public/` is configured as the Apache document root via a virtual host, or (b) a reviewed temporary arrangement is documented with explicit deny rules for `app/`, `config/`, `database/`, `storage/`, and secret files.
2. WHEN the strategy is documented, THEN the decision is recorded in `Docs/DECISION.md` as a new ADR.
3. THE chosen strategy SHALL be implemented before any PHP application files are created.
4. IF option (b) is chosen, THEN a migration path to option (a) is noted as a future task.

### Requirement 4: Application Directory Structure

**User Story:** As a developer, I want the planned directory structure created so that all future code has a consistent, documented home.

#### Acceptance Criteria

1. WHEN the skeleton is created, THEN it matches the structure defined in `Docs/ARCHITECTURE.md` section 3.
2. WHEN the skeleton is created, THEN `app/`, `config/`, `database/`, `storage/`, and `tests/` directories exist with `.gitkeep` files where needed.
3. WHEN the skeleton is created, THEN `storage/evidence/` and `storage/logs/` are present and listed in `.gitignore`.
4. WHEN the skeleton is created, THEN no placeholder files exist in `public/` until the document-root strategy (Requirement 3) is resolved.
5. THE `Docs/` directory SHALL remain at the project root and SHALL NOT be moved or renamed.

### Requirement 5: Front Controller and Basic Routing

**User Story:** As a developer, I want a minimal front controller and router so that all HTTP requests are handled through a single, controlled entry point.

#### Acceptance Criteria

1. WHEN a request reaches the front controller, THEN it is the sole entry point for all web requests.
2. WHEN a recognized route is requested, THEN the corresponding handler is invoked.
3. WHEN an unrecognized route is requested, THEN a 404 response is returned with a generic, user-friendly message — not a stack trace or file path.
4. WHEN a PHP error occurs, THEN the user sees a generic error message; detailed diagnostics go to a non-public log file only.
5. THE front controller SHALL NOT be created until Requirement 3 (document-root strategy) is resolved.

### Requirement 6: Configuration Loading

**User Story:** As a developer, I want a configuration loader that reads from environment variables or a local `.env` file so that real credentials never appear in committed source code.

#### Acceptance Criteria

1. WHEN the application starts, THEN configuration values are loaded from environment variables or a local `.env` file.
2. WHEN `.env` is absent, THEN the application fails with a clear, non-exposing error — it does not fall back to hardcoded credentials.
3. WHEN configuration is loaded, THEN the database DSN, credentials, and app secrets are accessible to the application without being echoed or logged.
4. THE `.env` file SHALL be listed in `.gitignore` and SHALL NOT be committed.
5. THE `.env.example` file SHALL contain only placeholder values and SHALL be committed as a setup reference.

### Requirement 7: Database Connection

**User Story:** As a developer, I want a verified PDO connection to MariaDB so that all subsequent database work starts from a confirmed, working foundation.

#### Acceptance Criteria

1. WHEN the database connection is initialized, THEN it uses PDO with `PDO::ERRMODE_EXCEPTION`.
2. WHEN the connection succeeds, THEN a smoke test confirms a simple query executes without error.
3. WHEN the connection fails, THEN a non-exposing error is shown to the user and details go to the log.
4. THE connection code SHALL use prepared statements — no user-controlled values concatenated into SQL.
5. WHEN connecting in local development, THEN the credentials come from `.env` — not hardcoded in source.
