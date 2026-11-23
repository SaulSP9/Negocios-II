import { $, all, escape } from './dom.js';

const ticker = $('#ticker');
const originalTicker = ticker?.innerHTML;
const pad = (value) => String(value).padStart(2, '0');
export function countdown(end) {
    const total = Math.max(0, Math.floor((new Date(end).getTime() - Date.now()) / 1000));
    return `${Math.floor(total / 86400)}D ${pad(Math.floor(total / 3600) % 24)}H ${pad(Math.floor(total / 60) % 60)}M ${pad(total % 60)}S`;
}

export function refreshClocks() {
    all('[data-countdown]').forEach(clock => { clock.textContent = countdown(clock.dataset.countdown); });
}

export function renderTicker(promotions) {
    if (!ticker) return;
    if (!promotions.length) {
        ticker.innerHTML = originalTicker;
        return;
    }
    const rows = Array.from({ length: Math.max(4, promotions.length) }, (_, index) => promotions[index % promotions.length]);
    const cells = rows.map(promo => `<span>${escape(promo.nombre)} · ${promo.tipo_descuento === 'porcentaje' ? escape(Number(promo.valor)) + '%' : '$' + escape(promo.valor) + ' MXN'}
        <time data-countdown="${escape(promo.fecha_fin)}"></time></span>`).join('');
    ticker.innerHTML = `<div class="ticker-strip">${cells}</div><div class="ticker-strip" aria-hidden="true">${cells}</div>`;
    refreshClocks();
}

export function startCarousel() {
    const hero = $('#hero');
    const slides = all('[data-slide]', hero);
    const dots = all('[data-slide-index]', hero);
    const progress = $('.hero-progress i', hero);
    const toggle = $('#hero-pause');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const duration = 4500;
    let index = 0;
    let elapsed = 0;
    let previous = performance.now();
    let stopped = reduced.matches;
    let hovering = false;

    function renderPause() {
        toggle.setAttribute('aria-pressed', String(stopped));
        toggle.setAttribute('aria-label', stopped ? 'Reanudar carrusel' : 'Pausar carrusel');
    }
    function select(target) {
        index = (target + slides.length) % slides.length;
        elapsed = 0;
        slides.forEach((slide, position) => {
            const active = position === index;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;
            dots[position].setAttribute('aria-current', String(active));
        });
    }
    function tick(now) {
        if (!hero.hidden && !document.hidden && !stopped && !hovering && !$('.hero-slide :focus', hero)) {
            elapsed += Math.min(now - previous, 100);
            if (elapsed >= duration) select(index + 1);
        }
        progress.style.transform = `scaleX(${elapsed / duration})`;
        previous = now;
        requestAnimationFrame(tick);
    }

    hero.addEventListener('pointerenter', () => { hovering = true; });
    hero.addEventListener('pointerleave', () => { hovering = false; });
    hero.addEventListener('click', (event) => {
        const step = event.target.closest('[data-slide-change]');
        const dot = event.target.closest('[data-slide-index]');
        if (step) select(index + Number(step.dataset.slideChange));
        if (dot) select(Number(dot.dataset.slideIndex));
    });
    toggle.addEventListener('click', () => { stopped = !stopped; renderPause(); });
    reduced.addEventListener('change', (event) => { stopped = event.matches; renderPause(); });
    renderPause();
    requestAnimationFrame(tick);
}
