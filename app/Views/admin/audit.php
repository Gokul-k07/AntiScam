<?php
$e       = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
$entries = $entries ?? [];
?>
<div class="container" style="padding:var(--space-xl) var(--space-md);">

    <div style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:var(--space-md);margin-bottom:var(--space-lg);">
        <h1 class="t-headline-lg">Audit Log</h1>
        <a href="/admin/incidents" class="btn-tertiary">← Back to Queue</a>
    </div>

    <p class="t-body-sm" style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">
        Showing last 200 entries. This log is append-only; entries cannot be edited or deleted.
    </p>

    <?php if (empty($entries)): ?>
        <div class="empty-state">No audit entries yet.</div>
    <?php else: ?>
        <div class="overflow-x">
            <table class="data-table" aria-label="Audit log entries">
                <thead>
                    <tr>
                        <th scope="col">Timestamp (UTC)</th>
                        <th scope="col">Actor</th>
                        <th scope="col">Action</th>
                        <th scope="col">Target</th>
                        <th scope="col">IP</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $row): ?>
                    <tr>
                        <td class="t-code-sm"><?= $e(substr($row['created_at'], 0, 16)) ?></td>
                        <td class="t-body-sm">
                            <?php if ($row['actor_user_id']): ?>
                                <?= $e($row['display_name'] ?? 'User #' . $row['actor_user_id']) ?>
                                <span style="color:var(--on-surface-variant);"> #<?= (int)$row['actor_user_id'] ?></span>
                            <?php else: ?>
                                <span style="color:var(--on-surface-variant);">System / Anon</span>
                            <?php endif; ?>
                        </td>
                        <td><code class="t-code-sm"><?= $e($row['action']) ?></code></td>
                        <td class="t-body-sm">
                            <?php if ($row['target_type']): ?>
                                <?= $e($row['target_type']) ?> #<?= (int)$row['target_id'] ?>
                            <?php else: ?>
                                <span style="color:var(--on-surface-variant);">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="t-code-sm" style="color:var(--on-surface-variant);"><?= $e($row['ip_address'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
