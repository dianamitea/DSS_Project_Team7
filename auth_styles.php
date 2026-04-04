<?php

?>
<style>
/* ── Auth page layout ────────────────────────────────────────── */
.auth-section {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    padding: 3rem 0 4rem;
    background-color: var(--cafe-cream);
    /* Subtle diagonal stripe texture */
    background-image:
        repeating-linear-gradient(
            -45deg,
            transparent,
            transparent 40px,
            rgba(200,147,90,.04) 40px,
            rgba(200,147,90,.04) 80px
        );
}

/* ── Auth Card ───────────────────────────────────────────────── */
.auth-card {
    background: var(--cafe-white);
    border: 1px solid var(--cafe-border);
    border-radius: var(--bs-border-radius-xl);
    box-shadow: var(--shadow-lg);
    padding: 2.5rem 2.25rem;
    position: relative;
    overflow: hidden;
}

/* Warm top-border accent */
.auth-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--cafe-coffee), var(--cafe-caramel), var(--cafe-coffee));
}

/* ── Auth Card Header ────────────────────────────────────────── */
.auth-card__header { margin-bottom: 1.75rem; }

.auth-card__logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px; height: 56px;
    border-radius: 50%;
    background: var(--cafe-parchment);
    border: 1px solid var(--cafe-border);
    transition: box-shadow var(--transition-base);
}
.auth-card__logo:hover {
    box-shadow: 0 0 0 4px rgba(200,147,90,.2);
    text-decoration: none;
}

.auth-card__title {
    font-family: var(--font-display);
    font-size: 1.75rem;
    font-weight: 400;
    color: var(--cafe-espresso);
    margin: .5rem 0 .25rem;
}

.auth-card__sub {
    font-size: .9rem;
    color: var(--cafe-muted);
    margin: 0;
}

.auth-link {
    color: var(--cafe-latte);
    font-weight: 500;
    text-decoration: none;
    border-bottom: 1px dashed var(--cafe-caramel);
    transition: color var(--transition-base), border-color var(--transition-base);
}
.auth-link:hover {
    color: var(--cafe-coffee);
    border-color: var(--cafe-coffee);
    text-decoration: none;
}

/* ── Input group icons ───────────────────────────────────────── */
.auth-input-icon {
    background-color: var(--cafe-parchment);
    border-color: var(--cafe-border);
    color: var(--cafe-latte);
    min-width: 2.6rem;
    justify-content: center;
}

/* Password toggle button */
.auth-toggle-pass {
    background-color: var(--cafe-parchment);
    border-color: var(--cafe-border);
    color: var(--cafe-muted);
    cursor: pointer;
    transition: color var(--transition-base), background-color var(--transition-base);
}
.auth-toggle-pass:hover {
    background-color: var(--cafe-cream);
    color: var(--cafe-coffee);
}

/* Override Bootstrap focus ring for our palette */
.form-control:focus,
.form-select:focus {
    border-color: var(--cafe-latte);
    box-shadow: 0 0 0 3px rgba(156,107,60,.18);
}

/* Input invalid state uses our rose */
.form-control.is-invalid {
    border-color: var(--cafe-rose);
}
.form-control.is-invalid:focus {
    border-color: var(--cafe-rose);
    box-shadow: 0 0 0 3px rgba(194,121,106,.18);
}
.invalid-feedback { color: var(--cafe-rose); font-size: .82rem; }

/* Input valid state (confirm password match) */
.form-control.is-valid {
    border-color: #5C7A55;
}
.form-control.is-valid:focus {
    box-shadow: 0 0 0 3px rgba(92,122,85,.15);
}

/* ── Password hints ──────────────────────────────────────────── */
.password-hints {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem .85rem;
}
.hint {
    font-size: .78rem;
    color: var(--cafe-muted);
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    transition: color var(--transition-base);
}
.hint i { font-size: .8rem; }
.hint--ok {
    color: #5C7A55;
}
.hint--ok i { color: #5C7A55; }

/* ── Submit button ───────────────────────────────────────────── */
.auth-submit {
    font-size: .9rem;
    letter-spacing: .08em;
    padding: .8rem 1.5rem;
    border-radius: .5rem;
}

/* ── Terms text ──────────────────────────────────────────────── */
.auth-terms {
    font-size: .78rem;
    color: var(--cafe-muted);
}
.auth-terms a {
    color: var(--cafe-latte);
    border-bottom: 1px dashed var(--cafe-border);
}
.auth-terms a:hover {
    color: var(--cafe-coffee);
    text-decoration: none;
}

/* ── Navbar extras ───────────────────────────────────────────── */
.navbar--scrolled {
    padding-top: .5rem !important;
    padding-bottom: .5rem !important;
    box-shadow: 0 4px 24px rgba(0,0,0,.35) !important;
}
.navbar-greeting {
    font-size: .82rem;
    color: rgba(245,236,215,.55);
    letter-spacing: .04em;
}
.cart-link { position: relative; }
.cart-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--cafe-rose);
    color: #fff;
    font-size: .6rem;
    font-weight: 700;
    line-height: 1;
    width: 16px; height: 16px;
    border-radius: 50%;
    position: relative;
    top: -2px;
    margin-left: 2px;
}

/* ── Flash banner ────────────────────────────────────────────── */
.flash-banner {
    border-radius: 0;
    border-left: none;
    border-right: none;
    border-top: none;
    margin: 0;
    padding-top: .6rem;
    padding-bottom: .6rem;
}

/* ── Footer hours table ──────────────────────────────────────── */
.footer__hours { border-collapse: collapse; font-size: .82rem; }
.footer__hours td { padding: .15rem .8rem .15rem 0; color: rgba(245,236,215,.55); }
.footer__hours td:first-child { color: rgba(245,236,215,.75); min-width: 80px; }

/* ── Footer social icons ─────────────────────────────────────── */
.footer__social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px; height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
    color: rgba(245,236,215,.6) !important;
    font-size: 1rem;
    transition: background var(--transition-base), color var(--transition-base);
    text-decoration: none !important;
}
.footer__social-link:hover {
    background: var(--cafe-caramel);
    color: var(--cafe-espresso) !important;
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 575.98px) {
    .auth-card { padding: 1.75rem 1.25rem; border-radius: var(--bs-border-radius-lg); }
    .auth-card__title { font-size: 1.5rem; }
}
</style>
