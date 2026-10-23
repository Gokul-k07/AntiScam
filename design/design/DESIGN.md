---
name: Public Cyber Utility
colors:
  surface: '#fbf9f8'
  surface-dim: '#dcd9d9'
  surface-bright: '#fbf9f8'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f6f3f2'
  surface-container: '#f0eded'
  surface-container-high: '#eae8e7'
  surface-container-highest: '#e4e2e1'
  on-surface: '#1b1c1c'
  on-surface-variant: '#594238'
  inverse-surface: '#303030'
  inverse-on-surface: '#f3f0f0'
  outline: '#8c7166'
  outline-variant: '#e0c0b3'
  surface-tint: '#a33e00'
  primary: '#a33e00'
  on-primary: '#ffffff'
  primary-container: '#ff742d'
  on-primary-container: '#5f2100'
  inverse-primary: '#ffb596'
  secondary: '#4254be'
  on-secondary: '#ffffff'
  secondary-container: '#8193ff'
  on-secondary-container: '#0a2391'
  tertiary: '#006686'
  on-tertiary: '#ffffff'
  tertiary-container: '#00aadc'
  on-tertiary-container: '#003a4d'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdbcd'
  primary-fixed-dim: '#ffb596'
  on-primary-fixed: '#360f00'
  on-primary-fixed-variant: '#7c2e00'
  secondary-fixed: '#dee0ff'
  secondary-fixed-dim: '#bbc3ff'
  on-secondary-fixed: '#000f5c'
  on-secondary-fixed-variant: '#273ba5'
  tertiary-fixed: '#bfe8ff'
  tertiary-fixed-dim: '#6ed2ff'
  on-tertiary-fixed: '#001f2b'
  on-tertiary-fixed-variant: '#004d65'
  background: '#fbf9f8'
  on-background: '#1b1c1c'
  surface-variant: '#e4e2e1'
typography:
  headline-xl:
    fontFamily: Space Grotesk
    fontSize: 44px
    fontWeight: '700'
    lineHeight: 52px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: Space Grotesk
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Space Grotesk
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 38px
    letterSpacing: -0.02em
  headline-lg-mobile:
    fontFamily: Space Grotesk
    fontSize: 26px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Space Grotesk
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Space Grotesk
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: IBM Plex Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: IBM Plex Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: IBM Plex Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
  label-code-lg:
    fontFamily: IBM Plex Mono
    fontSize: 15px
    fontWeight: '500'
    lineHeight: 20px
  label-code-md:
    fontFamily: IBM Plex Mono
    fontSize: 13px
    fontWeight: '500'
    lineHeight: 18px
  label-code-sm:
    fontFamily: IBM Plex Mono
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  gutter: 1rem
  gutter-desktop: 1.5rem
  margin: 1rem
  margin-desktop: 2.5rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
---

## Brand & Style

This design system is engineered for high-density, forensic verification and civic utility. It operates as an authoritative digital watchdog against financial and telecommunication fraud across India, handling high-stakes workflows such as UPI handle verification, SMS spoofing triage, and deceptive phishing site teardowns.

