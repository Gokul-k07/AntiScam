<?php
$e         = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
$incidents = $incidents ?? [];
$success   = $success   ?? null;
$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'info_needed'  => 'Info Needed',
    'closed'       => 'Closed',
];
?>
<div class="container" style="padding:var(--space-xl) var(--space-md);">

    <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);margin-bottom:var(--space-lg);">
        <h1 class="t-headline-lg">My Incidents</h1>
        <a href="/incidents/create" class="btn btn-primary">+ Report New Incident</a>
    </div>

    <?php if ($success): ?>
        <div class="info-strip" role="status" style="margin-bottom:var(--space-md);background:var(--color-blue-tint-8);border-color:var(--color-blue);">
            <?= $e($success) ?>
        </div>
    <?php endif; ?>

    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">
        These are your ScamShield incident records. They are <strong>not</strong> official police complaints.
        For immediate financial fraud, call <strong>1930</strong> or visit
        <a href="https://cybercrime.gov.in" rel="noopener noreferrer" target="_blank">cybercrime.gov.in</a>.
    </p>

    <?php if (empty($incidents)): ?>
        <div class="empty-state">
            <p>You have no incident reports yet.</p>
            <p style="margin-top:var(--space-sm);"><a href="/incidents/create" class="btn btn-secondary">Report Your First Incident</a></p>
        </div>
    <?php else: ?>
        <div class="overflow-x">
            <table class="data-table" aria-label="Your incident reports">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Submitted</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($incidents as $inc): ?>
                    <tr>
                        <td><code class="t-code-md"><?= $e($inc['public_reference']) ?></code></td>
                        <td><?= $e(ucwords(str_replace('_', ' ', $inc['incident_type']))) ?></td>
                        <td>
                            <span class="badge <?= $inc['status'] === 'closed' ? 'badge-neutral' : ($inc['status'] === 'under_review' ? 'badge-verified' : 'badge-alert') ?>">
                                <?= $e($statusLabels[$inc['status']] ?? $inc['status']) ?>
                            </span>
                        </td>
                        <td class="t-body-sm"><?= $e(substr($inc['created_at'], 0, 10)) ?></td>
                        <td><a href="/incidents/<?= $e(urlencode($inc['public_reference'])) ?>" class="btn-tertiary">View →</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
