# ScamShield — Product Requirements Document (PRD)

- **Status:** v1.1 — updated after full MVP implementation
- **Last reviewed:** 2026-10-14

## 1. Product summary

ScamShield is a locally hosted web-based digital-safety assistant. It helps users in India assess suspicious URLs, SMS/messages, and UPI payment requests by applying deterministic heuristic rules and — when configured — consulting VirusTotal's external reputation database. Users can create incident reports, upload evidence, and receive guidance toward official reporting channels.

ScamShield is not a law-enforcement system and does not determine whether a specific person or account is guilty of a crime.

**Implementation stack:** PHP 8.2, MariaDB 10.4, Apache 2.4 (XAMPP), custom HTML/CSS/JS (no Bootstrap CDN), no Composer packages, Git/GitHub.

## 2. Problem

People receive deceptive messages, phishing links, impersonation attempts, suspicious UPI payment requests, and scam calls. They often lack a quick, understandable way to assess warning signs and preserve the information needed to report an incident through official channels.

## 3. Product goals

1. Let a user submit a URL, SMS/message text, or UPI string and receive an explainable preliminary risk assessment.
2. Show the concrete indicators that contributed to the result, along with regulatory references and limitations.
3. Let authenticated users create and manage incident reports with private evidence.
4. Provide an admin review workflow with audit-logged status changes.
5. Guide users toward verified official reporting routes without claiming reports have been filed.
6. Keep the system usable without paid AI APIs; external reputation checks are optional.

## 4. Non-goals for the initial release

- Monitoring activity on a user's phone or computer automatically.
- Reading private messages, call audio, or other application data automatically.
- Guaranteeing detection of all scams or phishing sites.
- Claiming a person, phone number, URL, or UPI ID is criminal based solely on a heuristic score.
- Replacing official cybercrime reporting portals, banks, or law enforcement.
- Automatic submission of official complaints (no authorized integration exists).
- Training or hosting a large language model.
- QR-image decoding (text input only for UPI in the MVP).

## 5. Users and roles

| Role | Capabilities |
|---|---|
| **Visitor** | Read public pages, use the analyzer (anonymous analysis currently persists with user_id=NULL — final policy pending) |
| **Registered user** | All of the above + create/manage own incident reports, upload evidence, view analysis history |
| **Administrator** | All of the above + view all incidents, change status with a recorded reason, view audit log |

## 6. Implemented functional requirements

### FR-01: URL analysis (implemented)
- Accepts a URL string up to 4000 characters.
- Parses via PHP's `parse_url()` — no outbound fetch of the target URL.
- Applies 8 deterministic heuristic rules covering transport, host type, brand impersonation, TLD risk, query parameters, APK delivery, and domain structure.
- Optionally queries VirusTotal v3 `GET /api/v3/urls/{id}` to retrieve an existing report.
- Returns risk band (low/moderate/high/critical), score (0–100), findings with regulatory references, and — if VT is configured — engine counts as external reputation evidence.
- Stores result in `analyses` table with `threat_status` reflecting the VT outcome.
- VT results are clearly labelled as external evidence, not ScamShield's verdict.
- A URL not found in VT is never declared safe.

### FR-02: SMS / message text analysis (implemented)
- Accepts pasted text up to 4000 characters.
- Applies 7 deterministic rules: coercive urgency, PSB brand impersonation, KYC/credential harvest demand, embedded URL (analysed recursively at 50% weight), DLT non-compliance (mobile sender), digital/courier arrest patterns, job-scam patterns.
- SMS text is never sent to VirusTotal or any external API.
- Returns matched indicators, risk band, and safe next steps.

### FR-03: UPI ID / payment string analysis (implemented)
- Accepts UPI VPAs, payment strings, and message text relating to UPI.
- Applies 3 rules: PIN-to-receive reversal trap, VPA brand impersonation, coercive urgency.
- Does not attempt to decode QR images (text input only).
- Does not verify that a VPA is registered with NPCI — heuristic match only.
- UPI text is never sent to VirusTotal or any external API.

