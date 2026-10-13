<?php
use App\Support\Csrf;
use App\Support\Session;
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');

$incident   = $incident   ?? [];
$evidence   = $evidence   ?? [];
$history    = $history    ?? [];
$uploadError= $uploadError ?? null;
$success    = $success    ?? null;
$ref        = $e($incident['public_reference'] ?? '');
$isAdmin    = Session::isAdmin();

$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'info_needed'  => 'Info Needed',
    'closed'       => 'Closed',
];
$status = $incident['status'] ?? 'submitted';
$badgeClass = match($status) {
    'under_review' => 'badge-verified',
    'closed'       => 'badge-neutral',
    default        => 'badge-alert',
};
$allowedMime = 'JPG, PNG, GIF, PDF, TXT, CSV';
?>
<div class="container" style="padding:var(--space-xl) var(--space-md);">

    <!-- Header -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);margin-bottom:var(--space-lg);">
        <div>
            <a href="<?= $isAdmin ? '/admin/incidents' : '/incidents' ?>" class="btn-tertiary" style="display:inline-block;margin-bottom:var(--space-sm);">← Back</a>
            <h1 class="t-headline-lg">Incident <code class="t-code-lg"><?= $ref ?></code></h1>
            <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">
                This is a ScamShield record — not an official police complaint.
            </p>
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

    <!-- Incident details -->
    <div class="card card--accent-neutral" style="margin-bottom:var(--space-lg);">
        <div class="card-header"><h2 class="t-headline-sm">Incident Details</h2></div>
        <div class="card-body">
            <dl style="display:grid;grid-template-columns:max-content 1fr;gap:var(--space-xs) var(--space-lg);">
                <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Type</dt>
                <dd class="t-body-md"><?= $e(ucwords(str_replace('_', ' ', $incident['incident_type'] ?? ''))) ?></dd>

                <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Incident date</dt>
                <dd class="t-body-md"><?= $incident['incident_at'] ? $e(substr($incident['incident_at'], 0, 10)) : '<span style="color:var(--on-surface-variant);">Not specified</span>' ?></dd>

                <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Channel</dt>
                <dd class="t-body-md t-code-md" style="word-break:break-all;"><?= $incident['channel'] ? $e($incident['channel']) : '<span style="color:var(--on-surface-variant);">Not specified</span>' ?></dd>

                <dt class="t-body-sm" style="color:var(--on-surface-variant);font-weight:600;">Submitted</dt>
                <dd class="t-body-md"><?= $e(substr($incident['created_at'] ?? '', 0, 16)) ?></dd>
            </dl>
            <div style="margin-top:var(--space-md);padding-top:var(--space-md);border-top:var(--border-hairline);">
                <p class="t-body-sm" style="font-weight:600;color:var(--on-surface-variant);margin-bottom:4px;">Description</p>
                <p class="t-body-md" style="white-space:pre-wrap;"><?= $e($incident['description'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <div class="grid-12" style="align-items:start;">

        <!-- Evidence column -->
        <div class="col-8">
            <div class="card card--accent-neutral" style="margin-bottom:var(--space-md);">
                <div class="card-header">
                    <h2 class="t-headline-sm">Evidence Files</h2>
                    <span class="badge badge-neutral"><?= count($evidence) ?> file<?= count($evidence) !== 1 ? 's' : '' ?></span>
                </div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($evidence)): ?>
                        <div class="empty-state" style="margin:var(--space-md);">No evidence files attached yet.</div>
                    <?php else: ?>
                        <table class="data-table" aria-label="Evidence files">
                            <thead><tr>
                                <th scope="col">Filename</th>
                                <th scope="col">Type</th>
                                <th scope="col">Size</th>
                                <th scope="col">Uploaded</th>
                                <th scope="col"><span class="sr-only">Download</span></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($evidence as $ev): ?>
                                <tr>
                                    <td class="t-code-sm"><?= $e($ev['original_name']) ?></td>
                                    <td class="t-body-sm"><?= $e($ev['detected_mime']) ?></td>
                                    <td class="t-body-sm"><?= $e(number_format((int)$ev['size_bytes'] / 1024, 1)) ?> KB</td>
                                    <td class="t-body-sm"><?= $e(substr($ev['created_at'], 0, 10)) ?></td>
                                    <td><a href="/evidence/<?= (int)$ev['id'] ?>/download" class="btn-tertiary">↓ Download</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upload form (only if not closed) -->
            <?php if ($status !== 'closed'): ?>
            <div class="card" style="border:var(--border-hairline);">
                <div class="card-header"><h3 class="t-headline-sm">Upload Evidence</h3></div>
                <div class="card-body">
                    <?php if ($uploadError): ?>
                        <div class="error-state" role="alert" style="margin-bottom:var(--space-md);"><?= $e($uploadError) ?></div>
                    <?php endif; ?>
                    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-bottom:var(--space-md);">
                        Allowed: <?= $allowedMime ?> — max 10 MB per file.
                        Do not upload files containing passwords, banking credentials, or personal identity documents.
                    </p>
                    <form method="POST" action="/incidents/<?= $ref ?>/evidence" enctype="multipart/form-data">
                        <?= Csrf::field() ?>
                        <div class="form-field" style="margin-bottom:var(--space-md);">
                            <label class="form-label" for="evidence_file">Select file</label>
                            <input class="form-input" type="file" id="evidence_file" name="evidence_file"
                                   accept=".jpg,.jpeg,.png,.gif,.pdf,.txt,.csv" required>
                        </div>
                        <button class="btn btn-secondary" type="submit">Upload File</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Status history column -->
        <aside class="col-4">
            <div class="card" style="border:var(--border-hairline);">
                <div class="card-header"><h2 class="t-headline-sm">Status History</h2></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($history)): ?>
                        <div class="empty-state" style="margin:var(--space-md);font-size:var(--text-body-sm);">No status changes yet.</div>
                    <?php else: ?>
                        <ol style="list-style:none;padding:var(--space-sm) var(--space-md);display:flex;flex-direction:column;gap:var(--space-sm);">
                        <?php foreach ($history as $h): ?>
                            <li style="border-left:2px solid var(--color-charcoal-tint-15);padding-left:var(--space-sm);">
                                <span class="t-code-sm" style="color:var(--on-surface-variant);"><?= $e(substr($h['created_at'], 0, 16)) ?></span><br>
                                <span class="t-body-sm"><strong><?= $e($h['display_name'] ?? 'System') ?></strong>
                                changed status from <em><?= $e($h['old_status']) ?></em> to <strong><?= $e($h['new_status']) ?></strong></span>
                                <?php if ($h['reason'] !== ''): ?>
                                    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:2px;"><?= $e($h['reason']) ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Official reporting reminder -->
            <div class="limitations-box" style="margin-top:var(--space-md);">
                <p class="t-body-sm"><strong>For live fraud or emergency:</strong><br>
                Dial <strong>1930</strong> or file at
                <a href="https://cybercrime.gov.in" rel="noopener noreferrer" target="_blank">cybercrime.gov.in</a>.<br>
                This record is not an official complaint.</p>
            </div>
        </aside>

    </div><!-- /.grid-12 -->
</div>
