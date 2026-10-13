---
inclusion: auto
---

# ScamShield — Directory Structure and Naming Conventions

## Verified existing structure (as of 2026-10-12, after Phase 1)
```
AntiScam/
├── Docs/              ← canonical project documents (DO NOT move or rename)
│   ├── PRD.md
│   ├── ARCHITECTURE.md
│   ├── RULES.md
│   ├── DESIGN.md
│   ├── TASKS.md
│   ├── MEMORY.md
│   └── DECISION.md
├── .kiro/             ← Kiro workspace configuration
│   ├── steering/
│   └── specs/
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   └── HealthController.php
│   ├── Middleware/
│   ├── Services/
│   │   ├── Analysis/
│   │   ├── Incidents/
│   │   └── Evidence/
│   ├── Repositories/
│   ├── Validators/
│   ├── Views/
│   │   ├── layouts/main.php
│   │   ├── pages/home.php
│   │   ├── pages/not-found.php
│   │   ├── pages/error.php
│   │   └── partials/health-status.php
│   └── Support/
│       ├── Autoloader.php
│       ├── Bootstrap.php
│       ├── Database.php
│       ├── ErrorHandler.php
│       ├── Logger.php
│       └── Router.php
├── config/
│   ├── env.php        ← .env file loader
│   ├── app.php        ← application config
│   └── database.php   ← database config
├── database/
│   ├── schema.sql     ← stub (tables added in Milestone 2+)
│   └── setup.php      ← CLI database creation script
├── public/            ← ONLY this directory is web-accessible (via Apache vhost)
│   ├── index.php      ← front controller
│   ├── .htaccess      ← mod_rewrite rules
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── storage/
│   ├── evidence/      ← gitignored, MUST NOT be web-accessible
│   └── logs/          ← gitignored
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Security/
├── .env               ← gitignored, local credentials only — NEVER commit
├── .env.example       ← placeholder template, tracked in git
├── .gitignore
└── README.md
```

## Planned application structure (not yet created)
There are no more planned structural items from Milestone 1. Future directories (if needed) will be added within the existing structure as features are built.

## Naming conventions
- PHP classes: PascalCase.
- PHP files matching a class: same name as the class (e.g., `UrlAnalyzer.php`).
- Database tables: snake_case, plural (e.g., `incident_status_history`).
- Config and view files: lowercase with hyphens or underscores.
- Do not put generated source code in `Docs/`.
- Do not commit real credentials, uploads, or logs.

Full architecture document:

#[[file:Docs/ARCHITECTURE.md]]
