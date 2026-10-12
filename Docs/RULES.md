# ScamShield — Engineering and Safety Rules

- **Status:** Initial baseline, v0.1
- **Last reviewed:** 2026-10-12

These rules are binding project constraints for human contributors and AI coding agents. If a task conflicts with these rules, explain the conflict and ask before proceeding.

## 1. Truthfulness and evidence
1. Never claim a feature is implemented, tested, secure, or deployed unless there is evidence.
2. Distinguish facts from assumptions, estimates, heuristics, and external-source matches.
3. Do not invent API keys, endpoint behavior, threat-feed coverage, test results, or official integrations.
4. If uncertain, state what must be inspected or tested.
5. Do not label a person or account a criminal based only on a score or user allegation.

## 2. Development workflow
1. Inspect the current files and `git status` before making changes.
2. Preserve existing user work; do not reset, delete, or overwrite unrelated files.
3. Make small, coherent changes and explain affected files.
4. Run relevant tests and report exact commands/results; if tests cannot run, say why.
5. Do not commit or push without the user's instruction.
6. Update `TASKS.md`, `MEMORY.md`, or `DECISION.md` only when the change is meaningful, not for every trivial edit.
7. Keep documentation consistent with implemented behavior. Mark planned features as planned.

## 3. PHP and database security
1. Use PDO prepared statements for user-controlled values.
2. Never concatenate user input into SQL.
3. Hash passwords with `password_hash()` and verify with `password_verify()`.
4. Regenerate the session ID after authentication and privilege changes.
5. Set session cookie flags appropriately; use `Secure` when HTTPS is in use.
6. Protect state-changing requests with CSRF tokens.
7. Enforce authorization server-side for every private object, not just by hiding UI controls.
8. Validate input on the server; client-side validation is only a usability aid.
9. Escape output for its context, especially HTML, attributes and URLs.
10. Use least privilege for database accounts outside disposable local development.
11. Keep credentials outside Git. `.env.example` must contain placeholders only.
12. Do not expose stack traces, SQL errors, filesystem paths, tokens or secrets to users.

## 4. URL-analysis safety
1. Do not visit or fetch arbitrary user-submitted URLs from the PHP server by default.
2. Parse submitted URLs as data; validate scheme and host and reject unsupported schemes.
3. Avoid unsafe redirects and do not make the analyzer an open proxy or URL scanner.
4. A URL not found in a threat feed is **unknown/not matched**, not confirmed safe.
5. Treat feed results as source-specific and time-sensitive.
6. Explain heuristic indicators and uncertainty in the UI.
7. Avoid logging complete URLs when query strings may contain personal tokens.

## 5. Message-analysis safety
1. Treat the rule engine as heuristic, not as a genuine language model.
2. Do not use a single keyword as definitive proof.
3. Show which rules triggered and why.
4. Bound input length and handle Unicode safely.
5. Never ask users to submit OTPs, passwords, PINs, recovery codes, or remote-access credentials.
6. If external AI is introduced, document what content is sent to the provider, consent, retention, cost/limits and failure behavior first.

## 6. Upload and evidence safety
1. Use an allowlist of accepted formats and size limits.
2. Do not trust filenames, extensions, browser MIME values or client-provided paths.
3. Verify MIME type server-side and use random storage names.
4. Store evidence outside the public document root where possible.
5. Do not execute or render uploaded HTML/SVG/script content inline.
6. Require authorization for each evidence download.
7. Prevent path traversal and overwrite attacks.
8. Redact sensitive evidence details from logs.
9. Define retention and deletion behavior before public launch.

## 7. Privacy and responsible product language
1. Collect the minimum data needed for the chosen feature.
2. Explain what is stored and why.
3. Avoid public scammer lists or public exposure of unverified allegations in the MVP.
4. Distinguish a ScamShield incident report from an official complaint.
5. Provide verified official reporting guidance, but do not claim a report has been filed unless confirmation exists.
6. Do not guarantee prevention, detection, recovery of money, or law-enforcement action.

## 8. AI-agent operating instructions
Before coding:
- Inspect the relevant files and repository state.
- Identify assumptions and possible destructive operations.
- Propose a small implementation plan.

While coding:
- Follow the existing structure and these rules.
- Do not add dependencies without explaining why.
- Do not put generated source code in `Docs/` unless it is documentation.
- Do not add an external service before checking its current documentation and terms.

After coding:
- Run the most relevant tests or clearly state why they could not run.
- Summarize files changed, behavior implemented, tests and remaining risks.
- Recommend documentation updates only for meaningful changes.

## 9. Research basis (re-check before release)

- OWASP SQL Injection Prevention: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html
- OWASP Session Management: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html
- OWASP Secure Code Review: https://cheatsheetseries.owasp.org/cheatsheets/Secure_Code_Review_Cheat_Sheet.html
- PHP PDO prepared statements: https://www.php.net/manual/en/pdo.prepared-statements.php
- PHP password hashing: https://www.php.net/manual/en/function.password-hash.php
- OpenPhish feed information and terms: https://openphish.com/phishing_feeds.html and https://www.openphish.com/terms.html
- Official India cybercrime reporting: https://www.cybercrime.gov.in/

These are baseline references, not a claim of compliance. Re-check the current documents when implementing the relevant feature.
