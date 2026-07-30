/**
 * Multi-step "Request a Quote" form.
 *
 * The form still posts as a single request — the steps are purely a UI
 * affordance — so server-side validation remains the source of truth. If the
 * server returns errors, we jump to the first pane that contains one.
 */
export function initQuoteWizard() {
    const form = document.querySelector('[data-quote-wizard]');

    if (!form) {
        return;
    }

    const panes = Array.from(form.querySelectorAll('.wizard-pane'));
    const steps = Array.from(form.querySelectorAll('.wizard-step'));
    let current = 0;

    const render = () => {
        panes.forEach((pane, i) => pane.classList.toggle('is-active', i === current));
        steps.forEach((step, i) => {
            step.classList.toggle('is-active', i === current);
            step.classList.toggle('is-done', i < current);
        });

        form.querySelectorAll('[data-wizard-prev]').forEach((btn) => {
            btn.classList.toggle('d-none', current === 0);
        });
        form.querySelectorAll('[data-wizard-next]').forEach((btn) => {
            btn.classList.toggle('d-none', current === panes.length - 1);
        });
        form.querySelectorAll('[data-wizard-submit]').forEach((btn) => {
            btn.classList.toggle('d-none', current !== panes.length - 1);
        });

        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    /** Validate only the fields inside the current pane before advancing. */
    const paneIsValid = () => {
        const fields = panes[current].querySelectorAll('input, select, textarea');
        let valid = true;

        fields.forEach((field) => {
            if (!field.checkValidity()) {
                field.classList.add('is-invalid');
                valid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });

        if (!valid) {
            panes[current].querySelector(':invalid')?.focus();
        }

        return valid;
    };

    form.querySelectorAll('[data-wizard-next]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (paneIsValid() && current < panes.length - 1) {
                current += 1;
                render();
            }
        });
    });

    form.querySelectorAll('[data-wizard-prev]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (current > 0) {
                current -= 1;
                render();
            }
        });
    });

    steps.forEach((step, i) => {
        step.addEventListener('click', () => {
            // Allow jumping back to a completed step, never skipping forward.
            if (i < current) {
                current = i;
                render();
            }
        });
    });

    // Enter should advance the wizard rather than submit a half-filled form.
    form.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA' && current < panes.length - 1) {
            event.preventDefault();
            if (paneIsValid()) {
                current += 1;
                render();
            }
        }
    });

    const firstErrorPane = panes.findIndex((pane) => pane.querySelector('.is-invalid, .invalid-feedback.d-block'));
    current = firstErrorPane > -1 ? firstErrorPane : 0;
    render();
}
