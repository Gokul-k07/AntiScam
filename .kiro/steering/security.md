---
inclusion: auto
---

# ScamShield — Security Rules (Binding)

These rules are binding for all contributors and AI coding agents. If a task conflicts with any rule, explain the conflict and ask before proceeding.

## Non-negotiable rules
1. PDO prepared statements for every user-controlled value. Never concatenate user input into SQL.
2. `password_hash()` for storage; `password_verify()` for checking. Never store plaintext passwords.
3. Regenerate session ID with `session_regenerate_id(true)` after login and any privilege change.
4. CSRF token on every state-changing form (POST/PUT/DELETE). Validate server-side.
5. Enforce authorization server-side on every private object — not by hiding UI controls.
6. Validate all input server-side. Client-side validation is a usability aid only.
7. Escape output for its context: `htmlspecialchars()` for HTML, proper encoding for attributes and URLs.
8. Keep credentials and secrets outside Git. `.env` is gitignored. `.env.example` contains placeholders only.
9. Do not expose stack traces, SQL errors, filesystem paths, tokens, or secrets to users.
10. Do not fetch arbitrary user-submitted URLs from the PHP server (SSRF risk).
11. Store evidence files outside the public web root. Authorize every download server-side.
12. Use an allowlist for file uploads: validate extension, MIME type (server-side via fileinfo), file signature, and size. Use random server-generated storage filenames.
13. Do not execute or render uploaded content inline.
14. Prevent path traversal and overwrite attacks on all file operations.
15. Use least-privilege database accounts (not root) outside local dev.
16. "No threat-feed match" means only no match was found in that source — never "safe."

## Anti-hallucination rules
1. Never claim a feature is implemented, tested, secure, or deployed without evidence.
2. Never invent API keys, endpoint behavior, test results, threat-feed coverage, or official integrations.
3. If uncertain, state what must be inspected or tested. Do not guess.
4. Do not label a person or account a criminal based only on a score or user allegation.

## Before coding
- Inspect the relevant files and `git status` before making any change.
- Identify assumptions and potentially destructive operations.
- Propose a small plan.

## After coding
- Run relevant tests and report exact commands and results; if tests cannot run, explain why.
- Summarize files changed, behavior implemented, tests run, and remaining risks.

Full rules document:

#[[file:Docs/RULES.md]]
