# ScamShield — UI and Experience Design

- **Status:** Initial baseline, v0.1
- **Last reviewed:** 2026-10-12

## 1. Design objective

Create a trustworthy, calm, accessible security interface. The product should help users understand the next safe action, not frighten them with dramatic colors or imply certainty that the system does not possess.

## 2. Visual direction

- Use a clean, modern, responsive layout with generous spacing.
- Use a neutral light theme first; dark theme can follow after usability is stable.
- Use a restrained security palette: dark navy/charcoal for text, blue for primary actions, amber for caution, red for high-risk warnings, and green only for positive/verified states.
- Do not use color as the only risk signal; pair colors with labels and icons.
- Avoid fake counters, fabricated detection statistics, fake trust badges, and invented testimonials.
- Use Bootstrap components where they help consistency, but customize branding and spacing.

## 3. Information architecture

### Public
- Home / overview
- How analysis works
- Safety and limitations
- Login / registration
- Privacy notice

### Signed-in user
- Dashboard
- Analyze URL
- Analyze message
- Analysis history
- Create incident report
- My reports / report detail
- Evidence management
- Account settings / logout

### Administrator
- Admin overview
- Report queue
- Report detail and evidence access
- Status-change workflow
- Audit log view

All admin pages and actions must be protected by server-side authorization.

## 4. Core analysis-result layout

1. **Result heading:** “Potentially suspicious” or “No known indicator found” rather than “Definitely a scam” / “100% safe.”
2. **Risk band:** Low / Medium / High, with text and icon.
3. **Confidence/limitations note:** Explain that this is a preliminary assessment and can be wrong.
4. **Reasons:** List each triggered rule or source match, in plain language.
5. **Recommended action:** Context-specific next steps, such as not opening the link, not sharing credentials, contacting the organization through a known official channel, or contacting the bank immediately if money was sent.
6. **Source status:** Show whether threat-intelligence checking succeeded, found a match, returned no match, or was unavailable.
7. **Actions:** Re-analyze, save result (if signed in), or create an incident report.

## 5. UX copy rules

Prefer:
- “Potential phishing indicators detected.”
- “No match was found in the sources checked. This does not prove the URL is safe.”
- “External threat check unavailable; showing local heuristic results only.”
- “This is a ScamShield report, not an official police complaint.”

Avoid:
- “This person is a scammer.”
- “Guaranteed safe.”
- “AI proved this is fraud.”
- “Police have been notified” unless a verified integration actually confirms this.

## 6. Forms and validation
- Label every input.
- Explain expected formats and why sensitive information should not be entered.
- Preserve user input after validation errors when safe.
- Provide clear, accessible error messages.
- Confirm before destructive actions.
- Do not request OTPs, passwords, UPI PINs or account recovery codes.

## 7. Responsive and accessibility requirements
- Support mobile, tablet and desktop layouts.
- Use semantic headings, labels, landmarks and buttons.
- Support keyboard navigation and visible focus.
- Maintain readable contrast and scalable text.
- Announce validation/status messages accessibly.
- Do not rely only on red/green to communicate risk.

## 8. Evidence and privacy UX
- Display allowed file types and size limits before upload.
- Warn users to avoid uploading unrelated identity documents or secrets.
- Show upload progress and success/failure accurately.
- Make who can view evidence clear.
- Do not expose direct public file URLs.
- Provide a deletion/retention explanation once retention policy is decided.

## 9. Initial page priorities
1. Home and product explanation.
2. URL analyzer.
3. Message analyzer.
4. Result detail.
5. Authentication.
6. User dashboard and analysis history.
7. Incident submission and report tracking.
8. Admin queue and report review.

Build accessible, functional workflows before decorative animation.
