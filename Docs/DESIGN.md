# ScamShield — UI and Experience Design

- **Status:** Implemented v1.0 (Stitch-approved design system)
- **Last reviewed:** 2026-10-13

## 1. Design objective

Authoritative, high-density forensic verification interface for civic anti-scam utility.
Tone: blunt, objective, civic, analytically rigorous.
Aesthetic: Structural Utility — ruled grids, hairline borders, flat depth. No drop shadows, no gradients, no pill-style radii.

## 2. Source of truth

`design/stitch/design/DESIGN.md` is the canonical visual reference (Stitch-exported, October 2026).
The three exported screens map directly to implemented PHP views:

| Stitch screen folder | PHP view |
|---|---|
| `scamshield_civic_scam_checking_utility` | `app/Views/pages/home.php` |
| `scamshield_heuristic_analyzer` | `app/Views/pages/analyzer.php` |
| `scamshield_link_analysis_result_specimen_link_8841_in` | `app/Views/analysis/url_result.php` |

`design/stitch/` is kept as a reference; it is not served publicly.

## 3. Color palette (implemented)

All tokens are CSS custom properties in `public/assets/css/tokens.css`.

| Role | Hex | Primary token |
|---|---|---|
| Safety Orange — threat signals, primary CTA | `#FF742D` | `--primary-container` |
| Trust Blue — verified facts, focus ring | `#0A2391` | `--on-secondary-container` |
| Structural Charcoal — ink, framing | `#373737` | `--color-charcoal` |
| Canvas White — base inspection surface | `#FFFFFF` | `--color-canvas` |
| Orange tint 10% — threat highlight bg | `#FFF1EA` | `--color-orange-tint-10` |
| Blue tint 8% — verified indicator bg | `#EBEFFE` | `--color-blue-tint-8` |
| Charcoal tint 6% — code containers | `#F3F3F3` | `--color-charcoal-tint-6` |
| Hairline dividers | `#E2E2E2` | `--color-charcoal-tint-15` |

The old Bootstrap-era palette (`#1a2340`, `#1d4ed8`, `#d97706`) is fully replaced.

## 4. Typography (implemented)

| Scale | Font | Size | Weight |
|---|---|---|---|
| headline-xl | Space Grotesk | 44px (mob: 32px) | 700 |
| headline-lg | Space Grotesk | 32px (mob: 26px) | 600 |
| headline-md | Space Grotesk | 22px | 600 |
| headline-sm | Space Grotesk | 18px | 600 |
| body-lg/md/sm | IBM Plex Sans | 16/14/12px | 400 |
| code-lg/md/sm | IBM Plex Mono | 15/13/11px | 500 |

IBM Plex Mono is used for all telemetry: URLs, tokens, audit log, UPI strings, hashes.
Fonts loaded via Google Fonts `@import` in `base.css` — no CDN framework, no build step.

## 5. CSS architecture (implemented)

```
public/assets/css/
├── tokens.css      ← all CSS custom properties
├── base.css        ← reset, typography, focus ring, grid, utilities
└── components.css  ← buttons, badges, cards, inputs, risk meter, findings table, etc.
```

## 6. Accessibility rules (implemented)

- Risk level conveyed by **text label + shape icon + position** — never color alone.
  Icons: ○ Low · ◇ Moderate · ▲ High · ⬡ Critical.
- Focus ring: `2px solid #0A2391` with `2px` white offset — visible on every background.
- All inputs have `<label>` or `aria-label`. Heading order is maintained per page.
- Skip-to-main-content link in layout. All external links have `rel="noopener noreferrer"`.

## 7. Layout (implemented)

- Max canvas: 1200px. Desktop: 12-column grid. Mobile: single column below 900px.
- Primary result split: 8-col investigation pane + 4-col sticky ledger.
- Spacing: 4px/8px modular scale (`--space-xs` → `--space-xl`).
- Corner radii: 2px (chips/tags) and 4px (panels/inputs) only.

## 8. Implemented view structure

```
app/Views/
├── layouts/main.php            ← warning strip, CSRF meta, sticky nav, flash messages, footer
├── partials/
│   ├── warning-strip.php       ← persistent non-dismissible safety banner
│   ├── nav.php                 ← auth-aware: login/register ↔ username + logout + admin
│   ├── footer.php              ← disclaimer, 1930 hotline, footer links
│   ├── url-inspector-form.php  ← tabbed SMS/URL/UPI POST /analyze form
│   ├── risk-meter.php          ← 4-tier geometric risk tier indicator
│   ├── annotated-url.php       ← monospace URL anatomy token breakdown
│   ├── findings-list.php       ← numbered findings + rule audit log table
│   ├── next-steps.php          ← triage checklist, helplines, verified channels
│   └── limitations-box.php     ← airgap proof + local-rules disclaimer
├── pages/
│   ├── home.php                ← hero inspector, RBI band, capability split, emergency steps
│   └── analyzer.php            ← full analyzer form + active scam pattern registry table
├── analysis/
│   └── url_result.php          ← result page wired to UrlAnalysisService output
├── auth/
│   ├── register.php            ← CSRF-protected registration form
│   └── login.php               ← CSRF-protected login form
├── incidents/
│   ├── index.php               ← user's incident list
│   ├── create.php              ← incident creation form (CSRF protected)
│   └── show.php                ← detail + evidence upload + status history
└── admin/
    ├── queue.php               ← all incidents with status filter tabs
    ├── detail.php              ← admin review + status change form (CSRF protected)
    └── audit.php               ← append-only audit log viewer
```

## 9. UX copy rules

- "Potential indicators detected" — never "confirmed scam."
- "No rules matched" ≠ safe — state explicitly: "This does not mean the link is safe."
- Platform incident reference ≠ official police complaint — stated in every incident view.
- 1930 and cybercrime.gov.in appear in every relevant context.

## 10. Not yet implemented (planned)

- Analysis history page (`/analyses`) — DB schema and repository exist; route not wired.
- Pattern Registry page — nav link present but disabled.
- Account settings / password change.
- Pagination for incident list and admin queue.
- Threat-intelligence adapter (see DECISION.md ADR-008 — unresolved).
