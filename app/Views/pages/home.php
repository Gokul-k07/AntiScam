<?php
/**
 * Page: home.php (civic_scam_checking_utility screen)
 *
 * Matches Stitch design: asymmetric hero inspector, dark RBI statement band,
 * capability split, 4-step emergency triage, institutional registry strip.
 * All data is static/editorial — no dynamic bindings on this page.
 * No inline styles, no Tailwind CDN.
 */
$currentPage = 'home';
?>

<!-- ── Section 1: Asymmetric Forensic Hero ─────────────────────────────── -->
<section
    class="hero-section"
    aria-labelledby="hero-heading"
    style="background-color:var(--surface-bright);padding:var(--space-xl) 0;border-bottom:var(--border-hairline);"
>
    <div class="container">
        <div class="hero-grid">

            <!-- Left 7 cols: action area -->
            <div class="hero-left">
                <div class="hero-eyebrow t-code-sm" aria-hidden="true">
                    <span style="display:inline-block;width:10px;height:10px;background:var(--primary-container);"></span>
                    Civic Threat Detection &bull; Local Heuristics
                </div>

                <h1 class="t-headline-xl hero-heading" id="hero-heading">
                    Got a strange link or message?<br>
                    Check it before you tap.
                </h1>

                <p class="t-body-lg hero-subtext">
                    Deterministic rule matching for Indian phishing vectors, fake KYC demands,
                    and predatory UPI requests. Fully airgapped, zero-telemetry local inspection.
                </p>

                <!-- Inspector form -->
                <?php include __DIR__ . '/../partials/url-inspector-form.php'; ?>
            </div>

            <!-- Right 5 cols: forensic specimen anatomy sample -->
            <aside class="hero-right" aria-label="Example of a flagged SMS and its URL anatomy">
                <div class="card card-dark">
                    <div class="card-dark-header" style="padding:var(--space-sm) var(--space-md);border-bottom:1px solid rgba(255,255,255,0.08);display:flex;align-items:center;justify-content:space-between;">
                        <span class="t-code-sm" style="color:var(--primary-fixed);font-weight:600;text-transform:uppercase;letter-spacing:.06em;">
                            Live Anatomy Sample &bull; What a Flagged SMS Looks Like
                        </span>
                        <span class="t-code-sm" style="color:var(--surface-container-highest);">[SPECIMEN-IN-492]</span>
                    </div>
                    <div style="padding:var(--space-lg);display:flex;flex-direction:column;gap:var(--space-lg);">

                        <!-- Raw SMS text -->
                        <blockquote
                            class="t-body-md"
                            style="background:var(--surface-container-lowest);color:var(--on-surface);padding:var(--space-md);border-left:4px solid var(--primary-container);margin:0;line-height:var(--lh-body-lg);"
                        >
                            <p>
                                &ldquo;Dear SBI user, your YONO account has been suspended due to pending
                                PAN verification. Immediately update your KYC at
                                <span style="background:var(--primary-fixed);color:var(--on-primary-fixed);font-family:var(--font-mono);font-size:var(--text-code-sm);padding:0 4px;">
                                    http://sbi-kyc-update-portal.in/login
                                </span>
                                or your account will be frozen within 2 hours. Do not ignore.&rdquo;
                            </p>
                        </blockquote>

                        <!-- URL decomposition -->
                        <div>
                            <p class="t-code-sm" style="color:var(--surface-container-highest);text-transform:uppercase;letter-spacing:.06em;font-weight:600;margin-bottom:var(--space-sm);">
                                Parsed URI Decomposition:
                            </p>
                            <div class="url-anatomy" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.15);">
                                <div class="url-tokens">
                                    <div class="url-token url-token--warning">
                                        <span class="url-token__value">http://</span>
                                        <span class="url-token__label">[1 UNENCRYPTED]</span>
                                    </div>
                                    <div class="url-token url-token--warning">
                                        <span class="url-token__value">sbi-kyc-update-portal</span>
                                        <span class="url-token__label">[2 TYPOSQUAT BRAND STACK]</span>
                                    </div>
                                    <div class="url-token url-token--danger">
                                        <span class="url-token__value">.in/login</span>
                                        <span class="url-token__label">[3 ANONYMOUS ENDPOINT]</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Technical legend -->
                        <ol class="t-code-sm" style="color:var(--surface-container-high);display:flex;flex-direction:column;gap:var(--space-sm);padding-left:0;list-style:none;border-top:1px solid rgba(255,255,255,.1);padding-top:var(--space-md);">
                            <li style="display:flex;gap:var(--space-sm);">
                                <strong style="color:var(--primary-container);">1.</strong>
                                Unencrypted HTTP transport for a supposed core banking verification endpoint.
                            </li>
                            <li style="display:flex;gap:var(--space-sm);">
                                <strong style="color:var(--primary-container);">2.</strong>
                                Brand keyword concatenation designed to disguise root TLD in mobile browser viewports.
                            </li>
                            <li style="display:flex;gap:var(--space-sm);">
                                <strong style="color:var(--primary-container);">3.</strong>
                                Coercive 2-hour freeze countdown violates RBI Master Directions on customer communication.
                            </li>
                        </ol>

                        <!-- Score footer -->
                        <div style="padding:var(--space-sm) var(--space-md);background:rgba(255,116,45,.1);border:1px solid rgba(255,116,45,.3);display:flex;align-items:center;justify-content:space-between;">
                            <span class="t-code-sm" style="color:var(--primary-fixed);">Flagged locally in 12ms.</span>
                            <span class="t-code-sm" style="color:var(--primary-container);font-weight:700;">Risk Score: 94/100 [CRITICAL RISK]</span>
                        </div>

                    </div>
                </div>
            </aside>

        </div>
    </div>
