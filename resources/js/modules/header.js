/**
 * Sticky header shadow + mega menu behaviour.
 *
 * Bootstrap's dropdown only opens on click. On desktop we additionally open the
 * mega menu on hover, while leaving the click/keyboard path untouched so the
 * menu stays usable with a keyboard and on touch devices.
 */
export function initHeader() {
    const header = document.querySelector('.site-header');

    if (header) {
        const toggleStuck = () => {
            header.classList.toggle('is-stuck', window.scrollY > 12);
        };

        toggleStuck();
        window.addEventListener('scroll', toggleStuck, { passive: true });
    }

    const desktop = window.matchMedia('(min-width: 992px)');
    const hoverable = window.matchMedia('(hover: hover)');

    document.querySelectorAll('.has-megamenu').forEach((item) => {
        const toggle = item.querySelector('[data-bs-toggle="dropdown"]');

        if (!toggle) {
            return;
        }

        const instance = window.bootstrap.Dropdown.getOrCreateInstance(toggle);
        let closeTimer = null;

        const open = () => {
            if (!desktop.matches || !hoverable.matches) return;
            clearTimeout(closeTimer);
            instance.show();
        };

        const scheduleClose = () => {
            if (!desktop.matches || !hoverable.matches) return;
            closeTimer = setTimeout(() => instance.hide(), 180);
        };

        item.addEventListener('mouseenter', open);
        item.addEventListener('mouseleave', scheduleClose);
        item.addEventListener('focusin', () => clearTimeout(closeTimer));
    });
}
