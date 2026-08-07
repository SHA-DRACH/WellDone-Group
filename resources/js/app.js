import './bootstrap';

// Vendor styles
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'aos/dist/aos.css';
import 'swiper/css/bundle';

import * as bootstrap from 'bootstrap';
import $ from 'jquery';
import AOS from 'aos';
import Swiper from 'swiper/bundle';

window.bootstrap = bootstrap;
window.$ = window.jQuery = $;
window.Swiper = Swiper;

import { initAssistant } from './modules/assistant';
import { initHeader } from './modules/header';
import { initHeroSlider, initContentSliders } from './modules/sliders';
import { initCounters } from './modules/counters';
import { initQuoteWizard } from './modules/quote-wizard';
import { initAdmin } from './modules/admin';
import { initEditor } from './modules/editor';
import { initMisc } from './modules/misc';

document.addEventListener('DOMContentLoaded', () => {
    AOS.init({
        duration: 700,
        easing: 'ease-out-cubic',
        once: true,
        offset: 60,
        disable: () => window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    });

    initHeader();
    initHeroSlider();
    initContentSliders();
    initCounters();
    initQuoteWizard();
    initAdmin();
    initEditor();
    initMisc();
    initAssistant();
});
