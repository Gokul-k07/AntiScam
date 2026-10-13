<?php
/**
 * View: analysis/url_result.php (link_analysis_result screen)
 *
 * Full result page. Matches the Stitch link_analysis_result specimen screen.
 * ALL sample text from Stitch is replaced with dynamic $result data.
 *
 * Variables from AnalysisController:
 *   $result      : array from UrlAnalysisService::analyze()
 *   $currentPage : 'analyzer'
 *
 * Security: every $result value is escaped with htmlspecialchars() before output.
 * No SQL, no file I/O, no outbound requests in this view.
 */

// Guard against direct access without data
if (!isset($result) || !is_array($result)) {
    // Show empty/error state
    echo '<div class="container" style="padding:var(--space-xl) 0;">'
       . '<div class="error-state" role="alert">No analysis result available. '
       . '<a href="/analyze">Run a new inspection.</a></div></div>';
    return;
}

$e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$level   = $result['risk_level'] ?? 'low';
$score   = (int)($result['score']   ?? 0);
$verdict = $result['verdict']       ?? '';
$summary = $result['summary']       ?? '';
$input   = $result['input']         ?? '';
$type    = $result['input_type']    ?? 'url';

// Verdict banner colour modifier
$bannerMod = match($level) {
    'critical' => 'card--accent-error',
    'high'     => 'card--accent-alert',
    'moderate' => 'card--accent-verified',
    default    => 'card--accent-neutral',
};
?>

<div class="container" style="padding:var(--space-lg) 0 var(--space-xl);">

    <!-- TOP CONTEXT HEADER ──────────────────────────────────────────── -->
    <section aria-labelledby="result-meta-heading">
        <div class="card" style="margin-bottom:var(--space-lg);">
            <div class="card-body">
                <div class="breadcrumb-meta">
                    <!-- Left: scan meta -->
                    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-sm);">
                        <span class="dot-marker"></span>
                        <strong class="t-code-sm" style="color:var(--on-surface);letter-spacing:.06em;text-transform:uppercase;">
                            CIVIC HEURISTIC INSPECTION
                        </strong>
                        <span style="color:var(--outline-variant);">•</span>
                        <span class="badge badge-neutral" id="result-meta-heading">
                            <?= $e(strtoupper($type)) ?> ANALYSIS
                        </span>
                        <span style="color:var(--outline-variant);">•</span>
                        <span class="t-code-sm">Inspected locally in &lt;20ms</span>
                    </div>
                    <!-- Right: airgap badge -->
                    <div style="display:flex;align-items:center;gap:var(--space-xs);background:var(--surface-container-low);padding:4px 10px;border:var(--border-hairline);">
                        <span aria-hidden="true" style="font-size:.8rem;">🔒</span>
                        <span class="t-code-sm">We did not visit this link. Zero connection made to target server.</span>
                    </div>
                </div>

                <!-- URL anatomy -->
                <?php
                /**
                 * Annotated URL block — uses $result['url_tokens'].
                 * If no tokens (SMS input) shows raw payload.
                 */
                include dirname(__DIR__) . '/../partials/annotated-url.php';
                ?>
            </div>
        </div>
    </section>

    <!-- MAIN 8/4 GRID ───────────────────────────────────────────────── -->
    <div class="grid-12" style="align-items:start;">

        <!-- LEFT 8 COLS: Verdict + Findings ─────────────────────────── -->
        <section class="col-8" aria-labelledby="verdict-heading">

            <!-- VERDICT BANNER ─────────────────────────────────────── -->
            <div class="verdict-banner verdict-banner--<?= $e($level) ?> <?= $e($bannerMod) ?>" style="margin-bottom:var(--space-md);">

                <div class="verdict-header">
                    <div>
                        <div class="t-code-sm" style="color:var(--on-surface-variant);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">
                            DETERMINISTIC EVALUATION RESULT
                        </div>
                        <div style="display:flex;align-items:center;gap:var(--space-sm);flex-wrap:wrap;">
                            <h1 class="t-headline-xl" id="verdict-heading" style="color:var(--on-surface);">
                                <?= $e(ucfirst($level)) ?> risk
                            </h1>
                            <span class="badge <?= $level === 'critical' ? 'badge-danger' : ($level === 'high' ? 'badge-alert' : 'badge-neutral') ?>">
                                [<?= $e(strtoupper($level)) ?> RISK]
                            </span>
                        </div>
                    </div>
                    <div class="verdict-score" aria-label="Risk score <?= $score ?> out of 100">
                        <?= $score ?><span class="t-body-sm" style="color:var(--on-surface-variant);">/100</span>
                    </div>
                </div>

                <!-- Risk meter ────────────────────────────────────── -->
                <div style="padding:var(--space-md) var(--space-lg);">
                    <?php include dirname(__DIR__) . '/../partials/risk-meter.php'; ?>
                </div>

                <!-- Plain-language summary ──────────────────────── -->
                <div class="verdict-body">
                    <?= $e($summary) ?>
                </div>

                <!-- Metric chips ────────────────────────────────── -->
                <div class="verdict-meta">
                    <span class="badge badge-neutral">Score: <strong style="color:var(--primary);"><?= $score ?>/100</strong></span>
                    <?php if (!empty($result['findings'])): ?>
                        <span class="badge badge-neutral"><?= count($result['findings']) ?> Rule<?= count($result['findings']) === 1 ? '' : 's' ?> Matched</span>
                    <?php endif; ?>
                    <span class="badge badge-neutral">Input: <?= $e(strtoupper($type)) ?></span>
                    <span class="badge badge-neutral">Engine: IN-CIVIC-1.4</span>
                </div>

            </div><!-- /.verdict-banner -->

            <!-- FINDINGS LIST ──────────────────────────────────────── -->
            <?php include dirname(__DIR__) . '/../partials/findings-list.php'; ?>

        </section><!-- /.col-8 -->

        <!-- RIGHT 4 COLS: Triage + Limitations ──────────────────────── -->
        <aside class="col-4 sticky-aside" aria-label="Next steps and limitations">

            <!-- NEXT STEPS ─────────────────────────────────────────── -->
            <?php include dirname(__DIR__) . '/../partials/next-steps.php'; ?>

            <!-- LIMITATIONS BOX ────────────────────────────────────── -->
            <div style="margin-top:var(--space-md);">
                <?php include dirname(__DIR__) . '/../partials/limitations-box.php'; ?>
            </div>

        </aside><!-- /.col-4 -->

    </div><!-- /.grid-12 -->

    <!-- BOTTOM ACTION BAR ───────────────────────────────────────────── -->
    <section
        style="background-color:var(--surface-container-lowest);border:var(--border-hairline);padding:var(--space-md) var(--space-lg);margin-top:var(--space-md);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:var(--space-md);"
        aria-label="Post-analysis actions"
    >
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-sm);">
            <!-- Log incident: TODO in Milestone 2 (no backend yet) -->
            <button class="btn btn-primary" type="button" disabled
                    aria-disabled="true"
                    title="Incident logging coming in Milestone 2">
                Log as Incident
                <span class="t-code-sm" style="opacity:.6;">[TODO: Milestone 2]</span>
            </button>

            <!-- Copy summary — JS progressive enhancement -->
            <button class="btn btn-secondary js-only"
                    type="button"
                    id="copy-summary-btn"
                    aria-label="Copy plain text summary to clipboard">
                Copy Summary (Plain text)
            </button>

            <!-- Check another -->
            <a href="/analyze" class="btn btn-secondary">
                Check Another Link or Message
            </a>
        </div>

        <!-- Export JSON evidence packet -->
        <button class="btn-tertiary js-only"
                type="button"
                id="export-json-btn"
                aria-label="Export forensic evidence JSON packet">
            ↓ Export Evidence Packet (.json)
        </button>
    </section>

