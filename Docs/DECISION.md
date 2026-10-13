# ScamShield — Architecture Decision Records (ADR)

- **Status:** Initial decisions, v0.1
- **Last reviewed:** 2026-10-12

Record decisions that materially affect architecture, security, product scope, or maintenance. Do not add an ADR for every minor code change. If a decision is superseded, keep the old record and add a new one.

## ADR-001 — Start as a PHP/MySQL web application

- **Status:** Accepted
- **Context:** The project must satisfy a PHP + MySQL learning/project requirement and should be feasible to build and test locally.
- **Decision:** Build a local-first web application using PHP 8+, MySQL Community Edition through XAMPP, and HTML/CSS/JavaScript/Bootstrap.
- **Consequences:** The first version can analyze user-submitted data but cannot automatically inspect all device activity. Browser/mobile clients are later projects.
- **Revisit when:** The web MVP is tested and the need for device-level protection is clearly defined.

## ADR-002 — Deterministic rules before external AI

- **Status:** Accepted
- **Context:** Paid APIs should not be a requirement, and initial detection needs to be transparent and testable.
- **Decision:** Implement a versioned PHP rule engine for URL and message indicators first. Keep any future AI provider behind a separate interface.
- **Consequences:** The first analyzer is heuristic, not a sophisticated language model. Its performance must be measured against labeled examples. Rule weights and risk bands are provisional until evaluated.
- **Revisit when:** A useful labeled evaluation set exists and a candidate AI model can be assessed for quality, privacy, latency and cost.

## ADR-003 — Explain uncertainty; never equate absence of a match with safety

- **Status:** Accepted
- **Context:** Threat feeds are incomplete and heuristic checks can make mistakes.
- **Decision:** Results show the triggered indicators, source status, and limitations. “No match” is not “safe,” and no result proves a person is a criminal.
- **Consequences:** User-facing copy must avoid absolute guarantees. Store the ruleset version with each result.
- **Revisit when:** Risk bands are calibrated using a documented evaluation process; the core principle remains.

## ADR-004 — Do not fetch arbitrary submitted URLs from the PHP server

- **Status:** Accepted
- **Context:** Fetching a submitted URL can expose the server to SSRF, internal-network probing, redirect abuse and unsafe content.
- **Decision:** Initial URL analysis parses the URL locally and optionally checks a trusted threat-intelligence provider without navigating to the submitted destination. Any future fetching requires a separate security design and explicit approval.
- **Consequences:** Some signals such as live page content will not be available in the MVP.
- **Revisit when:** A concrete use case justifies remote retrieval and a hardened isolated fetch service is designed.

## ADR-005 — Private evidence storage with authorized downloads

- **Status:** Accepted
- **Context:** Reports may contain sensitive personal and financial evidence.
- **Decision:** Store evidence outside the public web root when possible; use random storage names and an authorization-checked download handler.
- **Consequences:** Direct static file links are not used. Evidence access needs tests and audit controls.
- **Revisit when:** The deployment platform imposes a different secure storage architecture.

## ADR-006 — Official reporting remains a separate process

- **Status:** Accepted
- **Context:** ScamShield is not a law-enforcement system, and no authorized integration has been established.
- **Decision:** Generate an incident summary and guide users to current official channels. Do not claim a complaint was submitted unless a supported integration confirms it.
- **Consequences:** A ScamShield case reference is explicitly distinct from an official complaint acknowledgement.
- **Revisit when:** An official, permitted integration is documented and its eligibility, security and legal requirements are verified.

## ADR-007 — No destructive repository operations without approval

- **Status:** Accepted
- **Context:** AI coding tools may overwrite files or reset Git history.
- **Decision:** Inspect `git status` and existing files before changes. Do not reset, delete, overwrite unrelated files, commit or push without appropriate user authorization.
- **Consequences:** Initial work begins with inspection and a plan, not blind scaffolding.
- **Revisit when:** No change required; this is a standing rule.

## ADR-008 — Threat intelligence must be evaluated before selection

- **Status:** Proposed
- **Context:** A community feed or API may have limitations, access conditions, coverage gaps or terms that do not fit the intended use.
- **Decision:** Do not hardwire a provider until its current official documentation, permitted use, update cadence, rate limits, and data-sharing implications are checked.
- **Consequences:** The analyzer must work in local-only mode and clearly show external-check availability.
- **Revisit when:** Provider evaluation is complete.

## Research note — OpenPhish is not yet selected

- **Status:** Pending provider evaluation
- **Finding:** OpenPhish lists a limited Community feed and links its terms of use. Its terms state that services are provided solely for personal use; therefore, the project must not assume that the community feed can be embedded in a public-facing application or redistributed.
- **Decision:** Keep the threat-intelligence adapter provider-neutral until current terms, permitted use, update frequency, rate limits, and privacy implications are verified for the intended use.
- **Sources:** https://openphish.com/phishing_feeds.html ; https://www.openphish.com/terms.html
- **Next action:** Review the current terms and, if necessary, select a source whose license explicitly permits the intended academic/demo use.

## ADR-009 — Document-root strategy: Option A (virtual host serving public/)

- **Status:** Accepted
- **Date:** 2026-10-12
- **Context:** XAMPP's default setup serves `C:\xampp\htdocs` at `localhost`, which would expose the entire `AntiScam\` repository tree (including `app/`, `config/`, `storage/`, `.env`) if accessed via `localhost/AntiScam/`. `Docs/ARCHITECTURE.md` recommends configuring Apache to serve only `AntiScam/public/` as the document root.
- **Decision:** Add two virtual host blocks to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:
  1. A `localhost *:80` catch-all that serves `C:/xampp/htdocs` with `Options -Indexes`, preventing directory listing.
  2. A `scamshield.local *:80` vhost whose `DocumentRoot` is `C:/xampp/htdocs/AntiScam/public` with `AllowOverride All` (for `.htaccess` rewrite support) and `Options -Indexes`.
  Add `127.0.0.1 scamshield.local` to the Windows hosts file.
  All application code, configuration, and storage remain in `app/`, `config/`, `database/`, and `storage/` — none of which are under `public/`.
- **Consequences:**
  - `http://scamshield.local/` serves only `public/index.php` and `public/assets/`.
  - `app/`, `config/`, `database/`, `storage/`, `.env`, and `Docs/` are unreachable over HTTP.
  - Apache must be restarted after the vhost config change.
  - The Windows hosts entry requires Administrator elevation and must be added manually or via an elevated shell.
  - `public/.htaccess` routes all non-file/non-directory requests through `public/index.php`.
- **Revisit when:** The project moves to a hosted server; at that point the server's own virtual host or config replaces this local arrangement.
