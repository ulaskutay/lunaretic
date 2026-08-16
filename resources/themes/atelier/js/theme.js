function headerRoot() {
    return document.querySelector('[data-etic-header]');
}

function setExpanded(button, expanded) {
    if (button) {
        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
}

function syncScrollState() {
    const header = headerRoot();

    if (! header) {
        return;
    }

    header.classList.toggle('is-scrolled', window.scrollY > 24);
}

function syncParallax() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const viewportCenter = window.innerHeight / 2;

    document.querySelectorAll('[data-etic-parallax]').forEach((element) => {
        const rect = element.getBoundingClientRect();
        const elementCenter = rect.top + (rect.height / 2);
        const offset = Math.max(-68, Math.min(68, (viewportCenter - elementCenter) * 0.12));

        element.style.setProperty('--etic-parallax-y', `${offset.toFixed(2)}px`);
    });
}

let scrollFrame = null;

function syncViewportEffects() {
    if (scrollFrame !== null) {
        return;
    }

    scrollFrame = window.requestAnimationFrame(() => {
        syncScrollState();
        syncParallax();
        scrollFrame = null;
    });
}

function syncCountdown(element) {
    const target = Date.parse(element.dataset.countdownEnd || '');

    if (Number.isNaN(target)) {
        return;
    }

    const remaining = Math.max(0, target - Date.now());
    const totalSeconds = Math.floor(remaining / 1000);
    const values = {
        days: Math.floor(totalSeconds / 86400),
        hours: Math.floor((totalSeconds % 86400) / 3600),
        minutes: Math.floor((totalSeconds % 3600) / 60),
        seconds: totalSeconds % 60,
    };

    Object.entries(values).forEach(([unit, value]) => {
        const output = element.querySelector(`[data-countdown-${unit}]`);

        if (output) {
            output.textContent = String(value).padStart(2, '0');
        }
    });
}

const countdowns = document.querySelectorAll('[data-etic-countdown]');

if (countdowns.length) {
    const updateCountdowns = () => countdowns.forEach(syncCountdown);

    updateCountdowns();
    window.setInterval(updateCountdowns, 1000);
}

function activateShopLook(root, index) {
    root.querySelectorAll('[data-shop-look-trigger]').forEach((button) => {
        const active = button.dataset.shopLookTrigger === index;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-expanded', active ? 'true' : 'false');
    });

    root.querySelectorAll('[data-shop-look-product]').forEach((product) => {
        product.hidden = product.dataset.shopLookProduct !== index;
    });

    const counter = root.querySelector('[data-shop-look-current]');

    if (counter) {
        counter.textContent = String(Number(index) + 1).padStart(2, '0');
    }
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-shop-look-trigger]');

    if (! trigger) {
        return;
    }

    const root = trigger.closest('[data-etic-shop-look]');

    if (root) {
        activateShopLook(root, trigger.dataset.shopLookTrigger || '0');
    }
});

