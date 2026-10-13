<?php
use App\Support\Csrf;
$e          = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
$errors     = $errors     ?? [];
$old        = $old        ?? [];
$analysisId = $analysisId ?? '';

$incidentTypes = [
    'phishing_url'    => 'Phishing URL / Fake Website',
    'phishing_sms'    => 'Phishing SMS / Message',
    'upi_fraud'       => 'UPI / Payment Fraud',
    'impersonation'   => 'Impersonation (Bank / Police / Official)',
    'digital_arrest'  => 'Digital Arrest / Fake Custody Threat',
    'job_scam'        => 'Part-time Job / Advance Fee Scam',
    'investment_scam' => 'Investment / Ponzi Scheme',
    'other'           => 'Other',
];
?>
<div class="container" style="max-width:720px;padding:var(--space-xl) var(--space-md);">

    <a href="/incidents" class="btn-tertiary" style="margin-bottom:var(--space-md);display:inline-block;">← Back to My Incidents</a>

    <h1 class="t-headline-lg" style="margin-bottom:var(--space-xs);">Report an Incident</h1>
    <p class="t-body-md" style="color:var(--on-surface-variant);margin-bottom:var(--space-lg);">
        This creates a record inside ScamShield only — it is <strong>not</strong> an official complaint.
        For active financial fraud, call <strong>1930</strong> immediately.
    </p>

    <?php if (!empty($errors['form'])): ?>
        <div class="error-state" role="alert" style="margin-bottom:var(--space-md);"><?= $e($errors['form']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/incidents/create" novalidate>
        <?= Csrf::field() ?>
        <?php if ($analysisId !== ''): ?>
            <input type="hidden" name="analysis_id" value="<?= $e($analysisId) ?>">
        <?php endif; ?>

        <div class="form-field" style="margin-bottom:var(--space-md);">
            <label class="form-label" for="incident_type">Incident type</label>
            <select class="form-input" id="incident_type" name="incident_type" required
                    aria-describedby="<?= !empty($errors['incident_type']) ? 'err-type' : '' ?>">
                <option value="">— Select —</option>
                <?php foreach ($incidentTypes as $val => $label): ?>
                    <option value="<?= $e($val) ?>" <?= (($old['incident_type'] ?? '') === $val) ? 'selected' : '' ?>>
                        <?= $e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['incident_type'])): ?>
                <span id="err-type" class="t-body-sm" style="color:var(--error);"><?= $e($errors['incident_type']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field" style="margin-bottom:var(--space-md);">
            <label class="form-label" for="incident_at">
                Approximate date of incident <span style="font-weight:400;color:var(--on-surface-variant);">(optional)</span>
            </label>
            <input class="form-input" type="date" id="incident_at" name="incident_at"
                   value="<?= $e($old['incident_at'] ?? '') ?>"
                   max="<?= date('Y-m-d') ?>"
                   aria-describedby="<?= !empty($errors['incident_at']) ? 'err-date' : '' ?>">
            <?php if (!empty($errors['incident_at'])): ?>
                <span id="err-date" class="t-body-sm" style="color:var(--error);"><?= $e($errors['incident_at']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-field" style="margin-bottom:var(--space-md);">
            <label class="form-label" for="channel">
                Channel used by scammer <span style="font-weight:400;color:var(--on-surface-variant);">(URL, phone number — optional)</span>
            </label>
            <input class="form-input" type="text" id="channel" name="channel"
                   value="<?= $e($old['channel'] ?? '') ?>"
                   maxlength="200" placeholder="e.g. http://sbi-kyc.in or +91 98765 43210">
        </div>

        <div class="form-field" style="margin-bottom:var(--space-lg);">
            <label class="form-label" for="description">
                Describe what happened <span style="font-weight:400;color:var(--on-surface-variant);">(at least 20 characters)</span>
            </label>
            <textarea class="form-input" id="description" name="description"
                      rows="6" required minlength="20" maxlength="5000"
                      aria-describedby="<?= !empty($errors['description']) ? 'err-desc' : '' ?>"
                      placeholder="Describe the incident. Do not include your passwords, PINs, or OTPs."><?= $e($old['description'] ?? '') ?></textarea>
            <?php if (!empty($errors['description'])): ?>
                <span id="err-desc" class="t-body-sm" style="color:var(--error);"><?= $e($errors['description']) ?></span>
            <?php endif; ?>
            <p class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">
                Do <strong>not</strong> include passwords, OTPs, UPI PINs, or full card numbers.
            </p>
        </div>

        <button class="btn btn-primary" type="submit">Submit Incident Report</button>
        <a href="/incidents" class="btn btn-secondary" style="margin-left:var(--space-sm);">Cancel</a>
    </form>
</div>
