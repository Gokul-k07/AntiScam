---
inclusion: auto
---

# ScamShield — Directory Structure and Naming Conventions

## Verified existing structure (as of 2026-10-12)
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
├── .gitignore
├── .env.example
└── README.md
```

## Planned application structure (not yet created)
The following is the intended structure from ARCHITECTURE.md. Nothing below has been created yet.

```
AntiScam/
├── public/            ← Web document root / front controller (PENDING: virtual host decision)
│   ├── index.php
│   └── assets/
│       ├── css/
│       ├── js/
│       └── images/
├── app/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Services/
│   │   ├── Analysis/
│   │   ├── Incidents/
│   │   └── Evidence/
│   ├── Repositories/
│   ├── Validators/
│   ├── Views/
│   └── Support/
├── config/
│   ├── app.php
│   └── database.php
├── database/
│   ├── schema.sql
│   └── seeds/
├── storage/
│   ├── evidence/      ← MUST NOT be publicly served
│   └── logs/
└── tests/
    ├── Unit/
    ├── Integration/
    └── Security/
```

## Naming conventions
- PHP classes: PascalCase.
- PHP files matching a class: same name as the class (e.g., `UrlAnalyzer.php`).
- Database tables: snake_case, plural (e.g., `incident_status_history`).
- Config and view files: lowercase with hyphens or underscores.
- Do not put generated source code in `Docs/`.
- Do not commit real credentials, uploads, or logs.

Full architecture document:

#[[file:Docs/ARCHITECTURE.md]]
