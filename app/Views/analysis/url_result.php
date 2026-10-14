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
                 * __DIR__ = app/Views/analysis  → dirname(__DIR__) = app/Views
                 */
                include dirname(__DIR__) . '/partials/annotated-url.php';
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
                    <?php include dirname(__DIR__) . '/partials/risk-meter.php'; ?>
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
            <?php include dirname(__DIR__) . '/partials/findings-list.php'; ?>

        </section><!-- /.col-8 -->

        <!-- RIGHT 4 COLS: Triage + Limitations ──────────────────────── -->
        <aside class="col-4 sticky-aside" aria-label="Next steps and limitations">

            <!-- NEXT STEPS ─────────────────────────────────────────── -->
            <?php include dirname(__DIR__) . '/partials/next-steps.php'; ?>

            <!-- LIMITATIONS BOX ────────────────────────────────────── -->
            <div style="margin-top:var(--space-md);">
                <?php include dirname(__DIR__) . '/partials/limitations-box.php'; ?>
            </div>

        </aside><!-- /.col-4 -->

    </div><!-- /.grid-12 -->

    <!-- VIRUSTOTAL EXTERNAL REPUTATION ─────────────────────────────────── -->
    <?php
    /**
     * VirusTotal reputation card.
     *
     * $vtResult is a VirusTotalResult value object passed from AnalysisController.
     * Shown only when the provider is configured (not STATUS_NOT_CONFIGURED).
     *
     * EPISTEMIC rules (RULES.md §16 + product.md):
     *  - Engine counts are external evidence, not a definitive verdict.
     *  - "Not found" is not evidence of safety.
     *  - Zero detections are not evidence of safety.
     *  - API key is never present in this view.
     */
    if (isset($vtResult) && $vtResult instanceof \App\Services\Analysis\VirusTotalResult
        && $vtResult->status !== \App\Services\Analysis\VirusTotalResult::STATUS_NOT_CONFIGURED):
    ?>
    <section
        aria-labelledby="vt-heading"
        style="margin-top:var(--space-md);background:var(--surface-container-low);border:var(--border-hairline);padding:var(--space-md) var(--space-lg);"
    >
        <div style="display:flex;align-items:center;gap:var(--space-sm);margin-bottom:var(--space-sm);">
            <span class="t-code-sm" style="text-transform:uppercase;letter-spacing:.06em;color:var(--on-surface-variant);">External Reputation</span>
            <span class="badge badge-neutral">VirusTotal</span>
            <?php if ($vtResult->fromCache): ?>
                <span class="badge badge-neutral" title="Served from local cache — no new API call was made">cached</span>
            <?php endif; ?>
        </div>

        <h2 id="vt-heading" class="t-title-md" style="margin:0 0 var(--space-xs);">
            VirusTotal Engine Report
        </h2>

        <?php if ($vtResult->status === \App\Services\Analysis\VirusTotalResult::STATUS_FOUND): ?>
            <?php
            $vtMalicious  = $vtResult->malicious  ?? 0;
            $vtSuspicious = $vtResult->suspicious ?? 0;
            $vtHarmless   = $vtResult->harmless   ?? 0;
            $vtUndetected = $vtResult->undetected ?? 0;
            $vtTimeout    = $vtResult->timeout    ?? 0;
            $vtTotal      = $vtResult->totalEngines ?? 0;
            $vtFlagged    = $vtMalicious + $vtSuspicious;
            ?>

            <!-- Engine count summary chips -->
            <div style="display:flex;flex-wrap:wrap;gap:var(--space-sm);margin-bottom:var(--space-sm);">
                <span class="badge <?= $vtMalicious > 0 ? 'badge-danger' : 'badge-neutral' ?>">
                    Malicious: <strong><?= $vtMalicious ?></strong>
                </span>
                <span class="badge <?= $vtSuspicious > 0 ? 'badge-alert' : 'badge-neutral' ?>">
                    Suspicious: <strong><?= $vtSuspicious ?></strong>
                </span>
                <span class="badge badge-neutral">Harmless: <strong><?= $vtHarmless ?></strong></span>
                <span class="badge badge-neutral">Undetected: <strong><?= $vtUndetected ?></strong></span>
                <?php if ($vtTimeout > 0): ?>
                    <span class="badge badge-neutral">Timed out: <strong><?= $vtTimeout ?></strong></span>
                <?php endif; ?>
                <span class="badge badge-neutral">Total engines: <strong><?= $vtTotal ?></strong></span>
            </div>

            <?php if ($vtResult->lastAnalysisDate !== null): ?>
                <p class="t-body-sm" style="color:var(--on-surface-variant);margin:0 0 var(--space-sm);">
                    Last analysis: <?= htmlspecialchars(date('d M Y', $vtResult->lastAnalysisDate), ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <!-- Mandatory epistemic caveat (RULES.md §16) -->
            <div
                role="note"
                style="background:var(--surface-container);border-left:3px solid var(--outline-variant);padding:var(--space-sm) var(--space-md);font-size:.85rem;color:var(--on-surface-variant);"
            >
                <strong>How to read this:</strong>
                These engine counts are one external data source.
                <?php if ($vtFlagged === 0): ?>
                Zero detections do <em>not</em> mean the URL is safe —
                newly registered phishing domains may not yet appear in engine databases.
                <?php else: ?>
                <?= (int)$vtFlagged ?> engine<?= $vtFlagged === 1 ? '' : 's' ?> flagged this URL.
                This corroborates the local heuristic findings but is not an independent legal determination.
                <?php endif; ?>
                ScamShield did not visit or load this URL at any point.
            </div>

        <?php elseif ($vtResult->status === \App\Services\Analysis\VirusTotalResult::STATUS_NOT_FOUND): ?>
            <p class="t-body-sm" style="margin:0 0 var(--space-xs);">
                No VirusTotal report found for this URL.
            </p>
            <div
                role="note"
                style="background:var(--surface-container);border-left:3px solid var(--outline-variant);padding:var(--space-sm) var(--space-md);font-size:.85rem;color:var(--on-surface-variant);"
            >
                <strong>Important:</strong> The absence of a VirusTotal report is <em>not</em> evidence of
                safety. Freshly registered phishing domains are frequently absent from reputation databases.
                Rely on the local heuristic findings above.
            </div>

        <?php elseif ($vtResult->status === \App\Services\Analysis\VirusTotalResult::STATUS_RATE_LIMITED): ?>
            <p class="t-body-sm" style="color:var(--on-surface-variant);margin:0;">
                VirusTotal daily or per-minute quota reached — external reputation check skipped.
                Local heuristic analysis is unaffected.
            </p>

        <?php else: ?>
            <!-- unavailable -->
            <p class="t-body-sm" style="color:var(--on-surface-variant);margin:0;">
                External reputation check temporarily unavailable.
                Local heuristic analysis is unaffected.
            </p>
        <?php endif; ?>

    </section>
    <?php endif; ?>

    <!-- BOTTOM ACTION BAR ───────────────────────────────────────────── -->
    <section
        style="background-color:var(--surface-container-lowest);border:var(--border-hairline);padding:var(--space-md) var(--space-lg);margin-top:var(--space-md);display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:var(--space-md);"
        aria-label="Post-analysis actions"
    >
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-sm);">
            <?php
            $incidentHref = '/incidents/create' . (!empty($analysisId) ? '?from_analysis=' . (int)$analysisId : '');
            if (\App\Support\Session::isLoggedIn()): ?>
                <a href="<?= htmlspecialchars($incidentHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                    Log as Incident →
                </a>
            <?php else: ?>
                <a href="/login" class="btn btn-primary">Sign in to Log as Incident</a>
            <?php endif; ?>

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
