<?php
/**
 * Partial: risk-meter.php
 *
 * 4-tier geometric risk meter.
 * Risk level conveyed by TEXT label + SHAPE icon + position — NOT color alone (a11y).
 *
 * Expects $result array from UrlAnalysisService to be in scope:
 *   $result['risk_level'] : 'low' | 'moderate' | 'high' | 'critical'
 *   $result['score']      : int 0–100
 */
$level = $result['risk_level'] ?? 'low';
$score = (int)($result['score'] ?? 0);

$steps = [
    [
        'key'    => 'low',
        'label'  => 'Low',
        'icon'   => '○',  /* circle — no sides, open, minimal threat */
        'range'  => '0–24 pts',
        'aria'   => 'Low risk, 0 to 24 points',
    ],
    [
        'key'    => 'moderate',
        'label'  => 'Moderate',
        'icon'   => '◇',  /* diamond — rotated square, elevated attention */
        'range'  => '25–54 pts',
        'aria'   => 'Moderate risk, 25 to 54 points',
    ],
    [
        'key'    => 'high',
        'label'  => 'High',
        'icon'   => '▲',  /* triangle — pointing up, escalation */
        'range'  => '55–84 pts',
        'aria'   => 'High risk, 55 to 84 points',
    ],
    [
        'key'    => 'critical',
        'label'  => 'Critical',
        'icon'   => '⬡',  /* hexagon — complex, multi-faceted danger */
        'range'  => '85–100 pts',
        'aria'   => 'Critical risk, 85 to 100 points',
    ],
];
?>

<div
    class="risk-meter"
    role="img"
    aria-label="Risk level: <?= htmlspecialchars(ucfirst($level), ENT_QUOTES, 'UTF-8') ?>, score <?= $score ?> out of 100"
>
    <?php foreach ($steps as $step): ?>
        <?php
        $isActive = ($step['key'] === $level);
        $classes  = 'risk-step';
        if ($isActive) {
            $classes .= ($level === 'critical') ? ' risk-step--critical-active' : ' risk-step--active';
        }
        ?>
        <div
            class="<?= htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($step['aria'], ENT_QUOTES, 'UTF-8') ?>"
            <?= $isActive ? 'aria-current="true"' : '' ?>
        >
            <span class="risk-step__icon" aria-hidden="true"><?= $step['icon'] ?></span>
            <span class="risk-step__label"><?= htmlspecialchars($step['label'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="risk-step__range"><?= htmlspecialchars($step['range'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endforeach; ?>
</div>

<p class="t-code-sm" style="text-align:right;margin-top:var(--space-sm);color:var(--on-surface-variant);">
    Active tier:
    <strong style="color:<?= $level === 'critical' ? 'var(--error)' : 'var(--primary-container)' ?>;">
        <?= htmlspecialchars(ucfirst($level), ENT_QUOTES, 'UTF-8') ?>
        (Tier <?= $level === 'low' ? '1' : ($level === 'moderate' ? '2' : ($level === 'high' ? '3' : '4')) ?>/4)
    </strong>
</p>
