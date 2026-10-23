# ScamShield — InfinityFree Deployment Guide

**Target domain:** `https://scamshield.site.je`  
**Hosting provider:** InfinityFree (free shared hosting)  
**Prepared:** 2026-10-21  
**Status:** Deployment package ready. Manual steps required before the site is live.

> **This guide covers only the technical deployment steps. It does not cover
> privacy review, terms-of-service compliance, or production readiness for
> public traffic. Read the constraints section before proceeding.**

---

## Table of Contents

1. [Verified Hosting Requirements and Limitations](#1-verified-hosting-requirements-and-limitations)
2. [Deploy Package Layout](#2-deploy-package-layout)
3. [Files to Upload — Exact Remote Directory Layout](#3-files-to-upload--exact-remote-directory-layout)
4. [Hosted Database Setup](#4-hosted-database-setup)
5. [Configuration Variables](#5-configuration-variables)
6. [HTTPS and Domain Setup](#6-https-and-domain-setup)
7. [Writable Directories and Permission Checks](#7-writable-directories-and-permission-checks)
8. [Deployment Procedure](#8-deployment-procedure)
9. [Post-Deployment Security Checks](#9-post-deployment-security-checks)
10. [Rollback Procedure](#10-rollback-procedure)
11. [Troubleshooting](#11-troubleshooting)
12. [Features That Cannot Safely Operate on This Plan](#12-features-that-cannot-safely-operate-on-this-plan)

---

## 1. Verified Hosting Requirements and Limitations

### Confirmed InfinityFree specifications

| Property | Value | Source |
|---|---|---|
| PHP version | 8.4 | InfinityFree homepage (2026) |
| MySQL version | 8.0 / MariaDB 11.4 | InfinityFree homepage (2026) |
| Web root | `htdocs/` | InfinityFree documentation |
| `open_basedir` | Restricts PHP to `htdocs/` only | InfinityFree admin confirmation |
| Files outside `htdocs/` | Deleted automatically | InfinityFree documentation |
| Outbound HTTP/HTTPS | Allowed (`file_get_contents`, cURL) | InfinityFree admin confirmation |
| File size limit | 10 MB per file | InfinityFree documentation |
| `mod_rewrite` | Enabled, `.htaccess` supported | InfinityFree documentation |
| Session storage | Server-managed (`/php_sessions`) | Inferred from `open_basedir` |
| SSL certificates | Free (Let's Encrypt via InfinityFree) | InfinityFree homepage |
| SSL termination | Load balancer (sets `X-Forwarded-Proto`) | InfinityFree forum |
| Disk space | 5 GB | InfinityFree homepage |
| PHP extensions available | PDO, pdo_mysql, openssl, mbstring, fileinfo, json | Standard PHP 8.4 |

### Critical constraints that affect this application

**`open_basedir` restriction:** PHP can only read/write files inside `htdocs/`. This means:
- The `.env` credentials file must live inside `htdocs/config/` (protected by `.htaccess`).
- `storage/evidence/`, `storage/vt_cache/`, `storage/logs/` must be inside `htdocs/`.
- No files can be placed outside `htdocs/` — they are deleted automatically.

**SSL termination at load balancer:** `$_SERVER['HTTPS']` may not be set. The deployed
`Session.php` detects HTTPS via both `$_SERVER['HTTPS']` and `HTTP_X_FORWARDED_PROTO`.

**No shell/CLI access:** Database must be set up via phpMyAdmin, not via `php database/setup.php`.

**No Composer:** The application uses no Composer packages — no impact.

**InfinityFree fair-use limits:** Free accounts have CPU and hit limits. Heavy traffic or
large evidence uploads could trigger suspension. This is a student/demonstration deployment.

---

## 2. Deploy Package Layout

The deployment package is at:
```
deploy/infinityfree/htdocs/
```

This directory maps **exactly** to the remote `htdocs/` folder on InfinityFree.
Upload its entire contents into your InfinityFree `htdocs/` folder via FTP.

**Do NOT upload** into `htdocs/` from the repository root:
- `.git/`, `tests/`, `Docs/`, `design/`, `.env`, `.env.example`
- `database/setup.php` (CLI tool, not needed on server)
- `public/` (local XAMPP layout — replaced by `htdocs/index.php`)

---

## 3. Files to Upload — Exact Remote Directory Layout

After upload, your InfinityFree `htdocs/` should look like this:

```
htdocs/                               ← web root for scamshield.site.je
├── .htaccess                         ← HTTPS redirect, routing, security headers
├── index.php                         ← Front controller (adapted for htdocs-root)
│
├── app/                              ← PHP source — HTTP-denied by app/.htaccess
│   ├── .htaccess                     ← Require all denied
│   ├── Controllers/                  ← HomeController, AuthController, etc.
│   ├── Middleware/                   ← AuthMiddleware
│   ├── Repositories/                 ← User, Analysis, Incident, Evidence, Audit
│   ├── Services/
│   │   ├── Analysis/                 ← UrlAnalysisService, VirusTotalProvider, etc.
│   │   └── Incidents/                ← IncidentService, EvidenceService
│   ├── Support/                      ← Bootstrap, Router, Session, Csrf, etc.
│   └── Views/                        ← layouts/, partials/, pages/, analysis/, etc.
│
├── config/                           ← Config — HTTP-denied by config/.htaccess
│   ├── .htaccess                     ← Require all denied
│   ├── .env                          ← YOUR CREDENTIALS — upload manually, never commit
│   ├── .env.deploy.example           ← Template with placeholders (safe to upload)
│   ├── env.php                       ← Loads config/.env (adapted for htdocs layout)
│   ├── app.php                       ← Application config (reads from $_ENV)
│   └── database.php                  ← Database config (reads from $_ENV)
│
├── database/                         ← SQL — HTTP-denied by database/.htaccess
│   ├── .htaccess                     ← Require all denied
│   ├── schema.sql                    ← Reference copy (do not import this one)
│   └── schema_hosted.sql             ← Import this via phpMyAdmin
│
├── assets/                           ← PUBLIC static files
│   ├── css/                          ← tokens.css, base.css, components.css
│   ├── js/                           ← (empty — no JS bundles)
│   └── images/                       ← (empty — no images)
│
└── storage/                          ← Private data — HTTP-denied by storage/.htaccess
    ├── .htaccess                     ← Require all denied
    ├── evidence/                     ← Uploaded evidence files (writable)
    ├── vt_cache/                     ← VirusTotal report cache (writable)
    └── logs/                         ← Application logs (writable)
```

---

## 4. Hosted Database Setup

### Step 1 — Create the database in InfinityFree control panel

1. Log in to the InfinityFree client area at `https://app.infinityfree.com/`.
2. Select your hosting account.
3. Go to **Databases → MySQL Databases**.
4. Create a new database. InfinityFree will prefix it with your account ID
   (e.g., `if0_12345678_scamshield`). Note the full database name.
5. Create a database user with a strong password. Note the username and password.
6. Add the user to the database with **SELECT, INSERT, UPDATE, DELETE** permissions only.
   Do NOT grant ALTER, DROP, or CREATE.
7. Note the **MySQL hostname** shown in the panel (e.g., `sql123.infinityfree.com`).

### Step 2 — Import the schema via phpMyAdmin

1. In the InfinityFree control panel, open **phpMyAdmin**.
2. Select your database from the left panel.
3. Click **Import** in the top menu.
4. Click **Choose File** and select:
   ```
   deploy/infinityfree/htdocs/database/schema_hosted.sql
   ```
5. Leave encoding as `utf-8`. Click **Go**.
6. Verify: click **Structure**. You should see 6 tables:
   `users`, `analyses`, `incidents`, `evidence_files`,
   `incident_status_history`, `audit_logs`.

### Step 3 — Set admin role (after first registration)

After the site is live and you have registered an account, run this in phpMyAdmin
(**SQL** tab — replace with your actual email):

```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```

> Do NOT run this before registering — the users table will be empty.

---

## 5. Configuration Variables

Create `config/.env` by copying `config/.env.deploy.example` and filling in all
`<CHANGE_THIS>` values. Upload it to `htdocs/config/.env` via FTP.

**Never commit `config/.env` to version control.**  
**Never paste credentials into Kiro chat or any public medium.**

| Variable | Where to find it | Notes |
|---|---|---|
| `APP_ENV` | Set to `production` | Must be `production` |
| `APP_DEBUG` | Set to `false` | Never `true` in production |
| `APP_KEY` | Generate: `php -r "echo bin2hex(random_bytes(16));"` | 32-char hex string |
| `DB_HOST` | InfinityFree control panel → MySQL Databases | e.g. `sql123.infinityfree.com` |
| `DB_PORT` | Always `3306` | — |
| `DB_DATABASE` | InfinityFree control panel → full prefixed name | e.g. `if0_12345678_scamshield` |
| `DB_USERNAME` | InfinityFree control panel → database user | e.g. `if0_12345678_user` |
| `DB_PASSWORD` | Set when creating the database user | Strong random password |
| `STORAGE_EVIDENCE_PATH` | See note below | Absolute server path |
| `LOG_PATH` | See note below | Absolute server path |
| `SESSION_NAME` | `ss_prod` (or any unique name) | Avoid `scamshield_sess` on shared host |
| `SESSION_LIFETIME` | `1800` | Seconds |
| `THREAT_INTEL_PROVIDER` | `none` or `virustotal` | Leave `none` until tested |
| `VIRUSTOTAL_API_KEY` | VirusTotal account → API key | Keep secret |

### Finding your absolute server path

Your InfinityFree path format is:
```
/home/vol{N}_{M}/infinityfree.com/{username}/htdocs
```

To find it:
1. In InfinityFree control panel → **FTP Details** — shows the FTP path.
2. Or create a temporary `phpinfo.php` in `htdocs/`:
   ```php
   <?php phpinfo();
   ```
   Visit `https://scamshield.site.je/phpinfo.php`, search for `DOCUMENT_ROOT`.
   **Delete `phpinfo.php` immediately after noting the path.**

Then set:
```ini
STORAGE_EVIDENCE_PATH=/home/vol1_1/infinityfree.com/if0_12345678/htdocs/storage/evidence
LOG_PATH=/home/vol1_1/infinityfree.com/if0_12345678/htdocs/storage/logs
```

---

## 6. HTTPS and Domain Setup

### Your domain `scamshield.site.je`

InfinityFree provides free SSL certificates via Let's Encrypt.

1. In the InfinityFree control panel, go to **SSL Certificates**.
2. Select your domain (`scamshield.site.je`) and install a free SSL certificate.
3. Wait up to 24 hours for DNS propagation if you recently pointed the domain.
4. The `.htaccess` in `htdocs/` already redirects HTTP → HTTPS:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteCond %{HTTP:X-Forwarded-Proto} !https
   RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
   ```

### Verify HTTPS is working

- Visit `http://scamshield.site.je/` — should redirect to `https://`.
- Browser padlock should appear. Certificate should be valid (Let's Encrypt).
- Session cookies will only be `Secure` once the site runs over HTTPS.

---

## 7. Writable Directories and Permission Checks

These three directories must be writable by the PHP process:

| Directory | Purpose | Required |
|---|---|---|
| `storage/evidence/` | Evidence file uploads | Yes — uploads fail without it |
| `storage/vt_cache/` | VirusTotal report cache | Yes — cache silently disabled if not writable |
| `storage/logs/` | Application and PHP error logs | Recommended |

### Setting permissions via FTP

In your FTP client (FileZilla or similar), right-click each directory → **File Permissions** → set to `755`:
- `755` = owner read/write/execute, group and other read/execute.
- InfinityFree's PHP process runs as the file owner, so `755` is sufficient.
- Do NOT set `777` — overly permissive and may be blocked by the host.

### Verifying writability

After upload, visit `https://scamshield.site.je/health` — the JSON response
includes a `writable_storage` field indicating whether the evidence path is writable.

---

## 8. Deployment Procedure

### Full deploy (first time)

1. **Create InfinityFree account and hosting** (if not already done).
2. **Point domain** `scamshield.site.je` to InfinityFree nameservers.
3. **Install SSL certificate** (step 6 above).
4. **Create database** (step 4 above).
5. **Prepare `config/.env`**: copy `config/.env.deploy.example`, fill in all values.
6. **Upload files via FTP**:
   - Connect to InfinityFree FTP (credentials in control panel → FTP Details).
   - Upload the entire contents of `deploy/infinityfree/htdocs/` into `htdocs/`.
   - Upload `config/.env` into `htdocs/config/`.
7. **Set directory permissions**: `storage/evidence/`, `storage/vt_cache/`, `storage/logs/` → `755`.
8. **Import schema**: phpMyAdmin → select database → Import → `schema_hosted.sql`.
9. **Smoke test**: visit `https://scamshield.site.je/health`.
10. **Register account** and set admin role (step 4, step 3).
11. **Run post-deployment security checks** (step 9 below).

### Updating the deployment (after code changes)

1. Re-run `php -l` on changed files in `deploy/infinityfree/htdocs/`.
2. Upload only the changed files via FTP (do not re-upload schema or `.env`).
3. Re-run post-deployment checks for any changed routes.

---

## 9. Post-Deployment Security Checks

Run these checks manually after each deployment. Checks marked **required** must
pass before the site handles real user data.

| Check | Expected result | Required |
|---|---|---|
| `https://scamshield.site.je/` loads | Homepage renders, no PHP errors | Yes |
| `https://scamshield.site.je/health` | JSON `{"status":"ok"}` or similar | Yes |
| `http://scamshield.site.je/` redirects | 301 → HTTPS | Yes |
| `https://scamshield.site.je/config/.env` | 403 Forbidden | **Critical** |
| `https://scamshield.site.je/app/Support/Bootstrap.php` | 403 Forbidden | **Critical** |
| `https://scamshield.site.je/storage/evidence/` | 403 Forbidden | **Critical** |
| `https://scamshield.site.je/storage/logs/app.log` | 403 Forbidden | **Critical** |
| `https://scamshield.site.je/database/schema_hosted.sql` | 403 Forbidden | **Critical** |
| `https://scamshield.site.je/.env` (root) | 403 Forbidden | Yes (no .env at root) |
| `https://scamshield.site.je/nonexistent-page` | 404 page (no stack trace) | Yes |
| Register new account | Success, redirect to home | Yes |
| Login with wrong password | Generic error, no account enumeration | Yes |
| Session cookie on login | `HttpOnly`, `SameSite=Lax`, `Secure` flag set | Yes |
| Submit URL for analysis | Result page renders with risk level | Yes |
| CSRF: submit form without token | 403 response | Yes |

---

## 10. Rollback Procedure

InfinityFree does not provide automatic backups on the free plan.

### Before every deployment

1. Download the current `htdocs/` contents via FTP to a local backup directory.
2. Export your database via phpMyAdmin → Export → SQL format.
3. Keep a copy of `config/.env` locally (secured, not in git).

### To rollback

1. Upload the previous version's files via FTP over the current files.
2. If schema changed: restore the previous SQL dump via phpMyAdmin → Import.
3. Do NOT delete the database and re-import unless it is a complete fresh install.

---

## 11. Troubleshooting

### 403 Forbidden on all pages

- Check that `htdocs/.htaccess` uploaded correctly. FTP clients sometimes skip dotfiles.
- In FileZilla: **Server → Force showing hidden files** to see `.htaccess`.
- Verify `mod_rewrite` is enabled (it is on InfinityFree by default).

### 404 on all routes except homepage

- The rewrite rule in `htdocs/.htaccess` is not routing to `index.php`.
- Confirm `RewriteEngine On` is present and `RewriteRule ^ index.php [QSA,L]` is correct.
- Check that `index.php` is present in `htdocs/` (not in a subfolder).

### 500 Internal Server Error

- Check `storage/logs/php_errors.log` via FTP.
- If `LOG_PATH` is not set yet, check InfinityFree's error log in the control panel.
- Most likely cause: `config/.env` not uploaded, or DB credentials wrong.
- Temporarily visit `/health` — it returns JSON with component statuses.

### Database connection failed

- Verify `DB_HOST` is the MySQL hostname from InfinityFree panel (not `127.0.0.1`).
- Verify `DB_DATABASE` uses the full prefixed name (e.g. `if0_12345678_scamshield`).
- Verify the database user has SELECT, INSERT, UPDATE, DELETE grants.
- Test credentials in phpMyAdmin directly.

### Evidence upload fails silently

- Check that `storage/evidence/` has permission `755`.
- Verify `STORAGE_EVIDENCE_PATH` in `config/.env` is the correct absolute server path.
- The 10 MB InfinityFree file size limit matches the application's own 10 MB limit.
- If a file over 10 MB is uploaded, InfinityFree will delete it automatically.

### VirusTotal always returns "unavailable"

- Confirm `THREAT_INTEL_PROVIDER=virustotal` and `VIRUSTOTAL_API_KEY` in `config/.env`.
- InfinityFree allows outbound HTTPS — check `storage/logs/app.log` for the exact error.
- If the API returns HTTP 401, the API key is wrong or expired.
- If timeout: increase `VIRUSTOTAL_CACHE_TTL_SECONDS` to reduce request frequency.

### Session cookie missing `Secure` flag

- The site must be served over HTTPS. Check the SSL certificate is installed.
- Verify the `.htaccess` HTTP→HTTPS redirect is in place.
- On InfinityFree, `X-Forwarded-Proto: https` is set by the load balancer — the
  deployed `Session.php` checks this header.

### Logs not being written

- Verify `LOG_PATH` in `config/.env` is an absolute path inside `htdocs/`.
- Verify `storage/logs/` has permission `755`.
- The logger fails silently on write errors — it does not crash the application.

---

## 12. Features That Cannot Safely Operate on This Plan

| Feature | Status | Reason |
|---|---|---|
| Evidence storage outside web root | **Not possible** | `open_basedir` restricts to `htdocs/` only. Evidence is stored inside `htdocs/storage/evidence/` protected by `.htaccess`. This is weaker than ideal but is the only option on free shared hosting. |
| `config/.env` outside web root | **Not possible** | Same `open_basedir` constraint. Stored in `htdocs/config/` protected by `.htaccess`. |
| CLI database setup (`php database/setup.php`) | **Not available** | No SSH/shell access. Use phpMyAdmin instead. |
| QR image decoding | **Not implemented** | GD extension not confirmed on InfinityFree; feature not in MVP. Do not claim it works. |
| Analysis history page (`/analyses`) | **Not implemented** | Route not wired in MVP. DB and repository are ready. |
| Account settings / password change | **Not implemented** | Not in MVP scope. |
| High-volume evidence uploads | **Not recommended** | InfinityFree fair-use CPU/hit limits apply. Large files (near 10 MB) may trigger rate limiting. |
| Production use with real user data | **Not recommended without review** | Free shared hosting is not suitable for production systems handling sensitive incident data without a privacy review, rate limiting, and backup strategy. |

---

*ScamShield is a student engineering project. It provides heuristic indicators — not legal
determinations. For financial cyber fraud in India, call **1930** or report at
[cybercrime.gov.in](https://www.cybercrime.gov.in/).*
