<?php
/**
 * Partial: limitations-box.php
 *
 * "How sure are we?" / limitations card displayed in the right column.
 * Renders the airgap proof statement and the local-rules-only disclaimer.
 * Security rule 16: "no threat-feed match" is never "safe."
 *
 * No dynamic $result dependency — pure static disclaimer text.
 */
?>

<div class="limitations-box">
    <div style="display:flex;align-items:center;gap:var(--space-xs);margin-bottom:var(--space-sm);">
        <span aria-hidden="true" style="font-size:1rem;">🛡️</span>
        <h3 class="t-headline-sm" style="margin:0;">How sure are we?</h3>
    </div>

    <p>
        Local heuristic rules only. No live external threat feed or telemetry was consulted.
        The analyzer uses static pattern matching against known Indian phishing vectors.
    </p>
    <p style="margin-top:var(--space-sm);">
        A high score means the link or message displays proven attack hallmarks.
        <strong>It does not replace a formal police investigation.</strong>
        A low score does not guarantee the link is safe — novel domains created recently
        may not yet match any known pattern.
    </p>

    <div class="airgap-proof" style="margin-top:var(--space-md);">
        <span class="t-code-sm" style="color:var(--primary-container);font-weight:700;">[AIRGAP PROOF]</span>
        <span class="t-code-sm" style="color:var(--on-surface);">
            We did not visit this link. No request was sent to any remote server.
            Zero cookies, zero telemetry, zero data retention.
        </span>
    </div>
</div>