### FR-04: Accounts and sessions (implemented)
- Registration with email/password; `password_hash(PASSWORD_BCRYPT, cost:12)`.
- Login with session regeneration; secure session cookies (HttpOnly, SameSite=Lax, custom name).
- CSRF tokens on all state-changing forms, validated server-side.
- Server-side authorization on every private resource.
- Roles: `user` (default) and `admin` (set directly in DB).

### FR-05: Incident reports (implemented)
- Incident types: phishing_url, phishing_sms, upi_fraud, impersonation, digital_arrest, job_scam, investment_scam, other.
- Fields: incident_type, incident_at (optional past date), description (20–5000 chars), channel (scammer contact), optional link to a prior analysis.
- Public reference format: `SS-YYYY-XXXXXX`.
- Status flow: submitted → under_review → info_needed ↔ under_review → closed.
- Every view states explicitly: "This is a ScamShield incident report, not an official police complaint."

### FR-06: Evidence (implemented)
- Allowed types: jpg/jpeg, png, gif, pdf, txt, csv.
- Maximum file size: 10 MB.
- Server-side MIME detection via PHP `fileinfo` extension.
- Random storage key (hex-encoded random bytes); original filename stored for display only.
- Files stored at `STORAGE_EVIDENCE_PATH` outside the web root.
- SHA-256 hash of each file stored in the database.
- Every download is authorization-checked against incident ownership.
- No execution or inline rendering of uploaded content.

### FR-07: Admin review and audit (implemented)
- Admin routes protected by `AuthMiddleware::requireAdmin()` (server-side role check).
- Status changes require an explicit reason; history row written and audit entry created.
- `audit_logs` table is append-only at application level.
- Audit log viewer at `/admin/audit`.

### FR-08: Safety guidance (implemented)
- "No rules matched" is never described as "safe."
- Limitations box shown on every analysis result page.
- 1930 (national cybercrime helpline) and cybercrime.gov.in appear in every relevant context.
- Every incident view carries the "not an official complaint" disclaimer.

## 7. Open decisions (not yet resolved)

| Decision | Impact |
|---|---|
| Anonymous analysis policy | Whether unauthenticated analyses are retained with user_id=NULL |
| Evidence retention/deletion policy | How long evidence files and incident records are kept |
| Analysis history page `/analyses` | DB and repository ready; route not wired |
| Account settings / password change | Not yet implemented |

## 8. Non-functional requirements (verified)

- **Security:** PDO prepared statements, bcrypt passwords, CSRF, session security, server-side authorization, output escaping, secure uploads. See `Docs/RULES.md`.
- **Privacy:** Input excerpts capped at 500 chars in DB; full message bodies not retained by default; evidence access controlled.
- **Reliability:** All VT errors handled as value objects; heuristic analysis always runs regardless of VT availability.
- **Explainability:** Every finding shows trigger ID, tag, title, body, weight, severity, and regulatory/advisory reference.
- **Maintainability:** Custom MVC with clear separation; no framework lock-in.
- **Accessibility:** Semantic HTML, keyboard-navigable, risk level conveyed by text+shape+position (not color alone), visible focus ring.

## 9. Roadmap

### Completed (MVP — 2026-10-14)
- Repository, environment, configuration, document-root strategy
- Database schema and setup script
- Authentication and role authorization
- Rule-based URL, SMS, and UPI analysis with explainable findings
- VirusTotal v3 external reputation integration (optional, configurable)
- Incident reports, evidence upload/download, status tracking
- Admin review dashboard and append-only audit trail
- Custom UI implemented as semantic PHP views (tokens → base → components CSS)
- 45/45 automated offline tests pass

### Planned (post-MVP)
- Analysis history page (`/analyses`) — repository ready, route not wired
- Account settings / password change
- Pagination for incident list and admin queue
- Integration test with live VirusTotal API key
- Additional threat-intelligence providers (URLhaus, Google Safe Browsing) — currently unimplemented
- REST API
- Browser extension and Android companion
- Carefully evaluated public hosting

## 10. Key risks

- False positives and false negatives in heuristic detection.
- VirusTotal public API quota (500/day, 4/min) may be reached under heavy use.
- VT may not have a report for a freshly registered phishing domain.
- Sensitive incident reports may expose victims to additional harm if access controls fail.
- Public hosting of a student prototype creates privacy and abuse risks; not yet deployed publicly.
