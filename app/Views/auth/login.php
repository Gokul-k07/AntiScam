<?php
use App\Support\Csrf;
$e     = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
$error = $error ?? null;
$old   = $old   ?? [];
?>
<div class="container" style="max-width:480px;padding:var(--space-xl) var(--space-md);">

    <h1 class="t-headline-lg" style="margin-bottom:var(--space-xs);">Sign in</h1>
    <p class="t-body-md" style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">
        New here? <a href="/register">Create an account</a>
    </p>

    <?php if ($error !== null): ?>
        <div class="error-state" role="alert" style="margin-bottom:var(--space-md);">
            <?= $e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/login" novalidate>
        <?= Csrf::field() ?>

        <div class="form-field" style="margin-bottom:var(--space-md);">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email"
                   value="<?= $e($old['email'] ?? '') ?>"
                   autocomplete="email" required>
        </div>

        <div class="form-field" style="margin-bottom:var(--space-lg);">
            <label class="form-label" for="password">Password</label>
            <input class="form-input" type="password" id="password" name="password"
                   autocomplete="current-password" required>
        </div>

        <button class="btn btn-primary btn-block" type="submit">Sign In</button>
    </form>
</div>
