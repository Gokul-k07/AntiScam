<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

    <title><?= htmlspecialchars($title ?? 'ScamShield', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> — ScamShield</title>

    <!-- Design system CSS (no CDN frameworks) -->
    <link rel="stylesheet" href="/assets/css/tokens.css">
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/components.css">

    <!-- Per-page CSS injected by controller (optional) -->
    <?php if (!empty($pageCss)): ?>
        <?php foreach ((array)$pageCss as $cssFile): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($cssFile, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<!-- ===== System warning strip (always visible, non-dismissible) ===== -->
<?php include dirname(__DIR__) . '/partials/warning-strip.php'; ?>

<!-- ===== Sticky site header (nav) ===== -->
<header class="site-header" role="banner">
    <?php include dirname(__DIR__) . '/partials/nav.php'; ?>
</header>

<!-- ===== Skip to main (accessibility) ===== -->
<a href="#main-content" class="skip-link">Skip to main content</a>

<!-- ===== Main content ===== -->
<main id="main-content" tabindex="-1">
    <?= $content ?? '' ?>
</main>

<!-- ===== Footer ===== -->
<?php include dirname(__DIR__) . '/partials/footer.php'; ?>

<!-- Minimal inline JS: mark body as JS-enabled for progressive enhancement -->
<script>document.body.classList.add('js-enabled');</script>

<!-- Per-page JS injected by controller (optional) -->
<?php if (!empty($pageJs)): ?>
    <?php foreach ((array)$pageJs as $jsFile): ?>
        <script src="<?= htmlspecialchars($jsFile, ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>

<style>
/* Layout: sticky header + warning strip */
.site-header {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background-color: var(--surface-container-lowest);
    border-bottom: var(--border-hairline);
}

/* Skip-to-main link (visually hidden until focused) */
.skip-link {
    position: absolute;
    top: -100px;
    left: var(--space-md);
    background: var(--color-blue);
    color: #fff;
    padding: var(--space-xs) var(--space-md);
    font-family: var(--font-mono);
    font-size: var(--text-code-sm);
    z-index: 9999;
    text-decoration: none;
    border-radius: var(--radius-micro);
    transition: top 0.1s ease;
}
.skip-link:focus {
    top: var(--space-sm);
}
</style>