The visual narrative draws from institutional forensic instruments and classical editorial broadsheets:
- **Tone:** Blunt, objective, civic, unadorned, and analytically rigorous.
- **Aesthetic Movement:** Structural Utility and High-Contrast Editorial Functionalism. Interface depth is achieved strictly through ruled grids, hairline data framing, and structured surface tensions rather than simulated lighting or organic curves.
- **Visual Tension:** Pure white inspection surfaces (#FFFFFF) framed by sharp, hairline charcoal dividers, punctured by aggressive Safety Orange (#FF742D) signals for acute danger and Deep Trust Blue (#0A2391) anchors for verified institutional facts.

## Colors

The palette rejects decorative hues in favor of precise operational signaling:

- **Primary (`#FF742D` — Safety Orange):** The active signal for threats, unverified flags, critical actions, and triage alerts.
  - Tint 10% (`#FFF1EA`): Threat item highlight backgrounds, high-risk status pills.
  - Tint 20% (`#FFE3D5`): Hover surfaces for urgent alert badges and inline warning indicators.
- **Secondary (`#0A2391` — Trust Blue):** Reserved for institutional legitimacy, confirmed registry checks, authoritative outbound references, and focus system states.
  - Tint 8% (`#EBEFFE`): Verified domain indicator backgrounds and cryptographic validity tags.
  - Tint 15% (`#D8DFFA`): Structural active indicators and selected state accents.
- **Neutral (`#373737` — Structural Charcoal):** Deep ink for legible reading and structural framing.
  - Tint 6% (`#F3F3F3`): Data table headers, code snippet containers, and secondary panel fills.
  - Tint 10% (`#EBEBEB`): Secondary divider tracks and inactive form border fills.
  - Tint 15% (`#E2E2E2` / 12–15% Charcoal): Hairline component structural frames and analytical table boundaries.
- **Base Canvas (`#FFFFFF`):** Uncompromising optical white for maximum scanning contrast and zero visual ambiguity.

## Typography

Typography enforces a strict demarcation between editorial authority, conversational clarity, and machine-level telemetry:

- **Display & Headings (Space Grotesk):** Engineered with mechanical geometric cuts. Must always use tight tracking (`-0.02em`) to evoke technical urgency without shouting. Line heights remain tight to lock headlines to structural section rules.
- **Body Text (IBM Plex Sans):** Neutral, highly legible humanist-grotesque engineered for long reading sequences, regulatory advisory sheets, and threat explanations. Body weights strictly use `400` for running copy, `500` for contextual emphasis, and `600` for field descriptors.
- **Monospace Telemetry (IBM Plex Mono):** Deployed for all raw telemetry: URLs, domain decomposition, regex tokens, hash signatures, SMS sender IDs, and UPI strings. Keeps all data inspection aligned, unambiguous, and distinct from surrounding guidance.

## Layout & Spacing

The layout is built upon an asymmetric 12-column architectural grid optimized for analytical review:
- **Canvas Rules:** Fixed edge gutters and responsive margins frame the grid. Screens above 1280px lock into a 1200px analytical canvas, using an 8-column primary investigation pane paired with a 4-column contextual ledger pane.
- **Mobile Paradigm:** Collapses to a single-column sequence with sticky top-level threat telemetry and compact 16px lateral margins.
- **Spacing Scale:** Built on a rigorous 4px/8px modular scale. Spacing within inspector clusters is deliberately tight (`space-xs` and `space-sm`) to increase information density, while boundaries between inspection panels leverage `space-lg` to `space-xl` separated by 1px solid hairline dividers (`#E2E2E2`).

## Elevation & Depth

This design system explicitly prohibits drop shadows, diffused lighting models, and floating z-index planes:

- **Flat Architectural Depth:** Z-index and visual hierarchy are articulated entirely through 1px hairline perimeter borders (`#373737` at 12% opacity / `#E2E2E2`) and tonal background shifts.
- **Layering Model:** 
  - Ground layer: Canvas (`#FFFFFF`).
  - Active Data panels: Pure `#FFFFFF` bordered with `#E2E2E2`.
  - Machine telemetry containers & code regions: `#F3F3F3` inset fills with no internal drop shadows.
- **Overlays & Dialogues:** Modals use a hard, non-blurred backdrop fill (`#373737` at 65% opacity) with the modal window framed by a 2px solid `#373737` border.
- **Focus Indicators:** Explicit, hyper-visible accessibility rings: `2px solid #0A2391` with a `2px` white offset gap.

## Shapes

The design system employs a clipped, near-orthogonal geometric footprint:
- **Corner Radii:** Set strictly to `2px` for micro-elements (chips, indicators, tags) and `4px` for structural panels, code inspector containers, and input fields.
- **Philosophy:** Rounded, pill-style shapes undermine civic urgency and institutional authority. Crisp corners convey technical rigor, structural permanence, and precision forensic analysis.

## Components

### Public Warning Banner (System-Wide Persistent)
- A non-dismissible, full-width top strip rendered in Safety Orange tint (`#FFF1EA`) bordered on the bottom with a 2px solid `#FF742D` rule.
- Text: "WARNING: Never share OTP, UPI PIN, or banking passwords. No bank or law enforcement agency will ever ask." in `IBM Plex Sans` 13px weight 600, color `#373737`. Preceded by an unmissable inline `[CRITICAL NOTICE]` indicator badge.

### Annotated URL Inspector
- A specialized inspection block showing decomposed domain anatomy.
- Monospace output (`IBM Plex Mono`) rendering the parsed URL inside a `#F3F3F3` background with a 1px border (`#E2E2E2`).
- Deconstructed segments: Protocol (`https://`), Subdomain, Root Domain, Top-Level Domain, and Path Query.
- Suspicious fragments (e.g., typosquatting, deceptive lookalike unicode characters, abnormal subdomains) are wrapped in bracketed indicators:
  - High risk: Red/Orange highlight with `[SUSPICIOUS DOMAIN]` tag immediately below the token.
  - Verified: Deep blue hairline underline with `[AUTHENTICATED TLD]` tag.

### Buttons
- **Primary Utility Button:** Solid Safety Orange (`#FF742D`), white text, 0-2px border-radius, weight 600, 1px `#373737` hairline stroke. Hover state darkens cleanly by 8%; active state depresses by 1px without blur.
- **Secondary Authority Button:** Pure white background, 1px solid `#373737` border, `#373737` text, 2px border-radius.
- **Tertiary Action:** Raw inline text in `#0A2391` with an immediate monospace bracket suffix (`[VERIFY REGISTRY ->]`).

### Inputs & Search Query Bars
- Structural rectangular fields framed by a crisp 1px solid border (`#373737` at 20% opacity). Focus shifts immediately to a `2px solid #0A2391` ring with a `2px` offset.
- Monospace font for verification queries (UPI IDs, phone numbers with `+91`, SMS headers, and URLs).

### Indicator Badges & Chips
- Sharp-edged (`2px` radius) monospace chips featuring bracketed structural styling:
  - `[VERIFIED ENTITY]`: `#EBEFFE` fill, `#0A2391` text and border.
  - `[FLAGGED MALICIOUS]`: `#FFF1EA` fill, `#FF742D` text and border.
  - `[UNCONFIRMED SENDER]`: `#F3F3F3` fill, `#373737` text and border.

### Data Cards & Inspection Ledgers
- Cards use `#FFFFFF` background, a 1px `#E2E2E2` outer border, and zero box-shadow.
- Card headers feature an authoritative top border accent (either neutral `#373737`, alert `#FF742D`, or confirmed `#0A2391`) 3px thick.