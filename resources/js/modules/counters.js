/**
 * Animated statistic counters. Each element opts in with
 * `data-counter="1500"` and may supply `data-suffix` / `data-prefix`.
 * Animation runs once, when the element scrolls into view.
 */
export function initCounters() {
    const targets = document.querySelectorAll('[data-counter]');

    if (!targets.length) {
        return;
    }

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const format = (el, value) => {
        const decimals = Number(el.dataset.decimals || 0);
        const text = value.toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });

        el.textContent = `${el.dataset.prefix || ''}${text}${el.dataset.suffix || ''}`;
    };

    const run = (el) => {
        const target = Number(el.dataset.counter || 0);
        const duration = Number(el.dataset.duration || 1800);

        if (reduceMotion) {
            format(el, target);
            return;
        }

        const start = performance.now();

        const tick = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            // easeOutExpo keeps the count lively at the start and settles gently.
            const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);

            format(el, target * eased);

            if (progress < 1) {
                requestAnimationFrame(tick);
            }
        };

        requestAnimationFrame(tick);
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                run(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.4 });

    targets.forEach((el) => {
        format(el, 0);
        observer.observe(el);
    });
}
