# ScamShield

A locally hosted digital-safety assistant that helps users in India assess suspicious URLs, SMS messages, and UPI payment strings — with explainable heuristic findings, optional VirusTotal external reputation, and a private incident-reporting workflow.

> **Implementation status:** Full MVP complete (2026-10-14). All eight milestones implemented. 45/45 offline tests pass. Live database and browser testing require manual environment setup (see [Local Setup](#local-setup)).

---

## Table of contents

1. [Problem statement](#1-problem-statement)
2. [Key features](#2-key-features)
3. [Architecture and technology stack](#3-architecture-and-technology-stack)
4. [Directory structure](#4-directory-structure)
5. [Prerequisites](#5-prerequisites)
6. [Local setup](#6-local-setup)
7. [Environment configuration](#7-environment-configuration)
8. [Running the application](#8-running-the-application)
9. [Running tests](#9-running-tests)
10. [Security and privacy design](#10-security-and-privacy-design)
11. [Analysis capabilities and limitations](#11-analysis-capabilities-and-limitations)
12. [VirusTotal integration](#12-virustotal-integration)
13. [Test evidence and known gaps](#13-test-evidence-and-known-gaps)
14. [Roadmap](#14-roadmap)
15. [Development workflow](#15-development-workflow)
16. [Project documents](#16-project-documents)

---

## 1. Problem statement

Scam attempts in India — phishing SMS, fake banking portals, UPI reversal traps, digital-arrest calls — are increasingly sophisticated and target users who lack technical tools to assess them quickly. Official reporting routes exist (`cybercrime.gov.in`, national helpline `1930`) but are under-utilised because users often don't know whether an incident is worth reporting or how to describe it.

ScamShield addresses this by:
- Providing an **immediate, explainable risk assessment** without sending user data to an AI model.
- Surfacing **regulatory references** (RBI, TRAI, NPCI, CERT-In) that explain *why* a pattern is suspicious.
- Offering a **private incident log** with evidence attachment so users can document and submit to official channels.

---

## 2. Key features

| Feature | Status | Notes |
|---|---|---|
| URL heuristic analysis | ✅ Implemented | 8 rules, deterministic, airgapped |
| SMS/message analysis | ✅ Implemented | 7 rules, Unicode-safe, 4000 char limit |
| UPI / payment string analysis | ✅ Implemented | 3 rules; text input only, no QR image decode |
| VirusTotal external reputation | ✅ Implemented | Optional; URL type only; engine counts as evidence |
| Report cache | ✅ Implemented | SHA-256 keyed flat-file; configurable TTL |
| User accounts + sessions | ✅ Implemented | bcrypt, CSRF, session regeneration |
| Incident reports | ✅ Implemented | Reference `SS-YYYY-XXXXXX`; 8 incident types |
| Evidence upload | ✅ Implemented | jpg/png/gif/pdf/txt/csv; 10 MB; MIME validated |
| Admin review dashboard | ✅ Implemented | Status workflow, audit log, CSRF protected |
| Custom UI | ✅ Implemented | No Bootstrap CDN; tokens/base/components CSS |
| Analysis history page | ⬜ Planned | DB/repo ready; route not wired |
| Account settings/password change | ⬜ Planned | Not implemented |
| QR image decoding | ⬜ Planned | GD extension absent; deferred |
| Additional threat providers | ⬜ Planned | URLhaus, GSB — unimplemented |

---

## 3. Architecture and technology stack

```
Browser  →  Apache 2.4 (scamshield.local → public/ only)
             │
             └── public/index.php  (front controller, all routes)
                      │
                      ├── UrlAnalysisService      (airgapped heuristics)
                      ├── VirusTotalProvider      (optional VT v3 GET /urls/{id})
                      │       └── ReportCache     (storage/vt_cache/)
                      ├── AnalysisRepository      (analyses table)
                      ├── IncidentService + EvidenceService
                      ├── AuthController + AuthMiddleware
                      └── AdminController + AuditRepository
```

**Stack:**

| Layer | Technology |
|---|---|
| Backend | PHP 8.2 (no framework — custom MVC skeleton) |
| Database | MariaDB 10.4 via PDO prepared statements |
| Frontend | Custom HTML/CSS (tokens → base → components), vanilla JS |
| Fonts | Space Grotesk + IBM Plex Sans + IBM Plex Mono (Google Fonts) |
| HTTP server | Apache 2.4 via XAMPP, virtual host |
| External API | VirusTotal v3 (optional, public free tier) |
| Dependencies | None (no Composer packages; PHP built-ins only) |

---

## 4. Directory structure

```
AntiScam/
├── Docs/               ← All project documentation (PRD, architecture, rules…)
├── .kiro/              ← Kiro steering rules and feature specs
├── app/
│   ├── Controllers/    ← Home, Health, Analysis, Auth, Incident, Admin
│   ├── Middleware/     ← AuthMiddleware (requireAuth, requireAdmin)
│   ├── Services/
│   │   ├── Analysis/   ← UrlAnalysisService, VirusTotalProvider, ReportCache, …
│   │   └── Incidents/  ← IncidentService, EvidenceService
│   ├── Repositories/   ← User, Analysis, Incident, Evidence, Audit
│   ├── Views/          ← layouts/, partials/, pages/, analysis/, auth/, incidents/, admin/
│   └── Support/        ← Autoloader, Bootstrap, Csrf, Database, ErrorHandler, Logger, Router, Session
├── config/             ← env.php, app.php, database.php
├── database/           ← schema.sql (6 tables), setup.php (CLI creation script)
├── deploy/
│   └── infinityfree/   ← InfinityFree deployment package (upload htdocs/ contents to server)
├── design/             ← Exported reference screens (not served)
├── public/             ← WEB ROOT: index.php, .htaccess, assets/css|js|images
├── storage/
│   ├── evidence/       ← Private uploads (gitignored, not web-accessible)
│   ├── vt_cache/       ← VT report cache (gitignored)
│   └── logs/           ← Application logs (gitignored)
├── tests/
│   ├── Unit/VirusTotalProviderTest.php  ← 40 offline tests
│   └── smoke_analysis.php              ← 5 heuristic smoke tests
├── .env.example        ← Configuration template (no real values)
└── README.md
```

---

## 5. Prerequisites

| Requirement | Version | Notes |
|---|---|---|
| XAMPP | Any recent | Provides Apache 2.4, MariaDB 10.4, PHP 8.2 |
| PHP | 8.2+ | Extensions needed: PDO, pdo_mysql, openssl, mbstring, fileinfo |
| MariaDB / MySQL | 10.4+ | Bundled with XAMPP |
| Apache | 2.4 | Bundled with XAMPP |
| Git | Any | For cloning the repository |

> PHP GD extension is **absent** in the verified XAMPP environment. Do not use image-processing features until it is enabled.

---

## 6. Local setup

### Step 1 — Clone the repository

```
git clone https://github.com/Gokul-k07/AntiScam.git C:\xampp\htdocs\AntiScam
cd C:\xampp\htdocs\AntiScam
```

### Step 2 — Configure the Apache virtual host

Add to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName scamshield.local
    DocumentRoot "C:/xampp/htdocs/AntiScam/public"
    <Directory "C:/xampp/htdocs/AntiScam/public">
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>
</VirtualHost>
```

Add to `C:\Windows\System32\drivers\etc\hosts` (requires Administrator elevation):

```
127.0.0.1 scamshield.local
```

Restart Apache from the XAMPP Control Panel.

### Step 3 — Create the database and user

Open a MariaDB console or use phpMyAdmin:

```sql
CREATE DATABASE scamshield CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'scamshield_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON scamshield.* TO 'scamshield_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 4 — Apply the schema

```
mysql -u scamshield_user -p scamshield < database/schema.sql
```

### Step 5 — Configure environment

```
copy .env.example .env
```

Edit `.env` with your local values (see [Environment configuration](#7-environment-configuration)). **Never commit `.env`.**

### Step 6 — Create storage directories

```
mkdir storage\vt_cache
```

`storage\evidence\` and `storage\logs\` should already exist (gitkeep files may be absent if cloned fresh). Create them if missing.

---

## 7. Environment configuration

All configuration is in `.env` (copied from `.env.example`). The file must never be committed.

```ini
# Application
APP_ENV=local
APP_DEBUG=false
APP_KEY=<random 32-char string>

# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scamshield
DB_USERNAME=scamshield_user
DB_PASSWORD=<your_password>

# Storage (absolute path, outside web root)
STORAGE_EVIDENCE_PATH=C:\xampp\htdocs\AntiScam\storage\evidence

# Logging (absolute path, outside web root)
LOG_PATH=C:\xampp\htdocs\AntiScam\storage\logs

# Session
SESSION_NAME=scamshield_sess
SESSION_LIFETIME=1800

# ── VirusTotal (optional) ────────────────────────────────────────────────
# Leave THREAT_INTEL_PROVIDER=none to run without VT (default, always works).
# Set to 'virustotal' and provide a free API key to enable external checks.
THREAT_INTEL_PROVIDER=none
VIRUSTOTAL_API_KEY=not_configured
VIRUSTOTAL_CACHE_TTL_SECONDS=3600
```

**To enable VirusTotal:**
1. Register a free account at https://www.virustotal.com/
2. Copy your API key from your profile page.
3. Set `THREAT_INTEL_PROVIDER=virustotal` and `VIRUSTOTAL_API_KEY=<your_key>` in `.env`.

Public API limits: **4 requests/minute, 500/day**. The cache prevents redundant calls.

---

## 8. Running the application

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Ensure the hosts file entry (`scamshield.local`) is present.
3. Visit `http://scamshield.local/` in a browser.

**Key routes:**

| URL | Description |
|---|---|
| `http://scamshield.local/` | Home page |
| `http://scamshield.local/analyze` | URL / SMS / UPI analyzer |
| `http://scamshield.local/register` | User registration |
| `http://scamshield.local/login` | User login |
| `http://scamshield.local/incidents` | Incident list (requires login) |
| `http://scamshield.local/admin/incidents` | Admin queue (requires admin role) |
| `http://scamshield.local/health` | JSON health check |

**Setting the admin role** (requires DB access):
```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```

---

## 9. Running tests

All tests run offline — no network or database required.

```powershell
# Heuristic smoke tests (5 cases)
php tests\smoke_analysis.php

# VirusTotal provider unit tests (40 cases)
php tests\Unit\VirusTotalProviderTest.php

# Syntax check all PHP files
Get-ChildItem -Path . -Recurse -Filter "*.php" |
  ForEach-Object { php -l $_.FullName } |
  Where-Object { $_ -notmatch "No syntax errors" }
```

**Last verified results (2026-10-14):**

| Test suite | Result | Cases |
|---|---|---|
| `smoke_analysis.php` | ✅ All pass | 5/5 |
| `VirusTotalProviderTest.php` | ✅ All pass | 40/40 |
| PHP syntax check (all files) | ✅ Clean | All files |

---

## 10. Security and privacy design

### Key controls

| Threat | Control |
|---|---|
| SQL injection | PDO prepared statements on every DB query — no string concatenation |
| Credential theft | `password_hash(PASSWORD_BCRYPT, cost:12)` / `password_verify()` |
| Session fixation | `session_regenerate_id(true)` after login |
| CSRF | Per-session token generated by `Csrf`, validated on every POST |
| SSRF | No outbound fetch of user-submitted URLs; VT call uses fixed endpoint |
| XSS | `htmlspecialchars(ENT_QUOTES, 'UTF-8')` on all output |
| Unauthorized access | `AuthMiddleware::requireAuth()` / `requireAdmin()` on every private route |
| Path traversal | Evidence storage key is server-generated; original filename stripped |
| Upload abuse | Extension allowlist + server-side MIME via `fileinfo` + 10 MB limit |
| Secret leakage | `.env` gitignored; `Logger` redacts password, token, key, secret, api_key |
| Audit trail | Append-only `audit_logs` table; no UPDATE/DELETE at application level |

### Privacy principles

- Analysis input excerpts are capped at **500 characters** in the database — full message bodies are not retained by default.
- SMS/UPI text is **never** sent to any external API.
- Evidence files are stored outside the web root and require ownership verification for download.
- The platform distinguishes its own case reference from an official police complaint in every relevant view.

---

## 11. Analysis capabilities and limitations

### URL analysis

ScamShield parses URLs locally using `parse_url()` — it **never fetches the submitted URL**. Eight deterministic rules check:

- Unencrypted HTTP transport
- Raw IP address as host
- Indian bank (PSB) brand keywords on unofficial domains
- Government/utility brand keywords on unofficial domains
- High-abuse TLDs (.xyz, .tk, .ml, etc.)
- Sensitive query parameters (token, otp, auth, etc.)
- Android APK delivery
- Excessive hyphen-stacking in domain names

**Limitations:** Local rules cannot detect novel domains registered after the rule set was written, compromised legitimate sites, or HTTPS phishing sites with valid certificates.

### SMS / message text analysis

Seven rules detect urgency coercion, PSB impersonation, KYC credential demands, DLT non-compliance, embedded URLs (analysed recursively at 50% weight), digital/courier arrest patterns, and job-scam patterns.

**Limitations:** Keyword rules can produce false positives and false negatives. A clean SMS does not mean the sender is legitimate.

### UPI / payment string analysis

Three rules detect the PIN-to-receive reversal trap, VPA brand impersonation, and coercive urgency. Text input only — **QR image decoding is not implemented**.

**Limitations:** Valid VPA syntax does not confirm ownership or legitimacy. ScamShield does not query NPCI.

### Risk scale

| Band | Score range | Meaning |
|---|---|---|
| Low | 0–24 | No high-confidence indicators matched |
| Moderate | 25–54 | Some indicators matched; review carefully |
| High | 55–84 | Multiple significant indicators matched |
| Critical | 85–100 | Strong pattern consistent with known attack types |

**A "low" result or zero matched rules is not a declaration of safety.** Novel phishing infrastructure may not match any rule.

---

## 12. VirusTotal integration

When enabled, ScamShield retrieves an **existing** VirusTotal URL report for the submitted URL using the v3 REST API. It does **not** submit URLs for scanning.

### How it works

1. The submitted URL's bytes are encoded locally into an unpadded URL-safe Base64 identifier: `rtrim(strtr(base64_encode($url), '+/', '-_'), '=')`.
2. A `GET` request is made to `https://www.virustotal.com/api/v3/urls/{id}` with the `x-apikey` header.
3. `last_analysis_stats` (malicious, suspicious, harmless, undetected, timeout) are parsed from the response.
4. The result is cached for the configured TTL (default 1 hour) in `storage/vt_cache/`.

### Result states

| State | Meaning | Display |
|---|---|---|
| `found` | Report exists; engine counts available | Shows counts + mandatory caveat |
| `not_found` | HTTP 404 — no VT report for this URL | Shows "absence is not evidence of safety" |
| `rate_limited` | HTTP 429 — quota exceeded | Shows quota message; heuristics unaffected |
| `unavailable` | Network error, timeout, bad JSON | Shows service message; heuristics unaffected |
| `not_configured` | No API key / provider disabled | VT card hidden; heuristics unaffected |

### Important caveats

- Engine counts are **external reputation evidence**, not ScamShield's verdict and not a probability of fraud.
- **Zero detections are not evidence of safety.** Freshly registered phishing domains may not be in any engine database.
- SMS and UPI text are **never** sent to VirusTotal.
- The API key is read from `$_ENV` only and is never logged, displayed, or stored in object properties.

---

## 13. Test evidence and known gaps

### Verified by automated tests

| Test | Command | Result (2026-10-14) |
|---|---|---|
| Heuristic smoke — APK URL | `php tests\smoke_analysis.php` | critical / score 92 / 4 findings |
| Heuristic smoke — empty input | `php tests\smoke_analysis.php` | low / score 0 / 0 findings |
| Heuristic smoke — SBI KYC SMS | `php tests\smoke_analysis.php` | high / score 83 / 6 findings |
| Heuristic smoke — UPI trap | `php tests\smoke_analysis.php` | moderate / score 50 / 1 finding |
| Heuristic smoke — official SBI URL | `php tests\smoke_analysis.php` | low / score 0 / 0 findings |
| VT value object (all 5 states) | `php tests\Unit\VirusTotalProviderTest.php` | 24/24 PASS |
| VT toThreatStatus() mapping | same | 7/7 PASS |
| URL identifier algorithm | same | 5/5 PASS |
| VT provider guard conditions | same | 4/4 PASS |
| ReportCache read/write/TTL/corrupt | same | 10/10 PASS |
| PHP syntax check (all files) | powershell loop | 0 errors |

### Known gaps requiring manual verification

- PDO repository queries and DB constraints (requires live MariaDB)
- File upload/download end-to-end (requires live server + DB)
- Full browser request cycle: session cookies, CSRF token round-trip, redirect-after-post
- VirusTotal live API call with a real key (offline guard tests pass; HTTP layer untested)
- Cross-user access control (requires two test accounts in live DB)
- Admin role enforcement (requires admin user in live DB)

---

## 14. Roadmap

### Completed (MVP + VT — 2026-10-14)
- Full heuristic engine (URL, SMS, UPI) with explainable findings
- VirusTotal v3 external reputation with caching
- User accounts, CSRF, session security
- Incident reports, evidence upload, status tracking
- Admin review, audit log
- Custom UI (tokens → base → components CSS)

### Planned (post-MVP)
- Analysis history page (`/analyses`) — DB and repo ready; route not wired
- Account settings / password change
- Pagination for incident list and admin queue
- Integration test with live VirusTotal key
- Additional threat providers (URLhaus, Google Safe Browsing) — unimplemented; terms not yet reviewed
- REST API
- Public hosting (requires HTTPS, privacy review, rate limiting)

---

## 15. Development workflow

This project was built with [Kiro](https://kiro.dev/) as the AI coding assistant. The workflow:

1. **Steering files** in `.kiro/steering/` define binding rules for the AI agent (security constraints, naming conventions, VT integration rules).
2. **Specs** in `.kiro/specs/` define requirements, design, and task lists for each feature area.
3. **Inspect before touching** — every Kiro session starts with `git status` and reading relevant source files.
4. **Evidence-based progress** — tasks are marked complete only when tests pass or files are verifiably on disk.
5. **No auto-commit** — Git commits and pushes are only made at the user's explicit instruction.

For new contributors:
- Do not add Composer packages without justification.
- Do not use Bootstrap CDN — extend the existing CSS system.
- Do not fetch user-submitted URLs from the server (SSRF).
- Do not commit `.env` or any file containing real credentials.
- Read `Docs/RULES.md` before making security-relevant changes.

---

## 16. Project documents

| Document | Purpose |
|---|---|
| [Docs/PRD.md](Docs/PRD.md) | Product requirements, user roles, implemented features, roadmap |
| [Docs/ARCHITECTURE.md](Docs/ARCHITECTURE.md) | Logical architecture, modules, DB schema, security controls |
| [Docs/DESIGN.md](Docs/DESIGN.md) | UI design system, color palette, typography, view structure |
| [Docs/RULES.md](Docs/RULES.md) | Binding security and engineering rules (read before coding) |
| [Docs/TASKS.md](Docs/TASKS.md) | Milestone task board with implementation status |
| [Docs/DECISION.md](Docs/DECISION.md) | Architecture decision records (ADR-001 to ADR-009) |
| [Docs/MEMORY.md](Docs/MEMORY.md) | Durable context for AI coding agents |
| [Docs/DEPLOYMENT_INFINITYFREE.md](Docs/DEPLOYMENT_INFINITYFREE.md) | InfinityFree shared hosting deployment guide (scamshield.site.je) |

---

*ScamShield is a student engineering project. It provides heuristic indicators — not legal determinations. For financial cyber fraud in India, call **1930** immediately or report at [cybercrime.gov.in](https://www.cybercrime.gov.in/).*
