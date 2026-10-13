<?php
/**
 * Partial: nav.php
 *
 * Main navigation bar: ScamShield logo + nav links + helpline.
 * Matches the Stitch heuristic_analyzer screen nav.
 *
 * Expects $currentPage (string) to be in scope to mark the active link.
 * Falls back gracefully if not set.
 */
$currentPage = $currentPage ?? '';
?>
<div class="site-nav" role="navigation" aria-label="Main navigation">
    <div class="container site-nav__inner">

        <!-- Logo + brand -->
        <a href="/" class="site-nav__brand" aria-label="ScamShield — home">
            <span class="site-nav__brand-icon" aria-hidden="true">
                <!-- SVG shield icon — no external image dependency -->
                <svg width="28" height="28" viewBox="0 0 28 28" fill="none" aria-hidden="true" focusable="false">
                    <path d="M14 2L4 6v8c0 6 4.5 10.5 10 12 5.5-1.5 10-6 10-12V6L14 2z"
                          fill="var(--primary-container)" stroke="var(--on-primary-container)" stroke-width="1.5"/>
                    <path d="M10 14l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter"/>
                </svg>
            </span>
            <span class="site-nav__brand-name t-headline-sm">ScamShield</span>
            <span class="site-nav__brand-tag t-code-sm" aria-label="India utility version 1.4">[IN-UTILITY v1.4]</span>
        </a>

        <!-- Primary nav links -->
        <nav class="site-nav__links" aria-label="Site pages">
            <a href="/"
               class="site-nav__link <?= ($currentPage ?? '') === 'home' ? 'is-active' : '' ?>"
               <?= ($currentPage ?? '') === 'home' ? 'aria-current="page"' : '' ?>>
                Analyzer
            </a>
            <span class="site-nav__link site-nav__link--disabled"
                  aria-disabled="true"
                  title="Coming soon">Pattern Registry</span>
            <?php if (!empty($navUser)): ?>
            <a href="/incidents"
               class="site-nav__link <?= ($currentPage ?? '') === 'incidents' ? 'is-active' : '' ?>"
               <?= ($currentPage ?? '') === 'incidents' ? 'aria-current="page"' : '' ?>>
                My Incidents
            </a>
            <?php else: ?>
            <span class="site-nav__link site-nav__link--disabled"
                  aria-disabled="true"
                  title="Sign in to access incidents">Incident Log</span>
            <?php endif; ?>
            <a href="https://cybercrime.gov.in"
               class="site-nav__link"
               rel="noopener noreferrer"
               target="_blank">
                Report to cybercrime.gov.in
            </a>
        </nav>

        <!-- Right cluster: helpline + auth -->
        <div class="site-nav__right">
            <div class="site-nav__helpline t-code-sm" aria-label="Citizen Cyber Helpline 1930">
                <span class="site-nav__helpline-dot" aria-hidden="true"></span>
                Helpline: <strong>1930</strong>
            </div>
            <?php if (!empty($navUser)): ?>
                <?php if (!empty($navIsAdmin)): ?>
                    <a href="/admin/incidents" class="t-code-sm site-nav__ext-link" style="color:var(--primary-container);">[ADMIN]</a>
                <?php endif; ?>
                <span class="t-code-sm" style="color:var(--on-surface-variant);">
                    <?= htmlspecialchars($navUser['display_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <a href="/logout" class="btn btn-secondary btn-sm">Sign Out</a>
            <?php else: ?>
                <a href="/login"    class="t-code-sm site-nav__ext-link">Sign In</a>
                <a href="/register" class="btn btn-secondary btn-sm">Register</a>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
.site-nav {
    background-color: var(--surface-container-lowest);
    border-bottom: var(--border-hairline);
    height: 64px;
    display: flex;
    align-items: center;
}
.site-nav__inner {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    height: 100%;
}
.site-nav__brand {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    text-decoration: none;
    color: var(--on-surface);
    flex-shrink: 0;
}
.site-nav__brand:hover { text-decoration: none; }
.site-nav__brand-name { color: var(--on-surface); }
.site-nav__brand-tag {
    display: none;
    padding: 2px 8px;
    background-color: var(--surface-container);
    color: var(--on-secondary-container);
    border: var(--border-hairline);
}
@media (min-width: 1024px) { .site-nav__brand-tag { display: inline; } }

.site-nav__links {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    height: 100%;
    flex: 1;
}
@media (max-width: 767px) { .site-nav__links { display: none; } }

.site-nav__link {
    display: inline-flex;
    align-items: center;
    height: 100%;
    font-family: var(--font-body);
    font-size: var(--text-body-md);
    color: var(--on-surface-variant);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    padding-top: 2px; /* visual optical balance */
    transition: color 0.1s ease, border-color 0.1s ease;
}
.site-nav__link:hover {
    color: var(--on-surface);
    text-decoration: none;
}
.site-nav__link.is-active {
    color: var(--on-surface);
    border-bottom-color: var(--primary);
    font-weight: 600;
}
.site-nav__link--disabled {
    color: var(--on-surface-variant);
    opacity: 0.55;
    cursor: not-allowed;
}

.site-nav__right {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    flex-shrink: 0;
    margin-left: auto;
}
.site-nav__helpline {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background-color: var(--surface-container-lowest);
    border: var(--border-hairline);
    color: var(--on-surface);
    white-space: nowrap;
}
.site-nav__helpline-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--on-secondary-container);
    flex-shrink: 0;
}
.site-nav__ext-link {
    color: var(--on-secondary-container);
    white-space: nowrap;
}
@media (max-width: 600px) { .site-nav__ext-link { display: none; } }
</style>
