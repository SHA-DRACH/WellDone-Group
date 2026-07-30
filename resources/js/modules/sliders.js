import Swiper from 'swiper/bundle';

/** Full-screen hero slider on the homepage. */
export function initHeroSlider() {
    const el = document.querySelector('[data-hero-slider]');

    if (!el) {
        return;
    }

    const slides = el.querySelectorAll('.swiper-slide');

    new Swiper(el, {
        loop: slides.length > 1,
        speed: 900,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        autoplay: slides.length > 1 ? { delay: 6500, disableOnInteraction: false } : false,
        navigation: {
            nextEl: el.querySelector('.swiper-button-next'),
            prevEl: el.querySelector('.swiper-button-prev'),
        },
        pagination: {
            el: el.querySelector('.swiper-pagination'),
            clickable: true,
        },
        a11y: {
            prevSlideMessage: 'Previous slide',
            nextSlideMessage: 'Next slide',
        },
    });
}

/** Generic carousels: testimonials, partner logos, project galleries. */
export function initContentSliders() {
    document.querySelectorAll('[data-slider]').forEach((el) => {
        const perView = Number(el.dataset.perView || 3);
        const autoplay = el.dataset.autoplay !== 'false';

        new Swiper(el, {
            loop: el.querySelectorAll('.swiper-slide').length > perView,
            spaceBetween: Number(el.dataset.gap || 24),
            speed: 650,
            autoplay: autoplay ? { delay: 4500, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
            navigation: {
                nextEl: el.querySelector('.swiper-button-next'),
                prevEl: el.querySelector('.swiper-button-prev'),
            },
            pagination: el.querySelector('.swiper-pagination')
                ? { el: el.querySelector('.swiper-pagination'), clickable: true }
                : false,
            breakpoints: {
                0: { slidesPerView: 1 },
                576: { slidesPerView: Math.min(2, perView) },
                992: { slidesPerView: Math.min(3, perView) },
                1200: { slidesPerView: perView },
            },
        });
    });

    // Project detail: thumbnail strip controlling the main gallery.
    document.querySelectorAll('[data-gallery-main]').forEach((mainEl) => {
        const thumbsEl = document.querySelector(mainEl.dataset.galleryThumbs || '');
        let thumbs = null;

        if (thumbsEl) {
            thumbs = new Swiper(thumbsEl, {
                slidesPerView: 4,
                spaceBetween: 12,
                watchSlidesProgress: true,
                breakpoints: {
                    768: { slidesPerView: 5 },
                    1200: { slidesPerView: 6 },
                },
            });
        }

        new Swiper(mainEl, {
            spaceBetween: 12,
            navigation: {
                nextEl: mainEl.querySelector('.swiper-button-next'),
                prevEl: mainEl.querySelector('.swiper-button-prev'),
            },
            thumbs: thumbs ? { swiper: thumbs } : undefined,
        });
    });
}
