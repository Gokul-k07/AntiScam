<?php
use App\Support\Csrf;
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');

$incident           = $incident           ?? [];
$history            = $history            ?? [];
$evidence           = $evidence           ?? [];
$allowedTransitions = $allowedTransitions ?? [];
$success            = $success            ?? null;
$error              = $error              ?? null;
$ref                = $e($incident['public_reference'] ?? '');

$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'info_needed'  => 'Info Needed',
    'closed'       => 'Closed',
];
$status = $incident['status'] ?? '';
$badgeClass = match($status) {
    'under_review' => 'badge-verified',
    'closed'       => 'badge-neutral',
    default        => 'badge-alert',
};
?>
<div class="container" style="padding:var(--space-xl) var(--space-md);">

    <a href="/admin/incidents" class="btn-tertiary" style="margin-bottom:var(--space-md);display:inline-block;">← Back to Queue</a>

    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);margin-bottom:var(--space-lg);">
        <div>
            <h1 class="t-headline-lg">Incident <code class="t-code-lg"><?= $ref ?></code></h1>
            <p class="t-body-sm" style="color:var(--on-surface-variant);">Admin review — all changes are recorded in the audit log.</p>
        </div>
        <span class="badge <?= $badgeClass ?>" style="font-size:var(--text-body-md);padding:var(--space-xs) var(--space-md);">
            <?= $e($statusLabels[$status] ?? $status) ?>
        </span>
    </div>

    <?php if ($success): ?>
        <div class="info-strip" role="status" style="margin-bottom:var(--space-md);background:var(--color-blue-tint-8);border-color:var(--color-blue);">
            <?= $e($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error-state" role="alert" style="margin-bottom:var(--space-md);"><?= $e($error) ?></div>
    <?php endif; ?>

    <div class="grid-12" style="align-items:start;">

        <!-- Left: details -->
        <div class="col-8">
            <div class="card card--accent-neutral" style="margin-bottom:var(--space-md);">
                <div class="card-header"><h2 class="t-headline-sm">Incident Details</h2></div>
                <div class="card-body">
                    <dl style="display:grid;grid-template-columns:max-content 1fr;gap:var(--space-xs) var(--space-lg);">
                        <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Submitted by</dt>
                        <dd class="t-body-md"><?= $e($incident['display_name'] ?? '') ?> &lt;<?= $e($incident['email'] ?? '') ?>&gt;</dd>

                        <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Type</dt>
                        <dd class="t-body-md"><?= $e(ucwords(str_replace('_', ' ', $incident['incident_type'] ?? ''))) ?></dd>

                        <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Incident date</dt>
                        <dd class="t-body-md"><?= $incident['incident_at'] ? $e(substr($incident['incident_at'], 0, 10)) : '—' ?></dd>

                        <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Channel</dt>
                        <dd class="t-body-md t-code-md" style="word-break:break-all;"><?= $incident['channel'] ? $e($incident['channel']) : '—' ?></dd>

                        <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Submitted</dt>
                        <dd class="t-body-md"><?= $e(substr($incident['created_at'] ?? '', 0, 16)) ?></dd>
                    </dl>
                    <div style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:var(--border-hairline);">
                        <p class="t-body-sm" style="font-weight:600;color:var(--on-surface-variant);margin-bottom:4px;">Description</p>
                        <p class="t-body-md" style="white-space:pre-wrap;"><?= $e($incident['description'] ?? '') ?></p>
                    </div>
                </div>
            </div>

            <!-- Evidence files -->
            <div class="card" style="border:var(--border-hairline);margin-bottom:var(--space-md);">
                <div class="card-header">
                    <h2 class="t-headline-sm">Evidence Files</h2>
                    <span class="badge badge-neutral"><?= count($evidence) ?> file<?= count($evidence) !== 1 ? 's' : '' ?></span>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($evidence)): ?>
                        <div class="empty-state" style="margin:var(--space-md);font-size:var(--text-body-sm);">No evidence files attached to this incident.</div>
                    <?php else: ?>
                        <table class="data-table" aria-label="Evidence files for incident <?= $ref ?>">
                            <thead><tr>
                                <th scope="col">Filename</th>
                                <th scope="col">Type</th>
                                <th scope="col">Size</th>
                                <th scope="col">Uploaded</th>
                                <th scope="col">SHA-256</th>
                                <th scope="col"><span class="sr-only">Action</span></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($evidence as $ev): ?>
                                <?php
                                $mime      = (string)($ev['detected_mime'] ?? '');
                                $isImage   = in_array($mime, ['image/jpeg','image/png','image/gif'], true);
                                $sha256    = (string)($ev['sha256'] ?? '');
                                $shortHash = $sha256 !== '' ? substr($sha256, 0, 12) . '…' : '—';
                                ?>
                                <tr>
                                    <td class="t-code-sm"><?= $e($ev['original_name']) ?></td>
                                    <td class="t-body-sm"><?= $e($mime) ?></td>
                                    <td class="t-body-sm" style="white-space:nowrap;"><?= $e(number_format((int)$ev['size_bytes'] / 1024, 1)) ?> KB</td>
                                    <td class="t-body-sm" style="white-space:nowrap;"><?= $e(substr((string)($ev['created_at'] ?? ''), 0, 10)) ?></td>
                                    <td class="t-code-sm" title="<?= $e($sha256) ?>"><?= $e($shortHash) ?></td>
                                    <td style="white-space:nowrap;">
                                        <a href="/evidence/<?= (int)$ev['id'] ?>/download"
                                           class="btn-tertiary"
                                           aria-label="Download <?= $e($ev['original_name']) ?>">↓ Download</a>
                                        <?php if ($isImage): ?>
                                            <a href="/evidence/<?= (int)$ev['id'] ?>/download"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="btn-tertiary"
                                               aria-label="Preview image <?= $e($ev['original_name']) ?>">⧉ Preview</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status history -->
            <div class="card" style="border:var(--border-hairline);">
                <div class="card-header"><h2 class="t-headline-sm">Status History</h2></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($history)): ?>
                        <div class="empty-state" style="margin:var(--space-md);font-size:var(--text-body-sm);">No status changes recorded yet.</div>
                    <?php else: ?>
                        <ol style="list-style:none;padding:var(--space-sm) var(--space-md);display:flex;flex-direction:column;gap:var(--space-sm);">
                        <?php foreach ($history as $h): ?>
                            <li style="border-left:2px solid var(--color-charcoal-tint-15);padding-left:var(--space-sm);">
                                <span class="t-code-sm" style="color:var(--on-surface-variant);"><?= $e(substr($h['created_at'], 0, 16)) ?></span><br>
                                <span class="t-body-sm"><strong><?= $e($h['display_name'] ?? 'System') ?></strong>:
                                <em><?= $e($h['old_status']) ?></em> → <strong><?= $e($h['new_status']) ?></strong></span>
                                <?php if ($h['reason'] !== ''): ?>
                                    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:2px;"><?= $e($h['reason']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right: status change -->
        <aside class="col-4 sticky-aside">
            <?php if (!empty($allowedTransitions)): ?>
            <div class="triage-panel" style="margin-bottom:var(--space-md);">
                <h2 class="t-headline-sm" style="margin-bottom:var(--space-md);">Change Status</h2>
                <form method="POST" action="/admin/incidents/<?= $ref ?>/status">
                    <?= Csrf::field() ?>
                    <div class="form-field" style="margin-bottom:var(--space-md);">
                        <label class="form-label" for="new_status">New status</label>
                        <select class="form-input" id="new_status" name="new_status" required>
                            <option value="">— Select —</option>
                            <?php foreach ($allowedTransitions as $ns): ?>
                                <option value="<?= $e($ns) ?>"><?= $e(ucwords(str_replace('_', ' ', $ns))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field" style="margin-bottom:var(--space-md);">
                        <label class="form-label" for="reason">Reason / notes <span style="font-weight:400;color:var(--on-surface-variant);">(optional)</span></label>
                        <textarea class="form-input" id="reason" name="reason" rows="3" maxlength="500"></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit">Update Status</button>
                </form>
            </div>
            <?php else: ?>
                <div class="limitations-box" style="margin-bottom:var(--space-md);">
                    <p class="t-body-sm">This incident is <strong>closed</strong>. No further status changes are allowed.</p>
                </div>
            <?php endif; ?>

            <div class="limitations-box">
                <p class="t-body-sm">
                    Admin review within ScamShield is <strong>not</strong> a law-enforcement investigation
                    or official complaint. Direct the user to
                    <a href="https://cybercrime.gov.in" rel="noopener noreferrer" target="_blank">cybercrime.gov.in</a>
                    for official filing.
                </p>
            </div>
        </aside>

    </div><!-- /.grid-12 -->
</div>
