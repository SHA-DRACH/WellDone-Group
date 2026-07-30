/**
 * Admin dashboard interactions: sidebar toggle, delete confirmation,
 * bulk selection, slug suggestion, image preview and repeatable rows.
 */
export function initAdmin() {
    initSidebar();
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
