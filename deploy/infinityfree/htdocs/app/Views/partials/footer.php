<?php
/**
 * Partial: footer.php
 *
 * Site-wide footer with helpline, disclaimer, and footer links.
 * Matches the design across all three screens.
 */
?>
<footer class="site-footer" role="contentinfo">
    <div class="container">
        <div class="site-footer__grid">

            <!-- Left: Disclaimer -->
            <div class="site-footer__col-main">
                <p class="site-footer__brand t-headline-sm">
                    <span aria-hidden="true" style="width:8px;height:8px;display:inline-block;background:var(--primary);margin-right:6px;vertical-align:middle;"></span>
                    Forensic Architecture &amp; Civic Truth
                </p>
                <p class="site-footer__disclaimer t-body-sm">
                    ScamShield is an open heuristic analyzer, not an intelligence agency.
                    Results are rule-based indicators, not definitive proof of guilt or innocence.
                    Designed strictly for preemptive civic vigilance and verification before transactional commitment.
                </p>
            </div>

            <!-- Right: Statutory hotline -->
            <div class="site-footer__col-aside">
                <div class="helpline-box">
                    <div class="t-code-sm" style="color:var(--on-surface-variant);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">Statutory Hotline</div>
                    <div class="t-code-md" style="font-weight:600;color:var(--on-surface);">
                        National Cyber Crime Helpline: <strong>1930</strong>
                    </div>
                    <div class="t-body-sm" style="color:var(--on-surface-variant);margin-top:4px;">
                        Official Reporting Portal:
                        <a href="https://cybercrime.gov.in"
                           rel="noopener noreferrer"
                           target="_blank">cybercrime.gov.in</a>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer nav + legal note -->
        <div class="site-footer__bottom">
            <nav class="site-footer__links" aria-label="Footer links">
                <a href="#" class="site-footer__link">Inspection Rules &amp; Heuristics</a>
                <a href="#" class="site-footer__link">Zero Data Retention Guarantee</a>
                <a href="#" class="site-footer__link">Incident Export</a>
            </nav>
            <p class="site-footer__legal t-code-sm">
                IN-CYBER-SHIELD &bull; NON-COMMERCIAL PUBLIC HEURISTIC SYSTEM
            </p>
        </div>
    </div>
</footer>

<style>
.site-footer {
    background-color: var(--surface-container-lowest);
    border-top: var(--border-hairline);
    padding-top: var(--space-xl);
    padding-bottom: var(--space-xl);
    margin-top: auto;
}
.site-footer__grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-lg);
    padding-bottom: var(--space-lg);
    border-bottom: var(--border-hairline);
}
@media (min-width: 768px) {
    .site-footer__grid {
        grid-template-columns: 7fr 5fr;
    }
}
.site-footer__brand {
    margin-bottom: var(--space-sm);
    color: var(--on-surface);
}
.site-footer__disclaimer {
    color: var(--on-surface-variant);
    max-width: 600px;
    line-height: var(--lh-body-lg);
}
.site-footer__bottom {
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
    padding-top: var(--space-lg);
}
@media (min-width: 768px) {
    .site-footer__bottom {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}
.site-footer__links {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-md) var(--space-lg);
}
.site-footer__link {
    font-family: var(--font-mono);
    font-size: var(--text-code-sm);
    color: var(--on-surface-variant);
    text-decoration: none;
    transition: color 0.1s ease;
}
.site-footer__link:hover { color: var(--on-surface); text-decoration: underline; }
.site-footer__legal {
    color: rgba(89,66,56,0.6);
    white-space: nowrap;
}
</style>
