<?php
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
$incidents    = $incidents    ?? [];
$statusFilter = $statusFilter ?? null;
$success      = $success      ?? null;

$statusLabels = [
    'submitted'    => 'Submitted',
    'under_review' => 'Under Review',
    'info_needed'  => 'Info Needed',
    'closed'       => 'Closed',
];
$badgeFor = fn($s) => match($s) {
    'under_review' => 'badge-verified',
    'closed'       => 'badge-neutral',
    default        => 'badge-alert',
};
?>
<div class="container" style="padding:var(--space-xl) var(--space-md);">

    <h1 class="t-headline-lg" style="margin-bottom:var(--space-sm);">Admin — Incident Queue</h1>
    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">
        Admin access only. All actions are logged.
        <a href="/admin/audit" style="margin-left:var(--space-md);">View Audit Log →</a>
    </p>

    <?php if ($success): ?>
        <div class="info-strip" role="status" style="margin-bottom:var(--space-md);background:var(--color-blue-tint-8);border-color:var(--color-blue);">
            <?= $e($success) ?>
        </div>
    <?php endif; ?>

    <!-- Status filter tabs -->
    <nav aria-label="Filter by status" style="display:flex;gap:var(--space-xs);flex-wrap:wrap;margin-bottom:var(--space-lg);">
        <?php
        $filters = [null => 'All'] + $statusLabels;
        foreach ($filters as $val => $label):
            $active = ($statusFilter === $val || ($val === null && $statusFilter === null));
            $href = $val !== null ? '/admin/incidents?status=' . urlencode($val) : '/admin/incidents';
        ?>
            <a href="<?= $e($href) ?>"
               class="btn <?= $active ? 'btn-primary btn-sm' : 'btn-secondary btn-sm' ?>"
               <?= $active ? 'aria-current="page"' : '' ?>>
                <?= $e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (empty($incidents)): ?>
        <div class="empty-state">No incidents found<?= $statusFilter ? ' with status "' . $e($statusLabels[$statusFilter] ?? $statusFilter) . '"' : '' ?>.</div>
    <?php else: ?>
        <div class="overflow-x">
            <table class="data-table" aria-label="Incident queue">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">User</th>
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
                        <td class="t-body-sm"><?= $e($inc['display_name'] ?? '') ?><br>
                            <span style="color:var(--on-surface-variant);"><?= $e($inc['email'] ?? '') ?></span>
                        </td>
                        <td class="t-body-sm"><?= $e(ucwords(str_replace('_', ' ', $inc['incident_type']))) ?></td>
                        <td>
                            <span class="badge <?= $badgeFor($inc['status']) ?>">
                                <?= $e($statusLabels[$inc['status']] ?? $inc['status']) ?>
                            </span>
                        </td>
                        <td class="t-body-sm"><?= $e(substr($inc['created_at'], 0, 10)) ?></td>
                        <td>
                            <a href="/admin/incidents/<?= $e(urlencode($inc['public_reference'])) ?>" class="btn-tertiary">
                                Review →
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
