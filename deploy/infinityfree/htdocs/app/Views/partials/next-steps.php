<?php
/**
 * Partial: next-steps.php
 *
 * Right-column triage panel: what the user should do right now.
 * Also contains official verified channels mini-card.
 *
 * Expects $result array from UrlAnalysisService in scope.
 */
$level = $result['risk_level'] ?? 'low';
$input = $result['input']      ?? '';
$type  = $result['input_type'] ?? 'url';

/**
 * Determine if we show bank-specific advice.
 * Only advisory — never a definitive label of guilt.
 */
$mentionsSbi = stripos($input, 'sbi') !== false;
?>

<!-- TRIAGE PANEL ─────────────────────────────────────────────────────── -->
<div class="triage-panel">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--space-md);padding-bottom:var(--space-sm);border-bottom:var(--border-hairline);">
        <h2 class="t-headline-sm">What to do right now</h2>
        <span class="badge badge-neutral">CITIZEN TRIAGE</span>
    </div>

    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-bottom:var(--space-md);">
        Follow this immediate response sequence to prevent unauthorized debits and assist civic reporting.
    </p>

    <!-- Interactive checklist -->
    <ol class="triage-steps" aria-label="Triage action checklist">

        <li class="triage-step">
            <label>
                <input type="checkbox" aria-label="Step 1: Do not open the link">
                <span class="step-number" aria-hidden="true">1</span>
                <span>
                    Do <strong>NOT</strong> tap, open, or forward this
                    <?= $type === 'url' ? 'link' : 'message' ?> to contacts.
                </span>
            </label>
        </li>

        <li class="triage-step">
            <label>
                <input type="checkbox" aria-label="Step 2: Freeze cards if credentials were shared">
                <span class="step-number" aria-hidden="true">2</span>
                <span>
                    If you entered your PIN or password: open your official bank app
                    immediately and freeze cards and net banking access.
                </span>
            </label>
        </li>

        <li class="triage-step">
            <label>
                <input type="checkbox" aria-label="Step 3: Dial 1930 helpline">
                <span class="step-number" aria-hidden="true">3</span>
                <span>
                    Report the phone number or sender SMS header to the
                    <strong>1930 Cyber Helpline</strong>.
                </span>
            </label>
        </li>

        <li class="triage-step">
            <label>
                <input type="checkbox" aria-label="Step 4: File on cybercrime portal">
                <span class="step-number" aria-hidden="true">4</span>
                <span>
                    Lodge an acknowledgement report on
                    <a href="https://cybercrime.gov.in" rel="noopener noreferrer" target="_blank">cybercrime.gov.in</a>.
                </span>
            </label>
        </li>

    </ol>

    <!-- Helplines -->
    <div style="display:flex;flex-direction:column;gap:var(--space-xs);margin-top:var(--space-md);padding-top:var(--space-sm);border-top:var(--border-hairline);">
        <div class="helpline-box">
            <div class="t-code-sm" style="color:var(--on-surface-variant);text-transform:uppercase;margin-bottom:4px;">
                Statutory National Line
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span class="helpline-number">Dial 1930</span>
                <span class="badge badge-verified">[TOLL-FREE 24×7]</span>
            </div>
        </div>

        <?php if ($mentionsSbi): ?>
        <div class="helpline-box">
            <div class="t-code-sm" style="color:var(--on-surface-variant);text-transform:uppercase;margin-bottom:4px;">
                State Bank of India Fraud Desk
            </div>
            <div class="t-code-md" style="font-weight:600;color:var(--on-surface);">
                1800 1234 / 1800 2100
            </div>
            <div class="t-body-sm" style="color:var(--on-surface-variant);margin-top:2px;">
                Official SBI unauthorized transaction reporting line
            </div>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.triage-panel -->


<!-- VERIFIED CHANNELS ────────────────────────────────────────────────── -->
<div class="card" style="border:var(--border-hairline);margin-top:var(--space-md);">
    <div class="card-header">
        <span aria-hidden="true" style="font-size:1rem;">✓</span>
        <h3 class="t-headline-sm" style="margin:0 0 0 var(--space-xs);">Official Verified Channels</h3>
    </div>
    <div class="card-body" style="padding:var(--space-sm) var(--space-md);">
        <table class="channels-table" aria-label="Official verified channel reference">
            <tbody>
                <tr>
                    <td style="color:var(--on-surface-variant);">SBI Web Portal:</td>
                    <td>onlinesbi.sbi</td>
                </tr>
                <tr>
                    <td style="color:var(--on-surface-variant);">TRAI DLT Portal:</td>
                    <td>trai.gov.in</td>
                </tr>
                <tr>
                    <td style="color:var(--on-surface-variant);">MHA Cyber Helpline:</td>
                    <td style="color:var(--primary);font-weight:700;">1930</td>
                </tr>
                <tr>
                    <td style="color:var(--on-surface-variant);">RBI Sachet Portal:</td>
                    <td>
                        <a href="https://sachet.rbi.org.in"
                           rel="noopener noreferrer"
                           target="_blank"
                           style="color:var(--on-secondary-container);">sachet.rbi.org.in</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
