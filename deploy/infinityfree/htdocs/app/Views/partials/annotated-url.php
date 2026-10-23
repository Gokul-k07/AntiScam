<?php
/**
 * Partial: annotated-url.php
 *
 * Monospace URL anatomy decomposition block.
 * Each token is colour-coded AND labelled with a bracketed textual tag
 * so risk is never conveyed by colour alone (accessibility requirement).
 *
 * Expects:
 *   $result['url_tokens']  : array from UrlAnalysisService::buildTokens()
 *                            or null if input was not a URL
 *   $result['input']       : original input string
 */

$tokens = $result['url_tokens'] ?? [];
$input  = $result['input']      ?? '';
$count  = 0;
foreach ($tokens as $t) {
    if (in_array($t['risk'], ['warning', 'danger'], true)) {
        $count++;
    }
}
?>

<?php if (!empty($tokens)): ?>
<div class="card" style="border:var(--border-hairline);">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:var(--space-sm);">
            <span aria-hidden="true" style="font-size:1rem;">🔍</span>
            <h2 class="t-headline-sm">Annotated URL Anatomy Breakdown</h2>
        </div>
        <?php if ($count > 0): ?>
            <span class="badge badge-alert">
                <?= $count ?> EXPLOIT TOKEN<?= $count === 1 ? '' : 'S' ?> DETECTED
            </span>
        <?php else: ?>
            <span class="badge badge-verified">NO EXPLOIT TOKENS</span>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <p class="t-body-sm" style="color:var(--on-surface-variant);margin-bottom:var(--space-sm);">
            Extracted target from submitted payload:
        </p>

        <!-- Monospace tokenised URL anatomy -->
        <div class="url-anatomy">
            <div class="url-tokens" role="list" aria-label="URL component breakdown">
                <?php foreach ($tokens as $token): ?>
                    <?php
                    $risk      = htmlspecialchars($token['risk']  ?? 'neutral', ENT_QUOTES, 'UTF-8');
                    $value     = htmlspecialchars($token['value'] ?? '',         ENT_QUOTES, 'UTF-8');
                    $label     = htmlspecialchars($token['label'] ?? '',         ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="url-token url-token--<?= $risk ?>" role="listitem">
                        <span class="url-token__value"><?= $value ?></span>
                        <span class="url-token__label"><?= $label ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Per-token findings are rendered in findings-list.php -->

    </div><!-- /.card-body -->
</div><!-- /.card -->

<?php else: ?>

<!-- No URL extracted — show raw input in a monospace block -->
<div class="card" style="border:var(--border-hairline);">
    <div class="card-header">
        <h2 class="t-headline-sm">Inspected Payload</h2>
        <span class="badge badge-neutral">TEXT PAYLOAD</span>
    </div>
    <div class="card-body">
        <div class="url-anatomy">
            <code class="t-code-md" style="color:var(--on-surface);word-break:break-all;">
                <?= htmlspecialchars(mb_substr($input, 0, 500), ENT_QUOTES, 'UTF-8') ?>
                <?php if (mb_strlen($input) > 500): ?>
                    <span style="color:var(--on-surface-variant);">… [truncated]</span>
                <?php endif; ?>
            </code>
        </div>
    </div>
</div>

<?php endif; ?>
