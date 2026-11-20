/**
 * Admin dashboard interactions: sidebar toggle, delete confirmation,
 * bulk selection, slug suggestion, image preview and repeatable rows.
 */
export function initAdmin() {
    initSidebar();
    initSidebarSections();
    initConfirmations();
    initBulkSelect();
    initSlugFrom();
    initImagePreview();
    initRepeaters();
}

function initSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const backdrop = document.querySelector('.admin-backdrop');

    if (!sidebar) {
        return;
    }

    const close = () => {
        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-open');
    };

    document.querySelectorAll('[data-sidebar-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            backdrop?.classList.toggle('is-open');
        });
    });

    backdrop?.addEventListener('click', close);
    document.addEventListener('keydown', (e) => e.key === 'Escape' && close());
}

/**
 * Turns each sidebar section heading ("Division Dashboards", "Reviews &
 * Messages", …) into a dropdown that opens and closes its links.
 *
 * The template keeps its flat markup — every section sits inside its own
 * permission check, so grouping is done here rather than in Blade: each
 * heading collects the links that follow it, up to the next heading.
 *
 * Opening state: what the person last chose is remembered per section.
 * Without a remembered choice a section starts open only when it holds the
 * page being viewed. A closed section that has something waiting (a red
 * badge) shows a dot on its heading, so nothing needing attention is hidden.
 */
function initSidebarSections() {
    const nav = document.querySelector('.admin-nav');

    if (!nav) {
        return;
    }

    const storageKey = 'welldone.admin.nav.sections';
    let saved = {};
    try {
        saved = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
    } catch {
        saved = {};
    }
    const remember = (key, open) => {
        saved[key] = open;
        try {
            localStorage.setItem(storageKey, JSON.stringify(saved));
        } catch {
            // Private browsing or blocked storage: the menu still works, it just forgets.
        }
    };

    nav.querySelectorAll(':scope > .nav-section').forEach((heading, index) => {
        const title = heading.textContent.trim();
        const key = title.toLowerCase().replace(/[^a-z0-9]+/g, '-');

        // Gather this section's links: everything up to the next heading.
        const group = document.createElement('div');
        group.className = 'nav-group';
        group.id = `nav-group-${index}`;
        const inner = document.createElement('div');
        inner.className = 'nav-group__inner';
        group.appendChild(inner);

        let next = heading.nextElementSibling;
        while (next && !next.classList.contains('nav-section')) {
            const current = next;
            next = next.nextElementSibling;
            inner.appendChild(current);
        }

        if (!inner.children.length) {
            heading.remove();
            return;
        }

        heading.after(group);

        // The heading becomes a button.
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'nav-section nav-section--toggle';
        button.setAttribute('aria-controls', group.id);
        button.innerHTML = `<span class="nav-section__label"></span><span class="nav-section__dot" aria-hidden="true"></span><i class="fa-solid fa-chevron-down nav-section__chevron" aria-hidden="true"></i>`;
        button.querySelector('.nav-section__label').textContent = title;
        heading.replaceWith(button);

        const hasActive = !!inner.querySelector('.nav-link.active');
        // Only the red "needs attention" counts, not labels such as "Mine".
        const hasBadge = !!inner.querySelector('.badge.bg-danger, .badge.bg-warning:not(.nav-label)');
        // The section holding the current page always opens; otherwise the
        // remembered choice, otherwise closed.
        const startOpen = hasActive || (key in saved ? saved[key] : false);

        const setOpen = (open) => {
            button.setAttribute('aria-expanded', String(open));
            group.classList.toggle('is-open', open);
            button.classList.toggle('has-attention', !open && hasBadge);
            inner.inert = !open;
        };

        setOpen(startOpen);
        // Shown in its final state immediately; animate only after the first paint.
        requestAnimationFrame(() => group.classList.add('is-ready'));

        button.addEventListener('click', () => {
            const open = button.getAttribute('aria-expanded') !== 'true';
            setOpen(open);
            remember(key, open);
        });
    });
}

/**
 * Destructive actions post through a hidden form; we intercept the submit so
 * the user has to confirm first. The confirmation is a guard rail, not
 * authorization — that is enforced server-side by policies.
 */
function initConfirmations() {
    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('submit', (event) => {
            if (!window.confirm(el.dataset.confirm)) {
                event.preventDefault();
            }
        });

        if (el.tagName === 'A' || el.tagName === 'BUTTON') {
            el.addEventListener('click', (event) => {
                if (!window.confirm(el.dataset.confirm)) {
                    event.preventDefault();
                }
            });
        }
    });
}

function initBulkSelect() {
    document.querySelectorAll('[data-bulk-form]').forEach((form) => {
        const master = form.querySelector('[data-bulk-master]');
        const boxes = form.querySelectorAll('[data-bulk-item]');
        const actions = form.querySelector('[data-bulk-actions]');
        const counter = form.querySelector('[data-bulk-count]');

        const sync = () => {
            const checked = form.querySelectorAll('[data-bulk-item]:checked').length;

            if (counter) counter.textContent = String(checked);
            actions?.classList.toggle('d-none', checked === 0);

            if (master) {
                master.checked = checked > 0 && checked === boxes.length;
                master.indeterminate = checked > 0 && checked < boxes.length;
            }
        };

        master?.addEventListener('change', () => {
            boxes.forEach((box) => { box.checked = master.checked; });
            sync();
        });

        boxes.forEach((box) => box.addEventListener('change', sync));
        sync();
    });
}

/** Auto-fill a slug field from a title field until the user edits it manually. */
function initSlugFrom() {
    document.querySelectorAll('[data-slug-from]').forEach((slugInput) => {
        const source = document.querySelector(slugInput.dataset.slugFrom);

        if (!source) {
            return;
        }

        let touched = slugInput.value.trim().length > 0;

        slugInput.addEventListener('input', () => { touched = true; });

        source.addEventListener('input', () => {
            if (touched) return;

            slugInput.value = source.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 180);
        });
    });
}

function initImagePreview() {
    document.querySelectorAll('[data-image-preview]').forEach((input) => {
        const preview = document.querySelector(input.dataset.imagePreview);

        if (!preview) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) return;

            const url = URL.createObjectURL(file);
            preview.src = url;
            preview.classList.remove('d-none');
            preview.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
        });
    });
}

/** Repeatable field groups (e.g. project specs, career requirements). */
function initRepeaters() {
    document.querySelectorAll('[data-repeater]').forEach((repeater) => {
        const list = repeater.querySelector('[data-repeater-list]');
        const template = repeater.querySelector('template[data-repeater-template]');

        repeater.querySelector('[data-repeater-add]')?.addEventListener('click', () => {
            if (!list || !template) return;

            const index = list.children.length;
            const html = template.innerHTML.replace(/__INDEX__/g, String(index));
            const wrapper = document.createElement('div');

            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;

            if (row) {
                list.appendChild(row);
                row.querySelector('input, textarea, select')?.focus();
            }
        });

        list?.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-repeater-remove]');

            if (remove) {
                remove.closest('[data-repeater-row]')?.remove();
            }
        });
    });
}