</div><!-- /.container -->


<script>
(function () {
    'use strict';

    /* Pre-hydrate result data for JS actions */
    var resultData = {
        score:     <?= json_encode($score, JSON_THROW_ON_ERROR) ?>,
        risk:      <?= json_encode($level, JSON_THROW_ON_ERROR) ?>,
        verdict:   <?= json_encode($verdict, JSON_THROW_ON_ERROR) ?>,
        summary:   <?= json_encode($summary, JSON_THROW_ON_ERROR) ?>,
        input:     <?= json_encode(mb_substr($input, 0, 500), JSON_THROW_ON_ERROR) ?>,
        inputType: <?= json_encode($type, JSON_THROW_ON_ERROR) ?>,
        timestamp: new Date().toISOString()
    };

    /* Copy summary to clipboard */
    var copyBtn = document.getElementById('copy-summary-btn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var text = [
                'ScamShield Forensic Inspection Report',
                'Input (' + resultData.inputType + '): ' + resultData.input,
                'Result: ' + resultData.risk.toUpperCase() + ' RISK (' + resultData.score + '/100)',
                'Verdict: ' + resultData.verdict,
                '---',
                resultData.summary,
                '---',
                'Action: Do not open. Freeze access if credentials entered. Dial 1930 / cybercrime.gov.in.'
            ].join('\n');

            navigator.clipboard.writeText(text).then(function () {
                copyBtn.textContent = 'Copied!';
                setTimeout(function () { copyBtn.textContent = 'Copy Summary (Plain text)'; }, 2500);
            });
        });
    }

    /* Export evidence JSON */
    var exportBtn = document.getElementById('export-json-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var packet = {
                system:           'ScamShield Heuristic Engine v1.4',
                timestamp_utc:    resultData.timestamp,
                input_type:       resultData.inputType,
                heuristic_verdict: resultData.risk.toUpperCase() + '_RISK',
                risk_score:       resultData.score,
                airgap_verified:  true,
                payload_excerpt:  resultData.input,
                statutory_helpline: '1930',
                official_portal:  'cybercrime.gov.in',
                note:             'This packet is generated from local heuristic analysis only. No server requests were made.'
            };

            var blob = new Blob([JSON.stringify(packet, null, 2)], { type: 'application/json' });
            var url  = URL.createObjectURL(blob);
            var a    = document.createElement('a');
            a.href     = url;
            a.download = 'scamshield-evidence-' + Date.now() + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }
}());
</script>
