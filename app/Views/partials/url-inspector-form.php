<?php
/**
 * Partial: url-inspector-form.php
 *
 * Tabbed heuristic inspector form: URL | SMS/Message | UPI.
 * Submits POST to /analyze. Fully server-side functional.
 * No CSRF needed here (GET preview) — POST in AnalysisController validates input.
 *
 * No inline styles (only class-based), no Tailwind.
 * Matches the heuristic_analyzer screen hero.
 */
$formAction = $formAction ?? '/analyze';
$activeTab  = $activeTab  ?? 'url';
$inputValue = $inputValue ?? '';
?>

<div class="inspector-panel" role="search" aria-label="Heuristic URL and message inspector">

    <!-- Tabs ────────────────────────────────────────────────────────────── -->
    <div class="tabs" role="tablist" aria-label="Inspection type">

        <button
            class="tab-btn <?= $activeTab === 'sms' ? 'is-active' : '' ?>"
            role="tab"
            id="tab-sms"
            aria-selected="<?= $activeTab === 'sms' ? 'true' : 'false' ?>"
            aria-controls="panel-sms"
            type="button"
        >
            <span>[1] SMS / Message Text</span>
            <?php if ($activeTab === 'sms'): ?>
                <span class="tab-label-active">ACTIVE</span>
            <?php else: ?>
                <span class="t-code-sm" style="color:var(--on-surface-variant);">PAYLOAD</span>
            <?php endif; ?>
        </button>

        <button
            class="tab-btn <?= $activeTab === 'url' ? 'is-active' : '' ?>"
            role="tab"
            id="tab-url"
            aria-selected="<?= $activeTab === 'url' ? 'true' : 'false' ?>"
            aria-controls="panel-url"
            type="button"
        >
            <span>[2] URL / Domain Link</span>
            <?php if ($activeTab === 'url'): ?>
                <span class="tab-label-active">ACTIVE</span>
            <?php else: ?>
                <span class="t-code-sm" style="color:var(--on-surface-variant);">LINK</span>
            <?php endif; ?>
        </button>

        <button
            class="tab-btn <?= $activeTab === 'upi' ? 'is-active' : '' ?>"
            role="tab"
            id="tab-upi"
            aria-selected="<?= $activeTab === 'upi' ? 'true' : 'false' ?>"
            aria-controls="panel-upi"
            type="button"
        >
            <span>[3] UPI ID / QR Payload</span>
            <?php if ($activeTab === 'upi'): ?>
                <span class="tab-label-active">ACTIVE</span>
            <?php else: ?>
                <span class="t-code-sm" style="color:var(--on-surface-variant);">UPI</span>
            <?php endif; ?>
        </button>

    </div><!-- /.tabs -->

    <!-- Input area ──────────────────────────────────────────────────────── -->
    <form
        method="POST"
        action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>"
        class="inspector-form"
        novalidate
    >
        <!-- Hidden field: which tab was active (server uses to classify) -->
        <input type="hidden" name="input_type" id="input_type_field"
               value="<?= htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8') ?>">

        <div style="padding: var(--space-md);">

            <label class="sr-only" for="inspector-input">
                Paste URL, SMS payload, or UPI string for analysis
            </label>
            <div class="inspector-textarea-wrap">
                <textarea
                    class="form-textarea"
                    id="inspector-input"
                    name="payload"
                    rows="4"
                    required
                    minlength="4"
                    maxlength="4000"
                    spellcheck="false"
                    autocomplete="off"
                    autocorrect="off"
                    placeholder="Paste suspicious URL or SMS payload (e.g. http://sbi-kyc-update-portal.in/login or forward from +91...)"
                ><?= htmlspecialchars($inputValue, ENT_QUOTES, 'UTF-8') ?></textarea>

                <!-- Char counter + clear button -->
                <div class="form-counter">
                    <div style="display:flex;align-items:center;gap:var(--space-sm);">
                        <span id="char-count" aria-live="polite">0 chars</span>
                        <span style="color:var(--outline-variant);">•</span>
                        <span id="detected-type" class="t-code-sm" style="color:var(--on-secondary-container);font-weight:600;"></span>
                    </div>
                    <button type="button"
                            class="btn-tertiary"
                            id="clear-btn"
                            aria-label="Clear input">
                        ✕ Clear
                    </button>
                </div>
            </div>

            <!-- Quick sample presets -->
            <div class="inspector-presets">
                <span class="t-code-sm" style="font-weight:600;color:var(--on-surface);text-transform:uppercase;letter-spacing:.06em;">Load Sample:</span>
                <button class="preset-btn" type="button" data-preset="sbi">SBI KYC SMS</button>
                <button class="preset-btn" type="button" data-preset="bijli">Electricity Disconnect</button>
                <button class="preset-btn" type="button" data-preset="upi">UPI QR Payment</button>
            </div>

            <!-- Action row -->
            <div class="inspector-action-row">
                <div class="inspector-guarantee t-code-sm" aria-live="polite">
                    <span class="dot-marker--orange" style="display:inline-block;width:8px;height:8px;background:var(--primary-container);margin-right:4px;border-radius:50%;animation:pulse 2s infinite;"></span>
                    Local heuristic verification — instant offline parsing, zero telemetry
                </div>
                <button class="btn btn-primary" type="submit" id="inspect-btn">
                    Analyse Payload <span aria-hidden="true">→</span>
                </button>
            </div>

        </div>
    </form>

