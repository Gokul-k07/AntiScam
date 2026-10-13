<?php
use App\Support\Csrf;
/** @var array<string,string> $errors */
/** @var array<string,string> $old */
$errors = $errors ?? [];
$old    = $old    ?? [];
$e      = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
?>
<div class="container" style="max-width:480px;padding:var(--space-xl) var(--space-md);">

    <h1 class="t-headline-lg" style="margin-bottom:var(--space-xs);">Create an account</h1>
    <p class="t-body-md" style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">
        Already have an account? <a href="/login">Sign in</a>
    </p>

    <?php if (!empty($errors['form'])): ?>
        <div class="error-state" role="alert" style="margin-bottom:var(--space-md);">
            <?= $e($errors['form']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/register" novalidate>
        <?= Csrf::field() ?>

        <div class="form-field" style="margin-bottom:var(--space-md);">
            <label class="form-label" for="display_name">Your name</label>
            <input class="form-input" type="text" id="display_name" name="display_name"
                   value="<?= $e($old['display_name'] ?? '') ?>"
                   autocomplete="name" required maxlength="120"
                   aria-describedby="<?= !empty($errors['display_name']) ? 'err-name' : '' ?>">
            <?php if (!empty($errors['display_name'])): ?>
                <span id="err-name" class="t-body-sm" style="color:var(--error);"><?= $e($errors['display_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field" style="margin-bottom:var(--space-md);">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email"
                   value="<?= $e($old['email'] ?? '') ?>"
                   autocomplete="email" required maxlength="254"
                   aria-describedby="<?= !empty($errors['email']) ? 'err-email' : '' ?>">
            <?php if (!empty($errors['email'])): ?>
                <span id="err-email" class="t-body-sm" style="color:var(--error);"><?= $e($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field" style="margin-bottom:var(--space-md);">
            <label class="form-label" for="password">Password <span style="font-weight:400;color:var(--on-surface-variant);">(min 8 characters)</span></label>
            <input class="form-input" type="password" id="password" name="password"
                   autocomplete="new-password" required minlength="8"
                   aria-describedby="<?= !empty($errors['password']) ? 'err-pw' : '' ?>">
            <?php if (!empty($errors['password'])): ?>
                <span id="err-pw" class="t-body-sm" style="color:var(--error);"><?= $e($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field" style="margin-bottom:var(--space-lg);">
            <label class="form-label" for="password_confirm">Confirm password</label>
            <input class="form-input" type="password" id="password_confirm" name="password_confirm"
                   autocomplete="new-password" required minlength="8"
                   aria-describedby="<?= !empty($errors['password_confirm']) ? 'err-pwc' : '' ?>">
            <?php if (!empty($errors['password_confirm'])): ?>
                <span id="err-pwc" class="t-body-sm" style="color:var(--error);"><?= $e($errors['password_confirm']) ?></span>
            <?php endif; ?>
        </div>

        <p class="t-body-sm" style="color:var(--on-surface-variant);margin-bottom:var(--space-md);">
            Do not enter OTPs, banking passwords, or UPI PINs anywhere in ScamShield.
        </p>

        <button class="btn btn-primary btn-block" type="submit">Create Account</button>
    </form>
</div>