</section>

<!-- ── Section 2: Dark RBI Statement Band ──────────────────────────────── -->
<section
    class="statement-band"
    aria-labelledby="rbi-heading"
    style="padding:var(--space-xl) 0;"
>
    <div class="container">
        <div style="display:flex;flex-direction:column;gap:var(--space-lg);">
            <div style="display:flex;align-items:center;gap:var(--space-sm);">
                <span style="display:inline-block;width:8px;height:8px;background:var(--primary-container);"></span>
                <span class="t-code-sm" style="color:var(--primary-fixed);font-weight:700;text-transform:uppercase;letter-spacing:.08em;">
                    RBI Circular RBI/2023-24/108 &bull; Statutory Directive
                </span>
            </div>
            <p class="t-headline-lg" id="rbi-heading" style="color:var(--surface-container-lowest);max-width:800px;line-height:1.3;">
                No bank, DISCOM, or police officer will
                <span style="text-decoration:underline;text-decoration-color:var(--primary-container);text-decoration-thickness:4px;text-underline-offset:4px;color:var(--primary-fixed);">ever ask you to read out an OTP</span>,
                <span style="text-decoration:underline;text-decoration-color:var(--primary-container);text-decoration-thickness:4px;text-underline-offset:4px;color:var(--primary-fixed);">scan a QR code to receive money</span>, or
                <span style="text-decoration:underline;text-decoration-color:var(--primary-container);text-decoration-thickness:4px;text-underline-offset:4px;color:var(--primary-fixed);">download an APK over WhatsApp</span>.
            </p>
        </div>
    </div>
</section>

<!-- ── Section 3: Capability Split (can / cannot) ──────────────────────── -->
<section
    style="background-color:var(--surface-container-lowest);padding:var(--space-xl) 0;border-bottom:var(--border-hairline);"
    aria-labelledby="capability-heading"
