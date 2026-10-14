<?php
/**
 * Page: pages/analyzer.php (heuristic_analyzer screen)
 *
 * Full analyzer page: header + tabbed inspector form + live threat pattern table.
 * Submits POST to /analyze.
 *
 * Variables from AnalysisController:
 *   $inputValue  : string — pre-populated value (empty by default)
 *   $activeTab   : string — 'url' | 'sms' | 'upi'
 *   $formAction  : string — '/analyze'
 *   $currentPage : string — 'analyzer'
 *   $formError   : string|null — validation error message
 */
$inputValue  = $inputValue  ?? '';
$activeTab   = $activeTab   ?? 'url';
$formAction  = $formAction  ?? '/analyze';
$currentPage = $currentPage ?? 'analyzer';
$formError   = $formError   ?? null;
?>

<!-- Section 1: Primary Input & Controls ─────────────────────────────── -->
<section
    style="background-color:var(--surface-container-lowest);border-bottom:var(--border-hairline);padding:var(--space-lg) 0;"
    aria-labelledby="analyzer-heading"
>
    <div class="container">

        <!-- Operational header -->
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:var(--space-md);padding-bottom:var(--space-md);border-bottom:var(--border-hairline);margin-bottom:var(--space-lg);">
            <div>
                <div style="display:flex;align-items:center;gap:var(--space-xs);margin-bottom:4px;">
                    <span style="display:inline-block;width:10px;height:10px;background:var(--primary-container);" aria-hidden="true"></span>
                    <span class="t-code-sm" style="font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--on-surface);">
                        Civic Heuristic Engine &bull; Version 1.4-L
                    </span>
                </div>
                <h1 class="t-headline-lg" id="analyzer-heading">
                    Paste a link, SMS, WhatsApp forward, or UPI handle
                </h1>
            </div>
            <span class="badge badge-neutral" style="white-space:nowrap;">MODE: AIRGAPPED LOCAL PARSER</span>
        </div>

        <!-- Error state (validation failure) -->
        <?php if ($formError !== null): ?>
            <div class="error-state" role="alert" style="margin-bottom:var(--space-md);">
                <?= htmlspecialchars($formError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- The tabbed inspector form partial -->
        <?php include dirname(__DIR__) . '/partials/url-inspector-form.php'; ?>

        <!-- Transparency guarantee strip -->
        <div class="info-strip" style="margin-top:var(--space-md);">
            <span aria-hidden="true" style="font-size:.8rem;">🔒</span>
            <span>Checked using local deterministic rules only. Zero telemetry, cookies, or remote server lookups.</span>
            <span style="margin-left:auto;color:rgba(89,66,56,.5);white-space:nowrap;" class="t-code-sm">
                RFC 3986 &bull; NPCI SPEC 2.1 &bull; TRAI TCCCPR-2018 RULES
            </span>
        </div>

    </div>
</section>

<!-- Section 2: Active Threat Pattern Registry ───────────────────────── -->
<section
    style="background-color:var(--surface-container-lowest);border-top:var(--border-hairline);padding:var(--space-xl) 0;"
    aria-labelledby="registry-heading"
>
    <div class="container">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:var(--space-md);margin-bottom:var(--space-lg);padding-bottom:var(--space-sm);border-bottom:var(--border-hairline);">
            <div>
                <div style="display:flex;align-items:center;gap:var(--space-xs);margin-bottom:4px;">
                    <span style="display:inline-block;width:8px;height:8px;background:var(--on-secondary-container);" aria-hidden="true"></span>
                    <span class="t-code-sm" style="font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Registry Surveillance Ledger</span>
                </div>
                <h2 class="t-headline-md" id="registry-heading">Active Threat Patterns in India</h2>
            </div>
            <span class="t-code-sm" style="color:var(--on-surface-variant);">
                Based on I4C &amp; CERT-In public advisories
            </span>
        </div>

        <!-- Broadsheet-style table -->
        <div class="overflow-x">
            <table class="data-table" aria-label="Active Indian scam threat patterns">
                <thead>
                    <tr>
                        <th scope="col">Scam Type</th>
                        <th scope="col">Deception Vector</th>
                        <th scope="col">Primary Heuristic Trigger</th>
                        <th scope="col">Official Channel to Verify</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>
                        <td>
                            <span class="badge badge-alert" style="margin-bottom:4px;">[VECTOR-01]</span><br>
                            <strong class="t-code-md">Electricity Cutoff Notice</strong>
                        </td>
                        <td>
                            <code class="t-code-sm" style="background:var(--surface-container-low);padding:var(--space-xs) var(--space-sm);border:var(--border-hairline);display:block;">
                                &ldquo;Dear consumer, your electricity will be disconnected tonight at 9:30 PM &hellip; contact officer Sharma at 98765-XXXXX.&rdquo;
                            </code>
                        </td>
                        <td>
                            <strong style="color:var(--error);">Arbitrary 10-digit Mobile Number</strong>
                            <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">DISCOMs never instruct users to WhatsApp or dial private numbers for bill settlement.</p>
                        </td>
                        <td>
                            <span class="t-code-sm" style="color:var(--on-secondary-container);font-weight:600;">
                                State DISCOM Official App (BSES, TANGEDCO, MSEDCL, UPPCL)
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="badge badge-alert" style="margin-bottom:4px;">[VECTOR-02]</span><br>
                            <strong class="t-code-md">FedEx / Customs Contraband</strong>
                        </td>
                        <td>
                            <code class="t-code-sm" style="background:var(--surface-container-low);padding:var(--space-xs) var(--space-sm);border:var(--border-hairline);display:block;">
                                &ldquo;Parcel in your name seized by Mumbai Customs containing passports and banned narcotics. Join Skype for clearance.&rdquo;
                            </code>
                        </td>
                        <td>
                            <strong style="color:var(--error);">Skype / WhatsApp &ldquo;Digital Arrest&rdquo;</strong>
                            <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">Indian Police or Customs never conduct interrogations over Skype or WhatsApp.</p>
                        </td>
                        <td>
                            <span class="t-code-sm" style="color:var(--on-secondary-container);font-weight:600;">
                                Direct Tracking via fedex.com/in or Local Police Station
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="badge badge-alert" style="margin-bottom:4px;">[VECTOR-03]</span><br>
                            <strong class="t-code-md">Hotel / YouTube Review Task</strong>
                        </td>
                        <td>
                            <code class="t-code-sm" style="background:var(--surface-container-low);padding:var(--space-xs) var(--space-sm);border:var(--border-hairline);display:block;">
                                &ldquo;Work from home: Earn ₹2,500&ndash;₹8,000 per day rating hotels &hellip; Join our Telegram group for immediate payouts.&rdquo;
                            </code>
                        </td>
                        <td>
                            <strong style="color:var(--error);">Advance Fee &amp; Telegram Escrow</strong>
                            <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">Initial ₹150 payouts build trust; then ₹10,000+ &ldquo;prepaid VIP tasks&rdquo; are demanded.</p>
                        </td>
                        <td>
                            <span class="t-code-sm" style="color:var(--on-secondary-container);font-weight:600;">
                                National Career Service (<a href="https://ncs.gov.in" rel="noopener noreferrer" target="_blank">ncs.gov.in</a>)
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <span class="badge badge-alert" style="margin-bottom:4px;">[VECTOR-04]</span><br>
                            <strong class="t-code-md">UPI QR &ldquo;Receive Money&rdquo; Trap</strong>
                        </td>
                        <td>
                            <code class="t-code-sm" style="background:var(--surface-container-low);padding:var(--space-xs) var(--space-sm);border:var(--border-hairline);display:block;">
                                &ldquo;Scan this QR and enter your UPI PIN to accept ₹15,000 into your account immediately.&rdquo;
                            </code>
                        </td>
                        <td>
                            <strong style="color:var(--error);">PIN Entry on Inbound Transaction</strong>
                            <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">NPCI: UPI PIN is entered ONLY to deduct funds — NEVER to receive funds.</p>
                        </td>
                        <td>
                            <span class="t-code-sm" style="color:var(--on-secondary-container);font-weight:600;">
                                NPCI UPI Safety Directive (<a href="https://npci.org.in" rel="noopener noreferrer" target="_blank">npci.org.in</a>)
                            </span>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div><!-- /.overflow-x -->

    </div>
</section>
