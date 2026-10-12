# ScamShield — Product Requirements Document (PRD)

- **Status:** Initial baseline, v0.1
- **Last reviewed:** 2026-10-12
- **Project root:** `C:\xampp\htdocs\AntiScam\`
- **Documentation root:** `C:\xampp\htdocs\AntiScam\Docs\`
- **Implementation stack:** PHP 8+, MySQL Community Edition, HTML/CSS/JavaScript, Bootstrap, XAMPP, Git/GitHub, Antigravity

## 1. Product summary

ScamShield is a web-based digital-safety assistant that helps people assess suspicious URLs and messages, understand the indicators behind an assessment, organize evidence, and prepare for appropriate reporting. It is not a law-enforcement system and does not determine guilt.

The first release is a locally hosted PHP/MySQL application. It will provide user-facing analysis and incident-management workflows before considering browser or mobile companions.

## 2. Problem

People receive deceptive messages, phishing links, impersonation attempts, suspicious payment requests, and scam calls. They often lack a quick, understandable way to assess warning signs and preserve the information needed to report an incident.

## 3. Product goals

1. Let a user submit a URL or text for an explainable preliminary risk assessment.
2. Show the concrete indicators that contributed to the result and the limitations of the assessment.
3. Let authenticated users create and manage incident reports and evidence metadata.
4. Provide an admin review workflow for reports submitted to this platform.
5. Maintain secure access controls, auditable status changes, and privacy-conscious data handling.
6. Keep the initial implementation usable without paid AI APIs.

## 4. Non-goals for the initial release

- Monitoring all activity on a phone or computer.
- Reading private messages, call audio, WhatsApp content, or other applications automatically.
- Guaranteeing detection of all scams or phishing sites.
- Claiming a person, phone number, URL, or UPI ID is criminal based solely on a heuristic score.
- Replacing official cybercrime reporting portals, banks, or law enforcement.
- Automatically submitting official complaints unless a supported, authorized integration is separately verified.
- Training or hosting a large language model as a prerequisite for the MVP.

## 5. Users and roles

### Visitor
- Read the product explanation and safety guidance.
- Submit a limited URL/message analysis if anonymous analysis is enabled by a later decision.

### Registered user
- Analyze URLs and messages.
- Review previous analyses.
- Create incident reports.
- Upload permitted evidence files.
- View and update their own report details where allowed.
- Receive guidance to appropriate official reporting routes.

### Administrator / platform reviewer
- View reports within assigned permissions.
- Change report status with a recorded reason.
- Review evidence metadata and incident details.
- View audit history.
- Cannot represent themselves as police or make legally conclusive determinations.

## 6. MVP functional requirements

### FR-01: URL analysis
- Accept a URL string.
- Normalize safely for analysis without silently changing its meaning.
- Validate parsing and supported schemes; reject malformed input.
- Extract explainable indicators such as suspicious URL structure, misleading host/path patterns, IP-literal hosts, punycode/IDN indicators where detectable, unusual ports, and suspicious terms.
- Optionally check a configured threat-intelligence source when the source is available and its use is permitted.
- Return a risk category, score or band, indicators, recommendations, analysis timestamp, and source/check status.
- Never fetch arbitrary submitted URLs from the server in the MVP. This avoids turning the analyzer into an SSRF or network-scanning service.

### FR-02: Message analysis
- Accept pasted text such as SMS, email, or chat content.
- Detect rule-based indicators: urgency/threats, requests for OTP/password/PIN, payment demands, impersonation language, suspicious links, APK installation requests, and requests to move to unusual channels.
- Return matched indicators and safe next steps.
- Do not claim that keywords alone prove fraud.

### FR-03: Risk assessment
- Use documented rules and weights stored in code/configuration.
- Keep each triggered rule, weight, and explanation available for the result.
- Bound and normalize scores to a defined range.
- Use provisional Low / Medium / High bands until validation data supports better calibration.
- Distinguish heuristic signals from confirmed threat-intelligence matches.

### FR-04: Accounts and sessions
- Register, log in, log out, and manage sessions securely.
- Store passwords using PHP's password hashing APIs.
- Use prepared statements for database operations.
- Enforce authorization server-side on every private resource.
- Protect state-changing forms against CSRF.

### FR-05: Incident reports
- Capture incident type, approximate time, narrative, contact/channel details the user chooses to provide, and transaction details only when necessary.
- Generate a reference ID.
- Support status tracking, for example: Submitted, Under Review, More Information Needed, Closed.
- Make clear that this is a report inside ScamShield, not an official police complaint.

### FR-06: Evidence
- Allow a small, explicitly permitted set of evidence types.
- Validate size, extension, MIME type and file signature where feasible.
- Generate server-side random filenames.
- Store uploaded files outside the public web root when possible.
- Require authorization to download evidence.
- Never execute uploaded content.
- Record metadata and access events without logging sensitive file contents.

### FR-07: Admin review and audit
- Restrict admin functionality by server-side role checks.
- Record meaningful report status changes and privileged actions.
- Keep audit records append-only at application level; protect them from ordinary user modification.
- Avoid collecting unnecessary personal data.

### FR-08: Safety guidance
- Explain that risk results are estimates.
- For suspected ongoing financial fraud in India, direct users to verified official reporting channels and emergency guidance.
- Tell users not to share OTPs, passwords, UPI PINs, or remote-access codes with the platform.

## 7. Non-functional requirements

- **Security:** follow current secure coding guidance; validate inputs and encode output.
- **Privacy:** data minimization, clear retention policy, restricted evidence access.
- **Reliability:** predictable errors; no raw stack traces or credentials shown to users.
- **Explainability:** every risk result should show the reason for its signals.
- **Maintainability:** separate request handling, domain logic, persistence, and templates.
- **Accessibility:** semantic HTML, keyboard operation, visible focus, understandable error messages.
- **Performance:** local analysis should be responsive for ordinary-sized inputs; impose input and upload limits.
- **Portability:** run on the installed XAMPP/PHP/MySQL setup without requiring paid services.

## 8. Success criteria / acceptance tests

1. Valid and invalid URL inputs are handled safely and deterministically.
2. Message-analysis examples trigger the expected rules and explain each match.
3. The same input and rules version produce the same deterministic score.
4. Unknown URLs are not automatically declared safe.
5. A user cannot view or download another user's private report/evidence by changing an ID.
6. SQL injection payloads do not bypass authentication or alter queries.
7. CSRF attempts on state-changing endpoints are rejected.
8. Upload tests reject disallowed extensions, oversized files, MIME mismatches, and executable content.
9. Admin-only endpoints reject ordinary users.
10. Status changes produce an audit entry.
11. The interface distinguishes platform case tracking from official reporting.
12. Tests and known limitations are documented before a release is called complete.

## 9. Roadmap

### MVP
- PHP application skeleton and configuration
- Database schema and migrations/setup script
- Authentication and role authorization
- Rule-based URL and message analysis
- Analysis history
- Incident reports and secure evidence handling
- Admin review dashboard and audit trail
- Automated/manual security tests

### Later, only after the MVP is reliable
- Verified threat-intelligence integration
- Optional AI text analysis behind a provider interface
- Email notifications with consent and secure configuration
- REST API
- Browser extension and Android companion
- Carefully evaluated hosting and deployment

## 10. Key risks

- False positives and false negatives in heuristic detection.
- Threat feeds may have coverage, licensing, freshness, or availability limits.
- Sensitive incident reports and evidence may expose victims to additional harm if access controls fail.
- Server-side URL fetching can introduce SSRF and must not be added casually.
- Public hosting of a student prototype may create privacy and abuse risks.
- AI output can be inaccurate and must not override deterministic evidence or be presented as proof.

## 11. Open decisions

- Should URL/message analysis require an account, or permit rate-limited anonymous use?
- Which exact file types and maximum sizes should evidence uploads support?
- What data-retention period is appropriate for analyses, reports, and evidence?
- Which threat-intelligence source and terms are suitable for a student project?
- What PHP version and MySQL/MariaDB versions are installed in XAMPP?
- Will the MVP run only locally, or will it later be deployed publicly?

## External research references

The following public sources informed the security and reporting requirements. These are design references, not proof that the application is compliant or secure.

- OWASP SQL Injection Prevention Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html — use parameterized queries and least privilege.
- OWASP Session Management Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html — protect session identifiers and configure cookie/session behavior carefully.
- OWASP Secure Code Review Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Secure_Code_Review_Cheat_Sheet.html — server-side validation, contextual output encoding, upload checks, and safe error messages.
- PHP PDO prepared statements: https://www.php.net/manual/en/pdo.prepared-statements.php — bind user values rather than concatenating them into SQL.
- PHP `password_hash`: https://www.php.net/manual/en/function.password-hash.php — use the platform's password hashing API.
- Indian National Cyber Crime Reporting Portal, Report Suspect: https://www.cybercrime.gov.in/Webform/cyber_suspect.aspx — official suspect-reporting route for supported identifiers and evidence.
- Indian National Cyber Crime Reporting Portal: https://www.cybercrime.gov.in/ — official reporting guidance; current portal guidance identifies 1930 for immediate reporting of financial cyber fraud.
- OpenPhish feeds: https://openphish.com/phishing_feeds.html and terms: https://www.openphish.com/terms.html — evaluate current feed access and terms before integration. The community feed is described as limited and subject to terms; do not assume unrestricted redistribution or use in a public service.

Review these sources again before deployment because provider terms and official guidance can change.