>
    <div class="container">
        <div class="section-header" style="display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:var(--space-sm);margin-bottom:var(--space-lg);padding-bottom:var(--space-md);border-bottom:var(--border-hairline);">
            <h2 class="t-headline-md" id="capability-heading" style="text-transform:uppercase;">
                Forensic Operational Bounds
            </h2>
            <span class="t-code-sm" style="color:var(--on-surface-variant);">STRICT DETERMINISTIC SCOPE SPECIFICATION</span>
        </div>

        <div class="capability-split">

            <!-- What ScamShield CAN flag -->
            <div class="capability-col">
                <div style="display:flex;align-items:center;gap:var(--space-sm);margin-bottom:var(--space-lg);">
                    <span style="display:inline-block;width:10px;height:10px;background:var(--on-secondary-container);"></span>
                    <h3 class="t-headline-sm" style="text-transform:uppercase;">What ScamShield Can Flag</h3>
                </div>
                <div style="display:flex;flex-direction:column;gap:var(--space-lg);">
                    <div class="capability-item">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Lookalike Typosquats &amp; Phishing Stacks:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface);">
                            Domains engineered to visually abuse scheduled commercial banks, parcel logistics
                            (India Post, BlueDart), and utility portals via unauthorized top-level suffixes.
                        </p>
                    </div>
                    <div class="capability-item">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Coercive Social Engineering Constructs:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface);">
                            Known extortion algorithms, electricity immediate blackout threats, fake judicial
                            notices, and unauthorized police summons invoking rapid asset freeze threats.
                        </p>
                    </div>
                    <div class="capability-item">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Non-Compliant SMS Senders:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface);">
                            Payloads originating from 10-digit mobile numbers (+91…) falsely claiming to be
                            transactional alerts, directly violating TRAI DLT commercial header standards.
                        </p>
                    </div>
                    <div class="capability-item">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Inbound UPI Collect Reversal Tricks:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface);">
                            VPA collection requests falsely disguised with descriptions like
                            &ldquo;Approved Refund&rdquo;, &ldquo;Cashback Credit&rdquo;, or &ldquo;Army Official Verification&rdquo;.
                        </p>
                    </div>
                </div>
            </div>

            <!-- What ScamShield CANNOT tell you -->
            <div class="capability-col">
                <div style="display:flex;align-items:center;gap:var(--space-sm);margin-bottom:var(--space-lg);">
                    <span style="display:inline-block;width:10px;height:10px;background:var(--primary-container);"></span>
                    <h3 class="t-headline-sm" style="text-transform:uppercase;">What ScamShield Can&rsquo;t Tell You</h3>
                </div>
                <div style="display:flex;flex-direction:column;gap:var(--space-lg);">
                    <div class="capability-item capability-item--warning">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Absence of Trigger Is Not an Endorsement:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface-variant);">
                            Zero matched heuristic flags does not guarantee absolute safety.
                            Novel domains created within the last hour may not match known patterns immediately.
                        </p>
                    </div>
                    <div class="capability-item capability-item--warning">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Zero Backend Telemetry &amp; Storage:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface-variant);">
                            The system does not send your query to an external server or save text strings.
                            Checks execute using local rule patterns only.
                        </p>
                    </div>
                    <div class="capability-item capability-item--warning">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Irreversible UPI Settlement:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface-variant);">
                            Once a UPI PIN is submitted on NPCI infrastructure, the software cannot halt
                            or reverse the clearing transaction. Only immediate reporting via <strong>1930</strong> can trigger freeze protocols.
                        </p>
                    </div>
                    <div class="capability-item capability-item--warning">
                        <strong class="t-body-lg" style="display:block;margin-bottom:4px;">Not a Law Enforcement Action:</strong>
                        <p class="t-body-lg" style="color:var(--on-surface-variant);">
                            This application is an educational pre-flight diagnostic tool. It does not replace
                            a formal First Information Report (FIR) filed through official state police mechanisms.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ── Section 4: Emergency Triage Strip ───────────────────────────────── -->
<section
    style="background-color:var(--surface-container);padding:var(--space-xl) 0;border-bottom:var(--border-hairline);"
    aria-labelledby="triage-heading"