</div><!-- /.inspector-panel -->

<style>
.inspector-textarea-wrap {
    border: 1px solid rgba(55,55,55,.20);
    border-radius: var(--radius-panel);
    overflow: hidden;
}
.inspector-textarea-wrap .form-textarea {
    border: none;
    border-radius: 0;
    resize: none;
}
.inspector-textarea-wrap .form-textarea:focus {
    box-shadow: none;
}
.inspector-textarea-wrap:focus-within {
    outline: var(--focus-ring);
    outline-offset: var(--focus-ring-offset);
}
.inspector-presets {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-xs);
    margin-top: var(--space-sm);
    padding-top: var(--space-xs);
}
.inspector-action-row {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
    padding-top: var(--space-md);
    border-top: var(--border-hairline);
    margin-top: var(--space-sm);
}
@media (min-width: 540px) {
    .inspector-action-row {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
.inspector-guarantee {
    color: var(--on-surface-variant);
    display: flex;
    align-items: center;
}
@keyframes pulse {
    0%,100% { opacity:1; }
    50%      { opacity:.4; }
}
</style>

<script>
(function () {
    'use strict';

    /* ── Sample presets ─────────────────────────────────────────────────── */
    var presets = {
        sbi:   'Dear SBI user, your YONO account has been suspended due to pending PAN verification. Immediately update your KYC at http://sbi-kyc-update-portal.in/login or your account will be frozen within 2 hours. Do not ignore.',
        bijli: 'URGENT NOTICE: Your electricity connection bill Rs 1,840 is pending. Power will be disconnected tonight at 9:30 PM. Call Electricity Officer Sharma immediately at +91 98321-04918 to pay or update details.',
        upi:   'Scan this official QR to receive approved refund of Rs 4,999 from Merchant Support. Note: You must enter your 6-digit UPI PIN to verify and accept incoming funds into your bank account.'
    };

    var typeLabels = {
        sbi:   '[TRAI SENDER PATTERN DETECTED]',
        bijli: '[UTILITY DISCOM SPOOF DETECTED]',
        upi:   '[UPI REVERSE PAYLOAD DETECTED]'
    };

    var input     = document.getElementById('inspector-input');
    var counter   = document.getElementById('char-count');
    var typeEl    = document.getElementById('detected-type');
    var clearBtn  = document.getElementById('clear-btn');
    var typeField = document.getElementById('input_type_field');

    if (!input) return;

    function updateCounter() {
        if (counter) counter.textContent = input.value.length + ' chars';
    }

    input.addEventListener('input', updateCounter);
    updateCounter(); /* init if value pre-populated */

    /* Clear */
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            input.value = '';
            if (typeEl) typeEl.textContent = '';
            updateCounter();
            input.focus();
        });
    }

    /* Sample presets */
    document.querySelectorAll('.preset-btn[data-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var key = btn.getAttribute('data-preset');
            if (presets[key]) {
                input.value = presets[key];
                updateCounter();
                if (typeEl && typeLabels[key]) typeEl.textContent = typeLabels[key];
            }
        });
    });

    /* Tab switching: update hidden field + ARIA attributes */
    document.querySelectorAll('.tab-btn[role="tab"]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var type = tab.id.replace('tab-', '');
            document.querySelectorAll('.tab-btn[role="tab"]').forEach(function (t) {
                t.setAttribute('aria-selected', 'false');
                t.classList.remove('is-active');
            });
            tab.setAttribute('aria-selected', 'true');
            tab.classList.add('is-active');
            if (typeField) typeField.value = type;

            /* Update placeholder hint */
            var placeholders = {
                sms: 'Paste complete forwarded SMS message or WhatsApp text payload…',
                url: 'Paste suspicious URL or domain (e.g. http://sbi-kyc-update-portal.in/login)…',
                upi: 'Paste UPI VPA or UPI intent string (e.g. upi://pay?pa=…)…'
            };
            if (placeholders[type]) input.placeholder = placeholders[type];
        });
    });
}());
</script>
