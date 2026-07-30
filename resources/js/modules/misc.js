/**
 * Small site-wide behaviours: back-to-top, auto-dismissing alerts,
 * tooltip/popover bootstrapping, project filter submit, and native
 * lazy-loading fallback markers.
 */
export function initMisc() {
    initBackToTop();
    initAutoDismiss();
    initTooltips();
    initAutoSubmit();
    initExternalLinks();
}

function initBackToTop() {
    const btn = document.querySelector('[data-back-to-top]');

    if (!btn) {
        return;
    }

    const toggle = () => btn.classList.toggle('is-visible', window.scrollY > 400);

    toggle();
    window.addEventListener('scroll', toggle, { passive: true });

    btn.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });
    });
}

function initAutoDismiss() {
    document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        const delay = Number(alert.dataset.autoDismiss || 6000);

        setTimeout(() => {
            window.bootstrap.Alert.getOrCreateInstance(alert).close();
        }, delay);
    });
}

function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        window.bootstrap.Tooltip.getOrCreateInstance(el);
    });

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
        window.bootstrap.Popover.getOrCreateInstance(el);
    });
}

/** Filter selects that submit their form on change (projects, blog, admin lists). */
function initAutoSubmit() {
    document.querySelectorAll('[data-auto-submit]').forEach((el) => {
        el.addEventListener('change', () => el.form?.submit());
    });
}

/** Harden user-supplied outbound links opened in a new tab. */
function initExternalLinks() {
    document.querySelectorAll('a[target="_blank"]').forEach((link) => {
        const rel = (link.getAttribute('rel') || '').split(/\s+/).filter(Boolean);

        if (!rel.includes('noopener')) rel.push('noopener');
        if (!rel.includes('noreferrer')) rel.push('noreferrer');

        link.setAttribute('rel', rel.join(' '));
    });
}
