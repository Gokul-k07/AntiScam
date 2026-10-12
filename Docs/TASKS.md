# ScamShield — Task Board

- **Status:** Initial plan, v0.1
- **Last reviewed:** 2026-10-12

Legend: `[ ]` not started, `[x]` complete and verified, `[!]` blocked or requires a decision. Do not mark a task complete without evidence.

## Milestone 0 — Repository and environment
- [ ] Inspect repository and record current `git status`.
- [ ] Confirm XAMPP Apache and MySQL start successfully.
- [ ] Record PHP and MySQL/MariaDB versions.
- [ ] Confirm repository root and `Docs/` path.
- [ ] Add/review `.gitignore` for secrets, uploads, logs, caches and local configuration.
- [ ] Create README with verified setup instructions only.
- [ ] Decide whether Apache will serve `public/` as document root or use a reviewed temporary local arrangement.

## Milestone 1 — Application skeleton
- [ ] Create directory structure from `ARCHITECTURE.md`.
- [ ] Add front controller/router and basic error handling.
- [ ] Add configuration loader; never commit real secrets.
- [ ] Verify local home page and a not-found route.
- [ ] Add initial smoke test/checklist.

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
- [!] Apache document-root approach.
- [!] Exact installed PHP/MySQL versions.

## Current next action
Inspect the existing repository and environment without modifying or deleting files. Then report findings and resolve the blockers before implementation.