document.addEventListener('click', (event) => {
    const filterToggle = event.target.closest('[data-catalog-filter-toggle]');
    const filterClose = event.target.closest('[data-catalog-filter-close]');
    const gridToggle = event.target.closest('[data-catalog-grid]');
    const root = (filterToggle || filterClose || gridToggle)?.closest('[data-etic-catalog]');

    if (! root) {
        return;
    }

    const mobile = window.matchMedia('(max-width: 899px)').matches;

    if (filterToggle) {
        if (mobile) {
            const open = ! document.body.classList.contains('etic-catalog-filters-lock');
            document.body.classList.toggle('etic-catalog-filters-lock', open);
            filterToggle.setAttribute('aria-expanded', open ? 'true' : 'false');

            const label = filterToggle.querySelector('[data-catalog-filter-label]');
            if (label) {
                label.textContent = open ? 'Filtreleri kapat' : 'Filtreler';
            }
        } else {
            const hidden = root.classList.toggle('is-filters-hidden');
            filterToggle.setAttribute('aria-expanded', hidden ? 'false' : 'true');

            const label = filterToggle.querySelector('[data-catalog-filter-label]');
            if (label) {
                label.textContent = hidden ? 'Filtreleri göster' : 'Filtreleri gizle';
            }
        }
    }

    if (filterClose) {
        document.body.classList.remove('etic-catalog-filters-lock');
        const toggle = root.querySelector('[data-catalog-filter-toggle]');
        toggle?.setAttribute('aria-expanded', 'false');
        const label = toggle?.querySelector('[data-catalog-filter-label]');
        if (label) {
            label.textContent = 'Filtreler';
        }
    }

    if (gridToggle) {
        root.dataset.gridColumns = gridToggle.dataset.catalogGrid || '3';
        root.querySelectorAll('[data-catalog-grid]').forEach((button) => {
            const active = button === gridToggle;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.body.classList.remove('etic-catalog-filters-lock');
    }
});

function closeMegaPanels() {
    const header = headerRoot();

    if (! header) {
        return;
    }

    header.classList.remove('is-mega-open');
    header.querySelectorAll('[data-mega-panel]').forEach((panel) => {
        panel.classList.remove('is-open');
        panel.hidden = true;
    });
}

function initMegaMenu() {
    const header = headerRoot();

    if (! header) {
        return;
    }

    const triggers = header.querySelectorAll('[data-mega-trigger]');
    const panels = header.querySelectorAll('[data-mega-panel]');

    if (! triggers.length || ! panels.length) {
        return;
    }

    let closeTimer = null;

    function isDesktop() {
        return window.matchMedia('(min-width: 768px)').matches;
    }

    function openPanel(id) {
        if (! isDesktop()) {
            return;
        }

        window.clearTimeout(closeTimer);
        header.classList.add('is-mega-open');

        panels.forEach((panel) => {
            const open = panel.dataset.megaPanel === id;
            panel.classList.toggle('is-open', open);
            panel.hidden = ! open;
        });
    }

    function scheduleClose() {
        if (! isDesktop()) {
            return;
        }

        closeTimer = window.setTimeout(() => {
            closeMegaPanels();
        }, 140);
    }

    triggers.forEach((trigger) => {
        const id = trigger.dataset.megaTrigger;
        const panel = header.querySelector(`[data-mega-panel="${id}"]`);

        trigger.addEventListener('mouseenter', () => openPanel(id));
        trigger.addEventListener('focusin', () => openPanel(id));
        trigger.addEventListener('mouseleave', scheduleClose);

        panel?.addEventListener('mouseenter', () => {
            window.clearTimeout(closeTimer);
            openPanel(id);
        });
        panel?.addEventListener('mouseleave', scheduleClose);
    });
}

function closeOverlays() {
    const header = headerRoot();

    if (! header) {
        return;
    }

    header.classList.remove('is-nav-open', 'is-search-open');
    closeMegaPanels();
    setExpanded(header.querySelector('[data-etic-nav-toggle]'), false);
    setExpanded(header.querySelector('[data-etic-search-toggle]'), false);

    const search = header.querySelector('[data-etic-search]');

    if (search) {
        search.hidden = true;
    }
}

document.addEventListener('click', (event) => {
    const header = headerRoot();

    if (! header) {
        return;
    }

    const navToggle = event.target.closest('[data-etic-nav-toggle]');
    const searchToggle = event.target.closest('[data-etic-search-toggle]');

    if (navToggle) {
        const open = ! header.classList.contains('is-nav-open');
        header.classList.toggle('is-nav-open', open);
        header.classList.remove('is-search-open');
        setExpanded(navToggle, open);
        setExpanded(header.querySelector('[data-etic-search-toggle]'), false);
        const search = header.querySelector('[data-etic-search]');
        if (search) {
            search.hidden = true;
        }
        return;
    }

    if (searchToggle) {
        const search = header.querySelector('[data-etic-search]');
        const open = Boolean(search?.hidden);
        header.classList.toggle('is-search-open', open);
        header.classList.remove('is-nav-open');
        setExpanded(searchToggle, open);
        setExpanded(header.querySelector('[data-etic-nav-toggle]'), false);

        if (search) {
            search.hidden = ! open;
            if (open) {
                search.querySelector('input')?.focus();
            }
        }
        return;
    }

    if (! event.target.closest('[data-etic-header]')) {
        closeOverlays();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-etic-newsletter]');

    if (! form) {
        return;
    }

    event.preventDefault();

    const email = new FormData(form).get('email');
    const status = form.parentElement?.querySelector('[data-newsletter-status]');

    if (typeof email === 'string' && email !== '') {
        window.localStorage.setItem('etic-newsletter-email', email);
        form.reset();

        if (status) {
            status.textContent = 'E-posta tercihiniz bu cihazda kaydedildi.';
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeOverlays();
    }
});

initMegaMenu();

window.addEventListener('resize', () => {
    if (window.matchMedia('(min-width: 768px)').matches) {
        const header = headerRoot();

        if (header?.classList.contains('is-nav-open')) {
            closeOverlays();
        }
    }
}, { passive: true });

function parsePdpVariants(root) {
    const script = root.querySelector('[data-pdp-variants]');

    if (! script) {
        return [];
    }

    try {
        return JSON.parse(script.textContent || '[]');
    } catch {
        return [];
    }
}

function selectedPdpOptions(root) {
    const selected = {};

    root.querySelectorAll('[data-pdp-option].is-active').forEach((button) => {
        selected[button.dataset.pdpOption] = Number(button.dataset.valueId);
    });

    return selected;
}

function matchPdpVariant(variants, selected) {
    const handles = Object.keys(selected);

    return variants.find((variant) => handles.every((handle) => (
        (variant.values || []).some((value) => value.option === handle && Number(value.id) === selected[handle])
    ))) || variants[0];
}

function syncPdp(root) {
    const variants = parsePdpVariants(root);
    const selected = selectedPdpOptions(root);
    const match = matchPdpVariant(variants, selected);
    const input = root.querySelector('[data-pdp-variant]');

    if (match && input) {
        input.value = match.id;
    }

    const currentPrice = root.querySelector('[data-pdp-price-current]');
    const comparePrice = root.querySelector('[data-pdp-price-compare]');

    if (currentPrice && match?.price) {
        currentPrice.textContent = match.price;
    }

    if (comparePrice) {
        const compare = match?.compare_price;
        comparePrice.hidden = ! compare;
        comparePrice.textContent = compare || '';
    }

    const stock = root.querySelector('[data-pdp-stock]');

    if (stock) {
        const ready = Boolean(match?.purchasable);
        const marker = stock.querySelector('span');
        const label = stock.querySelector('[data-pdp-stock-label]');

        marker?.classList.toggle('is-ready', ready);
        marker?.classList.toggle('is-empty', ! ready);

        if (label) {
            label.textContent = ready ? (stock.dataset.in || '') : (stock.dataset.out || '');
        }
    }

    root.querySelectorAll('[data-pdp-cart], [data-pdp-buy]').forEach((button) => {
        button.disabled = ! match?.purchasable;
    });

    root.querySelectorAll('[data-pdp-option]').forEach((button) => {
        const next = { ...selected, [button.dataset.pdpOption]: Number(button.dataset.valueId) };
        const candidate = matchPdpVariant(variants, next);
        button.classList.toggle('is-unavailable', ! candidate?.purchasable);
    });
}

function parseGalleryImages(gallery) {
    const script = gallery.querySelector('[data-pdp-gallery-images]');

    if (! script) {
        return [];
    }

    try {
        return JSON.parse(script.textContent || '[]');
    } catch {
        return [];
    }
}

function galleryState(gallery) {
    if (! gallery._pdp) {
        gallery._pdp = { index: 0, zoomed: false };
    }

    return gallery._pdp;
}

function setGalleryOrigin(target, event, image) {
    const rect = target.getBoundingClientRect();
    const x = ((event.clientX - rect.left) / rect.width) * 100;
    const y = ((event.clientY - rect.top) / rect.height) * 100;

    image.style.transformOrigin = `${Math.min(100, Math.max(0, x))}% ${Math.min(100, Math.max(0, y))}%`;
}

function renderGallery(gallery) {
    const images = parseGalleryImages(gallery);
    const state = galleryState(gallery);
    const src = images[state.index];
    const stageImage = gallery.querySelector('[data-pdp-image]');
    const lightbox = document.querySelector('[data-pdp-lightbox]') || gallery.querySelector('[data-pdp-lightbox]');
    const lightboxImage = lightbox?.querySelector('[data-pdp-lightbox-image]');
    const count = lightbox?.querySelector('[data-pdp-lightbox-count]');
    const hint = lightbox?.querySelector('[data-pdp-lightbox-hint]');

    if (src && stageImage) {
        stageImage.src = src;
    }

    if (src && lightboxImage) {
        lightboxImage.src = src;
        lightboxImage.style.transformOrigin = '50% 50%';
    }

    gallery.querySelectorAll('[data-pdp-thumb]').forEach((thumb, index) => {
        thumb.classList.toggle('is-active', index === state.index);
    });

    if (count) {
        count.textContent = `${state.index + 1} / ${images.length}`;
    }

    if (hint) {
        hint.textContent = state.zoomed ? 'Küçültmek için tıklayın' : 'Yakınlaştırmak için tıklayın';
    }

    lightbox?.classList.toggle('is-zoomed', state.zoomed);
}

function lightboxHome(lightbox) {
    return lightbox?._pdpGallery || lightbox?.closest?.('[data-pdp-gallery]') || document.querySelector('[data-pdp-gallery]');
}

function openLightbox(gallery) {
    const lightbox = gallery.querySelector('[data-pdp-lightbox]') || document.querySelector('[data-pdp-lightbox]');
    const state = galleryState(gallery);

    if (! lightbox) {
        return;
    }

    state.zoomed = false;
    lightbox._pdpGallery = gallery;
    document.body.appendChild(lightbox);
    lightbox.hidden = false;
    document.body.style.overflow = 'hidden';
    renderGallery(gallery);
}

function closeLightbox(gallery) {
    const lightbox = document.querySelector('[data-pdp-lightbox]');
    const home = gallery || lightboxHome(lightbox);
    const state = home ? galleryState(home) : { zoomed: false };

    state.zoomed = false;
    if (lightbox) {
        lightbox.hidden = true;
        lightbox.classList.remove('is-zoomed');
        if (home && lightbox.parentElement !== home) {
            home.appendChild(lightbox);
        }
    }
    document.body.style.overflow = '';
}

function stepGallery(gallery, direction) {
    const images = parseGalleryImages(gallery);
    const state = galleryState(gallery);

    if (! images.length) {
        return;
    }

    state.index = (state.index + direction + images.length) % images.length;
    state.zoomed = false;
    renderGallery(gallery);
}

document.addEventListener('click', (event) => {
    const lightbox = event.target.closest('[data-pdp-lightbox]');
    const gallery = event.target.closest('[data-pdp-gallery]') || lightboxHome(lightbox);

    if (lightbox && ! lightbox.hidden) {
        if (event.target === lightbox || event.target.closest('[data-pdp-lightbox-close]')) {
            closeLightbox(gallery);
            return;
        }

        if (event.target.closest('[data-pdp-lightbox-frame]')) {
            const state = galleryState(gallery);
            state.zoomed = ! state.zoomed;
            renderGallery(gallery);
            return;
        }

        if (event.target.closest('[data-pdp-prev]')) {
            stepGallery(gallery, -1);
            return;
        }

        if (event.target.closest('[data-pdp-next]')) {
            stepGallery(gallery, 1);
            return;
        }
    }

    if (! gallery) {
        return;
    }

    if (event.target.closest('[data-pdp-open-lightbox]')) {
        if (gallerySwipe?.moved) {
            return;
        }
        event.preventDefault();
        openLightbox(gallery);
        return;
    }

    if (event.target.closest('[data-pdp-prev]')) {
        stepGallery(gallery, -1);
        return;
    }

    if (event.target.closest('[data-pdp-next]')) {
        stepGallery(gallery, 1);
        return;
    }

    const thumb = event.target.closest('[data-pdp-thumb]');

    if (thumb) {
        galleryState(gallery).index = Number(thumb.dataset.index || 0);
        galleryState(gallery).zoomed = false;
        renderGallery(gallery);
    }
});

document.addEventListener('mousemove', (event) => {
    const frame = event.target.closest('[data-pdp-lightbox-frame]');
    const lightbox = event.target.closest('[data-pdp-lightbox]');
    const gallery = event.target.closest('[data-pdp-gallery]') || lightboxHome(lightbox);

    if (frame && gallery && galleryState(gallery).zoomed) {
        const image = frame.querySelector('[data-pdp-lightbox-image]');
        if (image) {
            setGalleryOrigin(frame, event, image);
        }
    }
});

let gallerySwipe = null;

document.addEventListener('pointerdown', (event) => {
    const stage = event.target.closest('[data-pdp-stage]');

    if (stage && ! event.target.closest('.etic-pdp__nav')) {
        gallerySwipe = { x: event.clientX, y: event.clientY, gallery: stage.closest('[data-pdp-gallery]'), moved: false };
    }
});

document.addEventListener('pointerup', (event) => {
    if (! gallerySwipe) {
        return;
    }

    const dx = event.clientX - gallerySwipe.x;
    const dy = event.clientY - gallerySwipe.y;
    const gallery = gallerySwipe.gallery;

    if (gallery && Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy)) {
        gallerySwipe.moved = true;
        stepGallery(gallery, dx < 0 ? 1 : -1);
        window.setTimeout(() => {
            gallerySwipe = null;
        }, 0);
        return;
    }

    gallerySwipe = null;
});

document.addEventListener('keydown', (event) => {
    const lightbox = document.querySelector('[data-pdp-lightbox]:not([hidden])');
    const gallery = lightboxHome(lightbox);

    if (! gallery || ! lightbox) {
        return;
    }

    if (event.key === 'Escape') {
        closeLightbox(gallery);
    }

    if (event.key === 'ArrowRight') {
        stepGallery(gallery, 1);
    }

    if (event.key === 'ArrowLeft') {
        stepGallery(gallery, -1);
    }
});

document.addEventListener('click', (event) => {
    const root = event.target.closest('[data-etic-pdp]');

    if (! root) {
        return;
    }

    const option = event.target.closest('[data-pdp-option]');

    if (option) {
        event.preventDefault();
        const handle = option.dataset.pdpOption;

        root.querySelectorAll(`[data-pdp-option="${handle}"]`).forEach((item) => {
            item.classList.toggle('is-active', item === option);
        });

        syncPdp(root);
    }
});

document.addEventListener('click', (event) => {
    const openChart = event.target.closest('[data-pdp-size-chart-open]');
    const chart = document.querySelector('[data-pdp-size-chart]');

    if (openChart && chart) {
        event.preventDefault();
        document.body.appendChild(chart);
        chart.showModal();
        return;
    }

    if (! chart || ! chart.open) {
        return;
    }

    if (event.target === chart || event.target.closest('[data-pdp-size-chart-close]')) {
        chart.close();
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-etic-pdp-question]');

    if (! form) {
        return;
    }

    event.preventDefault();

    const payload = Object.fromEntries(new FormData(form).entries());
    window.localStorage.setItem('etic-product-question', JSON.stringify({
        ...payload,
        product: form.dataset.product || '',
        at: new Date().toISOString(),
    }));
    form.reset();

    const status = form.querySelector('[data-pdp-question-status]');

    if (status) {
        status.textContent = 'Mesajınız alındı. En kısa sürede dönüş yapacağız.';
    }
});

const pdp = document.querySelector('[data-etic-pdp]');

if (pdp) {
    syncPdp(pdp);
}

window.addEventListener('scroll', syncViewportEffects, { passive: true });
window.addEventListener('resize', syncViewportEffects, { passive: true });
syncViewportEffects();
