<?php
/**
 * Partial: findings-list.php
 *
 * Numbered findings breakdown from UrlAnalysisService.
 * Each finding shows: tag badge, title, explanation, weight, severity, ref.
 *
 * Expects $result array from UrlAnalysisService in scope.
 * Also renders the audit log table at the bottom.
 */
$findings = $result['findings'] ?? [];
$auditLog = $result['audit_log'] ?? [];
$score    = (int)($result['score']  ?? 0);
?>

<!-- FINDINGS SECTION ─────────────────────────────────────────────────── -->
<div class="card" style="border:var(--border-hairline);">
    <div class="card-header">
        <div>
            <h2 class="t-headline-md">Forensic Inspection Log</h2>
            <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">
                Direct heuristic triggers indexed against the target specimen above.
            </p>
        </div>
        <span class="badge badge-neutral">
            <?= count($findings) ?> RULE<?= count($findings) === 1 ? '' : 'S' ?> MATCHED
        </span>
    </div>

    <div class="card-body" style="padding:0;">

        <?php if (empty($findings)): ?>
            <div class="empty-state" style="margin:var(--space-md);">
                <p>No heuristic rules triggered for this input.</p>
                <p class="t-body-sm" style="margin-top:var(--space-xs);color:var(--on-surface-variant);">
                    <strong>This is not a safety guarantee.</strong>
                    Novel domains and recently created phishing pages may not yet match known patterns.
                </p>
            </div>
        <?php else: ?>

            <div class="findings-list" style="gap:0;">

                <?php foreach ($findings as $finding): ?>
                    <?php
                    $severity    = $finding['severity'] ?? 'low';
                    $badgeClass  = match($severity) {
                        'high'   => 'badge-danger',
                        'medium' => 'badge-neutral',
                        default  => 'badge badge-severity-low',
                    };
                    $severityLabel = match($severity) {
                        'high'   => 'HIGH SEVERITY',
                        'medium' => 'MEDIUM SEVERITY',
                        default  => 'LOW SEVERITY',
                    };
                    $weight = (int)($finding['weight'] ?? 0);
                    ?>
                    <div class="finding-item" style="border-radius:0;border-top:none;border-left:none;border-right:none;">

                        <div class="finding-header">
                            <div style="display:flex;align-items:center;gap:var(--space-xs);flex-wrap:wrap;">
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($finding['tag'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <h3 class="finding-title t-headline-sm">
                                    <?= htmlspecialchars($finding['number'] . '. ' . ($finding['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                            </div>
                            <span class="badge badge-neutral" style="white-space:nowrap;">
                                <?= htmlspecialchars($severityLabel, ENT_QUOTES, 'UTF-8') ?>
                                &bull; +<?= $weight ?> PTS
                            </span>
                        </div>

                        <p class="finding-body">
                            <?= htmlspecialchars($finding['body'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <?php if (!empty($finding['ref'])): ?>
                            <div class="finding-meta">
                                <span aria-hidden="true" style="font-size:.8rem;">📋</span>
                                <span><?= htmlspecialchars($finding['ref'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

            </div><!-- /.findings-list -->

        <?php endif; ?>
    </div><!-- /.card-body -->
</div><!-- /.card -->


<!-- AUDIT LOG ────────────────────────────────────────────────────────── -->
<?php if (!empty($auditLog)): ?>
<div class="card" style="border:var(--border-hairline);margin-top:var(--space-md);">
    <div class="card-header">
        <span class="t-code-sm" style="text-transform:uppercase;letter-spacing:.06em;color:var(--on-surface-variant);">
            Rule Engine Audit Log
        </span>
        <span class="t-code-sm" style="color:var(--on-secondary-container);">DETERMINISTIC PIPELINE: PASS</span>
    </div>
    <div class="card-body" style="padding:0;">
        <div class="audit-log">
            <?php foreach ($auditLog as $row): ?>
                <div class="audit-row">
                    <span class="t-code-sm" style="color:var(--primary-container);font-weight:600;min-width:200px;">
                        <?= htmlspecialchars('[TRIGGER: ' . ($row['id'] ?? '') . ']', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="t-code-sm" style="color:var(--on-surface-variant);flex:1;">
                        <?= htmlspecialchars($row['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="t-code-sm" style="font-weight:600;color:var(--on-surface);">
                        +<?= (int)($row['weight'] ?? 0) ?>.00
                    </span>
                </div>
            <?php endforeach; ?>

            <div class="audit-total">
                <span class="t-code-sm">CUMULATIVE HEURISTIC WEIGHT:</span>
                <span class="t-code-sm" style="color:var(--primary);">
                    <?= $score ?> / 100
                    [<?= strtoupper(htmlspecialchars($result['risk_level'] ?? 'LOW', ENT_QUOTES, 'UTF-8')) ?> RISK]
                </span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
