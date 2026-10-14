<?php
use App\Support\Csrf;
use App\Support\Session;
use App\Support\Router;
$base = Router::basePath(); // '' for vhost root, '/AntiScam/public' for subfolder
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <!-- CSRF token available to JS for any future fetch()-based requests -->
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

    <title><?= htmlspecialchars($title ?? 'ScamShield', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?> — ScamShield</title>

    <link rel="stylesheet" href="<?= $base ?>/assets/css/tokens.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/components.css">

    <?php if (!empty($pageCss)): ?>
        <?php foreach ((array)$pageCss as $cssFile): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($cssFile, ENT_QUOTES, 'UTF-8') ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<?php include dirname(__DIR__) . '/partials/warning-strip.php'; ?>

<header class="site-header" role="banner">
    <?php
    // Pass auth state to nav partial
    $navUser    = Session::user();
    $navIsAdmin = Session::isAdmin();
    include dirname(__DIR__) . '/partials/nav.php';
    ?>
</header>

<a href="#main-content" class="skip-link">Skip to main content</a>

<main id="main-content" tabindex="-1">
<?php
// Flash messages (success / info) displayed globally
$_flashSuccess = Session::getFlash('success');
$_flashError   = Session::getFlash('error');
if ($_flashSuccess): ?>
    <div class="container" style="padding-top:var(--space-md);">
        <div class="info-strip" role="status" style="background:var(--color-blue-tint-8);border-color:var(--color-blue);">
            <?= htmlspecialchars($_flashSuccess, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?>
        </div>
    </div>
<?php endif;
if ($_flashError): ?>
    <div class="container" style="padding-top:var(--space-md);">
        <div class="error-state" role="alert">
            <?= htmlspecialchars($_flashError, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8') ?>
        </div>
    </div>
<?php endif; ?>
    <?= $content ?? '' ?>
</main>

<?php include dirname(__DIR__) . '/partials/footer.php'; ?>

<script>document.body.classList.add('js-enabled');</script>

<?php if (!empty($pageJs)): ?>
    <?php foreach ((array)$pageJs as $jsFile): ?>
        <script src="<?= htmlspecialchars($jsFile, ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>

<style>
.site-header {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background-color: var(--surface-container-lowest);
    border-bottom: var(--border-hairline);
}
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
}
.skip-link:focus { top: var(--space-sm); }
</style>
