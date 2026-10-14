---
inclusion: auto
---

# ScamShield — Directory Structure and Naming Conventions

## Actual structure (verified 2026-10-14, after all milestones)

```
AntiScam/
├── Docs/                              ← canonical project documents (DO NOT move or rename)
│   ├── PRD.md
│   ├── ARCHITECTURE.md
│   ├── RULES.md
│   ├── DESIGN.md
│   ├── TASKS.md
│   ├── MEMORY.md
│   └── DECISION.md
├── .kiro/
│   ├── steering/
│   │   ├── product.md
│   │   ├── tech.md
│   │   ├── structure.md
│   │   ├── security.md
│   │   └── workflow.md
│   └── specs/
│       ├── project-foundation/        ← Milestones 0–1 spec (complete)
│       └── analysis-and-vt/           ← Analysis + VT spec (added 2026-10-14)
├── app/
│   ├── Controllers/
│   │   ├── HomeController.php
│   │   ├── HealthController.php
│   │   ├── AnalysisController.php
│   │   ├── AuthController.php
│   │   ├── IncidentController.php
│   │   └── AdminController.php
│   ├── Middleware/
│   │   └── AuthMiddleware.php
│   ├── Services/
│   │   ├── Analysis/
│   │   │   ├── UrlAnalysisService.php          ← local heuristic engine
│   │   │   ├── ThreatIntelProviderInterface.php ← adapter contract
│   │   │   ├── VirusTotalProvider.php           ← VT v3 API client
│   │   │   ├── VirusTotalResult.php             ← immutable value object
│   │   │   └── ReportCache.php                  ← flat-file cache
│   │   └── Incidents/
│   │       ├── IncidentService.php
│   │       └── EvidenceService.php
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── AnalysisRepository.php
│   │   ├── IncidentRepository.php
│   │   ├── EvidenceRepository.php
│   │   └── AuditRepository.php
│   ├── Validators/                    ← .gitkeep (validation in services/controllers)
│   ├── Views/
│   │   ├── layouts/
│   │   │   └── main.php               ← warning strip, nav, footer, flash
│   │   ├── partials/
│   │   │   ├── warning-strip.php
│   │   │   ├── nav.php                ← auth-aware navigation
│   │   │   ├── footer.php
│   │   │   ├── url-inspector-form.php ← tabbed URL/SMS/UPI form
│   │   │   ├── risk-meter.php
│   │   │   ├── annotated-url.php
│   │   │   ├── findings-list.php
│   │   │   ├── next-steps.php
│   │   │   └── limitations-box.php
│   │   ├── pages/
│   │   │   ├── home.php
│   │   │   ├── analyzer.php
│   │   │   ├── not-found.php
│   │   │   └── error.php
│   │   ├── analysis/
│   │   │   └── url_result.php         ← result page with VT card
│   │   ├── auth/
│   │   │   ├── register.php
│   │   │   └── login.php
│   │   ├── incidents/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── show.php
│   │   └── admin/
│   │       ├── queue.php
│   │       ├── detail.php
│   │       └── audit.php
│   └── Support/
│       ├── Autoloader.php
│       ├── Bootstrap.php
│       ├── Csrf.php
│       ├── Database.php
│       ├── ErrorHandler.php
│       ├── Logger.php
│       ├── Router.php
│       └── Session.php
├── config/
│   ├── env.php        ← .env file loader
│   ├── app.php        ← application config
│   └── database.php   ← database config
├── database/
│   ├── schema.sql     ← full MVP schema (users, analyses, incidents, evidence_files,
│   │                     incident_status_history, audit_logs)
│   └── setup.php      ← CLI database creation script
├── design/
│   └── stitch/        ← Stitch-exported design reference (not served)
├── public/            ← ONLY this directory is web-accessible (via Apache vhost)
│   ├── index.php      ← front controller + all routes
│   ├── .htaccess      ← mod_rewrite rules
│   └── assets/
│       ├── css/
│       │   ├── tokens.css
│       │   ├── base.css
│       │   └── components.css
│       ├── js/
│       └── images/
├── storage/
│   ├── evidence/      ← gitignored, MUST NOT be web-accessible
│   ├── vt_cache/      ← gitignored, flat-file VT report cache
│   └── logs/          ← gitignored
├── tests/
│   ├── Unit/
│   │   └── VirusTotalProviderTest.php ← 40/40 offline tests (2026-10-14)
│   ├── Integration/   ← .gitkeep
│   ├── Security/      ← .gitkeep
│   ├── smoke_analysis.php             ← 5/5 heuristic smoke tests
│   ├── debug_upi.php
│   └── router_test.php
├── .env               ← gitignored, local credentials only — NEVER commit
├── .env.example       ← placeholder template, tracked in git
├── .gitignore
└── README.md
```

## Naming conventions

- PHP classes: PascalCase.
- PHP files matching a class: same name as the class (e.g., `VirusTotalProvider.php`).
- Database tables: snake_case, plural (e.g., `incident_status_history`).
- Config and view files: lowercase with hyphens or underscores.
- Do not put generated source code in `Docs/`.
- Do not commit real credentials, uploads, or logs.
- Cache files go in `storage/vt_cache/` — SHA-256 keyed JSON, never web-accessible.

Full architecture document:

#[[file:Docs/ARCHITECTURE.md]]
