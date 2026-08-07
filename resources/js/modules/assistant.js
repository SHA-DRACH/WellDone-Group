/**
 * The site assistant widget.
 *
 * A small chat surface over POST /assistant/ask. Nothing is rendered as HTML:
 * every reply, link label and suggestion the server returns is written with
 * textContent, so content that passes through a language model can never
 * become markup in the page.
 */
export function initAssistant() {
    const panel = document.querySelector('[data-assistant-panel]');
    const toggle = document.querySelector('[data-assistant-toggle]');

    if (!panel || !toggle) {
        return;
    }

    const log = panel.querySelector('[data-assistant-log]');
    const form = panel.querySelector('[data-assistant-form]');
    const input = panel.querySelector('[data-assistant-input]');
    const send = panel.querySelector('[data-assistant-send]');
    const openers = panel.querySelector('[data-assistant-openers]');

    const endpoint = panel.dataset.assistantEndpoint;
    const resetEndpoint = panel.dataset.assistantReset;
    const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let busy = false;

    // ---------------------------------------------------------- Open / close

    const open = () => {
        panel.hidden = false;
        // Flush the un-hidden state so the transition has somewhere to start
        // from. A forced reflow rather than requestAnimationFrame: rAF is
        // throttled in a background tab, which would leave the panel visible
        // in the accessibility tree but stuck at zero opacity.
        void panel.offsetHeight;
        panel.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.add('is-active');
        input.focus();
    };

    const close = () => {
        panel.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.remove('is-active');
        toggle.focus();

        const settle = () => { panel.hidden = true; };

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            settle();
        } else {
            setTimeout(settle, 200);
        }
    };

    toggle.addEventListener('click', () => (panel.hidden ? open() : close()));
    panel.querySelector('[data-assistant-close]')?.addEventListener('click', close);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            close();
        }
    });

    // ---------------------------------------------------------- Transcript

    /** @returns {HTMLElement} the bubble element, so callers can replace its text later */
    function addMessage(text, who) {
        const row = document.createElement('div');
        row.className = `assistant-msg assistant-msg--${who}`;

        const bubble = document.createElement('p');
        bubble.className = 'assistant-bubble';
        bubble.textContent = text;

        row.appendChild(bubble);
        log.appendChild(row);
        scrollToEnd();

        return row;
    }

    function addTyping() {
        const row = document.createElement('div');
        row.className = 'assistant-msg assistant-msg--bot';
        row.innerHTML = '<span class="assistant-typing" aria-label="Thinking"><i></i><i></i><i></i></span>';
        log.appendChild(row);
        scrollToEnd();

        return row;
    }

    function addLinks(links) {
        if (!Array.isArray(links) || links.length === 0) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'assistant-links';

        links.forEach((link) => {
            if (!link?.url || !link?.label) {
                return;
            }

            const anchor = document.createElement('a');
            anchor.className = 'assistant-link';
            anchor.href = link.url;
            anchor.textContent = link.label;
            wrap.appendChild(anchor);
        });

        if (wrap.childElementCount > 0) {
            log.appendChild(wrap);
            scrollToEnd();
        }
    }

    function addSuggestions(suggestions) {
        if (!Array.isArray(suggestions) || suggestions.length === 0) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'assistant-chips';

        suggestions.forEach((suggestion) => {
            if (typeof suggestion !== 'string' || suggestion.trim() === '') {
                return;
            }

            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'assistant-chip';
            chip.textContent = suggestion;
            chip.dataset.assistantSuggestion = suggestion;
            wrap.appendChild(chip);
        });

        if (wrap.childElementCount > 0) {
            log.appendChild(wrap);
            scrollToEnd();
        }
    }

    function scrollToEnd() {
        log.scrollTop = log.scrollHeight;
    }

    // ---------------------------------------------------------- Asking

    async function ask(question) {
        if (busy || question.trim() === '') {
            return;
        }

        busy = true;
        send.disabled = true;
        openers?.remove();

        addMessage(question, 'user');
        input.value = '';

        const typing = addTyping();

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ message: question }),
            });

            const payload = await response.json().catch(() => ({}));
            typing.remove();

            if (!response.ok) {
                // 429 is the one failure a visitor can act on, so name it.
                addMessage(
                    response.status === 429
                        ? 'That is a lot of questions at once — give it a moment and try again.'
                        : payload.message || 'Something went wrong at our end. Please try again.',
                    'bot',
                );

                return;
            }

            addMessage(payload.answer ?? '', 'bot');
            addLinks(payload.links);
            addSuggestions(payload.suggestions);
        } catch {
            typing.remove();
            addMessage('I could not reach the site just then. Check your connection and try again.', 'bot');
        } finally {
            busy = false;
            send.disabled = false;
            input.focus();
        }
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        ask(input.value);
    });

    // Delegated: the opening chips are in the markup, later ones are added
    // with each reply.
    panel.addEventListener('click', (event) => {
        const chip = event.target.closest('[data-assistant-suggestion]');

        if (chip) {
            ask(chip.dataset.assistantSuggestion);
        }
    });

    // ---------------------------------------------------------- Restart

    panel.querySelector('[data-assistant-restart]')?.addEventListener('click', async () => {
        try {
            await fetch(resetEndpoint, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            });
        } catch {
            // Clearing the screen is still worth doing if the call failed.
        }

        log.replaceChildren();
        addMessage('Fresh start. What would you like to know?', 'bot');
        input.focus();
    });
}
