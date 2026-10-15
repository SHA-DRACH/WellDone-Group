/**
 * Video helpers, used on both sides of the site.
 *
 *  - Admin: an MP4 chosen in a [data-video-min]/[data-video-max] field has
 *    its length read in the browser, and one outside the limit is refused
 *    before a large file is uploaded only to be rejected by the server.
 *
 *  - Public: a YouTube/Vimeo player is only created when the visitor
 *    presses play (see components/video-player.blade.php).
 */

const human = (seconds) => {
    const s = Math.round(seconds);
    const m = Math.floor(s / 60);
    const r = s % 60;

    if (m === 0) return `${r} seconds`;
    if (r === 0) return m === 1 ? '1 minute' : `${m} minutes`;

    return `${m}:${String(r).padStart(2, '0')}`;
};

function initVideoFields() {
    document.querySelectorAll('input[type="file"][data-video-min]').forEach((input) => {
        const feedback = input.parentElement.querySelector('[data-video-feedback]');
        const info = input.parentElement.querySelector('[data-video-info]');
        const original = info ? info.textContent : '';
        const min = Number(input.dataset.videoMin);
        const max = Number(input.dataset.videoMax);

        input.addEventListener('change', () => {
            input.classList.remove('is-invalid', 'is-valid');
            if (info) info.textContent = original;

            const file = input.files && input.files[0];
            if (!file) return;

            const url = URL.createObjectURL(file);
            const probe = document.createElement('video');
            probe.preload = 'metadata';

            probe.onloadedmetadata = () => {
                URL.revokeObjectURL(url);
                const length = probe.duration;

                if (!Number.isFinite(length)) return;

                if (length < min - 0.5 || length > max + 0.5) {
                    input.value = '';
                    input.classList.add('is-invalid');
                    if (feedback) {
                        feedback.textContent = `That clip runs ${human(length)}. It must be between ${human(min)} and ${human(max)}.`;
                    }
                } else {
                    input.classList.add('is-valid');
                    if (info) info.textContent = `${file.name} · ${human(length)} · ${(file.size / 1048576).toFixed(1)} MB — ready to upload.`;
                }
            };

            // A format the browser cannot decode: leave the decision to the server.
            probe.onerror = () => URL.revokeObjectURL(url);
            probe.src = url;
        });
    });
}

function initVideoEmbeds() {
    document.querySelectorAll('[data-video-embed]').forEach((frame) => {
        frame.querySelector('.video-frame__play')?.addEventListener('click', () => {
            const iframe = document.createElement('iframe');
            iframe.src = frame.dataset.videoEmbed;
            iframe.title = frame.dataset.videoTitle || 'Video';
            iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
            iframe.allowFullscreen = true;
            iframe.loading = 'lazy';

            frame.replaceChildren(iframe);
            frame.classList.add('is-playing');
        });
    });
}

export function initVideo() {
    initVideoFields();
    initVideoEmbeds();
}