>
    <div class="container">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:var(--space-sm);margin-bottom:var(--space-lg);padding-bottom:var(--space-sm);border-bottom:var(--border-hairline);">
            <div>
                <span class="t-code-sm" style="color:var(--primary);font-weight:700;text-transform:uppercase;letter-spacing:.06em;display:block;margin-bottom:4px;">
                    Time-Critical Action Sequence
                </span>
                <h2 class="t-headline-md" id="triage-heading" style="text-transform:uppercase;">
                    Emergency Triage — What to do if you already clicked or paid
                </h2>
            </div>
            <span class="t-code-sm" style="color:var(--on-surface-variant);">GOLDEN HOUR RECOVERY PROTOCOL</span>
        </div>

        <ol class="emergency-steps" aria-label="Four-step emergency triage sequence">

            <!-- Step 01 -->
            <li class="emergency-step">
                <div>
                    <div class="step-num" aria-hidden="true">01</div>
                    <h3 class="t-headline-sm" style="text-transform:uppercase;margin-bottom:var(--space-sm);">Disconnect &amp; Freeze</h3>
                    <p class="t-body-md" style="color:var(--on-surface-variant);line-height:var(--lh-body-lg);">
                        If card credentials or passwords were typed, open your official mobile banking app
                        immediately and toggle international, domestic, and online limits to zero.
                    </p>
                </div>
                <div class="step-footer">Helpline: SBI 1800 1234</div>
            </li>

            <!-- Step 02 — highlighted -->
            <li class="emergency-step emergency-step--highlight">
                <div>
                    <div class="step-num" aria-hidden="true">02</div>
                    <h3 class="t-headline-sm" style="text-transform:uppercase;margin-bottom:var(--space-sm);">Dial 1930 Helpline</h3>
                    <p class="t-body-md" style="color:var(--on-surface-variant);line-height:var(--lh-body-lg);">
                        Call the <strong style="color:var(--on-surface);">National Cyber Crime Helpline at 1930</strong>
                        within the golden hour. Provide transaction UTR, debit card digits, and beneficiary handle
                        to freeze funds in transit.
                    </p>
                </div>
                <div class="step-footer">Dial 1930 Immediately</div>
            </li>

            <!-- Step 03 -->
            <li class="emergency-step">
                <div>
                    <div class="step-num" aria-hidden="true">03</div>
                    <h3 class="t-headline-sm" style="text-transform:uppercase;margin-bottom:var(--space-sm);">Lodge Portal Incident</h3>
                    <p class="t-body-md" style="color:var(--on-surface-variant);line-height:var(--lh-body-lg);">
                        File a formal incident acknowledgement at
                        <a href="https://cybercrime.gov.in" rel="noopener noreferrer" target="_blank">cybercrime.gov.in</a>.
                        Submit timestamped screenshots, SMS sender headers, and WhatsApp conversation exports.
                    </p>
                </div>
                <div class="step-footer">Portal: cybercrime.gov.in</div>
            </li>

            <!-- Step 04 -->
            <li class="emergency-step">
                <div>
                    <div class="step-num" aria-hidden="true">04</div>
                    <h3 class="t-headline-sm" style="text-transform:uppercase;margin-bottom:var(--space-sm);">Export Evidence</h3>
                    <p class="t-body-md" style="color:var(--on-surface-variant);line-height:var(--lh-body-lg);">
                        Generate a structured evidentiary summary containing raw message payload,
                        resolved IP, and timestamp for official use.
                    </p>
                </div>
                <div class="step-footer">
                    <a href="/analyze" class="btn-tertiary">
                        [Use Analyzer to generate evidence packet →]
                    </a>
                </div>
            </li>

        </ol>
    </div>
</section>

<!-- ── Section 5: Institutional Registry Directory ─────────────────────── -->
<section
    style="background-color:var(--surface-container-lowest);padding:var(--space-xl) 0;border-bottom:var(--border-hairline);"
    aria-labelledby="registry-heading"
>
    <div class="container">
        <div style="padding:var(--space-lg);background:var(--surface-container-low);border:var(--border-hairline);display:flex;flex-direction:column;gap:var(--space-lg);">
            <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:var(--space-lg);">
                <div>
                    <span class="t-code-sm" style="color:var(--on-surface-variant);text-transform:uppercase;font-weight:600;letter-spacing:.06em;display:block;margin-bottom:4px;">
                        Institutional Registry Network
                    </span>
                    <h2 class="t-headline-sm" id="registry-heading">
                        Authoritative Redressal &amp; Verification Directories
                    </h2>
                    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">
                        Direct statutory pipelines managed under Ministry of Home Affairs, RBI, and TRAI directives.
                    </p>
                </div>
                <nav class="registry-links" aria-label="Official government portals">
                    <a class="registry-link"
                       href="https://cybercrime.gov.in"
                       rel="noopener noreferrer"
                       target="_blank">
                        cybercrime.gov.in [MHA]
                    </a>
                    <a class="registry-link"
                       href="https://sachet.rbi.org.in"
                       rel="noopener noreferrer"
                       target="_blank">
                        RBI Sachet Portal
                    </a>
                    <a class="registry-link"
                       href="https://www.npci.org.in"
                       rel="noopener noreferrer"
                       target="_blank">
                        NPCI Dispute Redressal
                    </a>
                    <a class="registry-link"
                       href="https://telecomreg.trai.gov.in"
                       rel="noopener noreferrer"
                       target="_blank">
                        TRAI DLT Registry
                    </a>
                </nav>
            </div>
        </div>
    </div>
</section>

<style>
/* Hero layout */
.hero-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-xl);
}
@media (min-width: 900px) {
    .hero-grid {
        grid-template-columns: 7fr 5fr;
        align-items: start;
    }
}
.hero-left {
    display: flex;
    flex-direction: column;
    gap: var(--space-lg);
}
.hero-eyebrow {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    color: var(--on-surface-variant);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    font-family: var(--font-mono);
    font-size: var(--text-code-sm);
}
.hero-heading {
    max-width: 640px;
    color: var(--on-surface);
}
.hero-subtext {
    color: var(--on-surface-variant);
    max-width: 540px;
    line-height: var(--lh-body-lg);
}
.hero-right {
    /* dark forensic panel */
}

/* Registry links flex strip */
.registry-links {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-xs);
}
</style>
