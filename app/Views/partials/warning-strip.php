<?php
/**
 * Partial: warning-strip.php
 *
 * Non-dismissible system-wide public warning banner.
 * Rendered at the very top of the page, above the navigation.
 * From Stitch DESIGN.md: Safety Orange tint bg, 2px bottom border.
 */
?>
<div
    class="warning-strip"
    role="complementary"
    aria-label="Critical safety notice"
>
    <div class="container warning-strip__inner">
        <div class="warning-strip__left">
            <span class="badge badge-critical" aria-hidden="true">[CRITICAL NOTICE]</span>
            <span class="warning-strip__text">
                Never share OTP, UPI&nbsp;PIN, or banking passwords.
                No bank or law enforcement agency will ever ask.
            </span>
        </div>
        <div class="warning-strip__right" aria-hidden="true">
            <span class="dot-marker--orange" style="display:inline-block;width:6px;height:6px;background:var(--primary-container);margin-right:4px;"></span>
            <span class="t-code-sm" style="color:var(--surface-container-highest);">HEURISTIC RIGOR v1.4</span>
        </div>
    </div>
</div>

<style>
.warning-strip {
    width: 100%;
    background-color: var(--inverse-surface);
    border-bottom: 2px solid var(--primary-container);
    padding: 6px 0;
}
.warning-strip__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-sm);
}
.warning-strip__left {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    flex-wrap: wrap;
}
.warning-strip__text {
    font-family: var(--font-body);
    font-size: var(--text-body-sm);
    font-weight: 600;
    color: var(--inverse-on-surface);
}
.warning-strip__right {
    display: flex;
    align-items: center;
    white-space: nowrap;
    font-family: var(--font-mono);
    font-size: var(--text-code-sm);
    color: var(--surface-container-highest);
}
@media (max-width: 600px) {
    .warning-strip__right { display: none; }
}
</style>
