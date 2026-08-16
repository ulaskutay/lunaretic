const TOAST_MS = 4200;

function cartSourceImage(root) {
    return root?.querySelector?.('[data-etic-cart-source], .etic-pdp__stage img')
        || document.querySelector('[data-etic-cart-source], .etic-pdp__stage img');
}

function pulseCartTarget() {
    const target = document.querySelector('[data-etic-cart-target]');

    if (! target) {
        return;
    }

    target.classList.remove('is-cart-pulse');
    void target.offsetWidth;
    target.classList.add('is-cart-pulse');
    window.setTimeout(() => target.classList.remove('is-cart-pulse'), 700);
}

function flyProductToCart(source, imageSrc) {
    const target = document.querySelector('[data-etic-cart-target]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (reduced || ! target || ! imageSrc) {
        pulseCartTarget();
        return Promise.resolve();
    }

    const origin = (source || cartSourceImage())?.getBoundingClientRect?.();
    const dest = target.getBoundingClientRect();

    if (! origin || origin.width < 8 || dest.width < 8) {
        pulseCartTarget();
        return Promise.resolve();
    }

    const maxEdge = 220;
    const scale = Math.min(1, maxEdge / Math.max(origin.width, origin.height));
    const width = origin.width * scale;
    const height = origin.height * scale;
    const fromX = origin.left + origin.width / 2;
    const fromY = origin.top + origin.height / 2;
    const toX = dest.left + dest.width / 2;
    const toY = dest.top + dest.height / 2;
    const dx = toX - fromX;
    const dy = toY - fromY;

    const flyer = document.createElement('div');
    flyer.className = 'etic-cart-flyer';
    flyer.setAttribute('aria-hidden', 'true');
    const img = document.createElement('img');
    img.src = imageSrc;
    img.alt = '';
    flyer.appendChild(img);
    Object.assign(flyer.style, {
        left: `${fromX}px`,
        top: `${fromY}px`,
        width: `${width}px`,
        height: `${height}px`,
    });
    document.body.appendChild(flyer);

    const animation = flyer.animate(
        [
            {
                transform: 'translate(-50%, -50%) translate(0, 0) scale(1) rotate(0deg)',
                opacity: 1,
                borderRadius: '0.35rem',
            },
            {
                transform: `translate(-50%, -50%) translate(${dx * 0.42}px, ${dy * 0.18 - 72}px) scale(0.42) rotate(-10deg)`,
                opacity: 1,
                borderRadius: '1.1rem',
                offset: 0.52,
            },
            {
                transform: `translate(-50%, -50%) translate(${dx}px, ${dy}px) scale(0.08) rotate(16deg)`,
                opacity: 0.12,
                borderRadius: '999px',
            },
        ],
        {
            duration: 780,
            easing: 'cubic-bezier(0.22, 0.61, 0.36, 1)',
            fill: 'forwards',
        },
    );

    return animation.finished
        .catch(() => undefined)
        .finally(() => {
            flyer.remove();
            pulseCartTarget();
        });
}

function showCartToast({ name, image, price, href }) {
    document.querySelectorAll('.etic-cart-toast').forEach((node) => node.remove());

    const toast = document.createElement('aside');
    toast.className = 'etic-cart-toast is-visible';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');

    if (image) {
        const media = document.createElement('img');
        media.className = 'etic-cart-toast__media';
        media.src = image;
        media.alt = '';
        toast.appendChild(media);
    } else {
        const media = document.createElement('span');
        media.className = 'etic-cart-toast__media is-empty';
        toast.appendChild(media);
    }

    const copy = document.createElement('div');
    copy.className = 'etic-cart-toast__copy';
    const kicker = document.createElement('p');
    kicker.textContent = 'Sepete eklendi';
    const title = document.createElement('strong');
    title.textContent = name || 'Ürün';
    copy.append(kicker, title);

    if (price) {
        const amount = document.createElement('span');
        amount.textContent = price;
        copy.append(amount);
    }

    const cta = document.createElement('a');
    cta.className = 'etic-cart-toast__cta';
    cta.href = href || '/sepet';
    cta.textContent = 'Sepeti gör';

    const progress = document.createElement('span');
    progress.className = 'etic-cart-toast__progress';

    toast.append(copy, cta, progress);
    document.body.appendChild(toast);

    window.setTimeout(() => {
        toast.classList.remove('is-visible');
        toast.classList.add('is-leaving');
        window.setTimeout(() => toast.remove(), 280);
    }, TOAST_MS);
}

function markAdded(button) {
    if (! button) {
        return;
    }

    const original = button.dataset.originalLabel || button.textContent || 'Sepete ekle';
    button.dataset.originalLabel = original.trim();
    button.classList.add('is-added');
    button.innerHTML = '<svg class="etic-pdp__cart-check" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12.5 9.2 17 19 7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>Eklendi';
    window.setTimeout(() => {
        button.classList.remove('is-added');
        button.textContent = button.dataset.originalLabel;
    }, 2200);
}

function announceCartAdded(detail) {
    void flyProductToCart(detail.source, detail.image);
    showCartToast(detail);
}

function bindAddToCartForms() {
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('[data-etic-add-to-cart]');

        if (! form) {
            return;
        }

        const submitter = event.submitter;
        const intent = submitter?.getAttribute('name') === 'intent'
            ? submitter.value
            : (form.querySelector('[name="intent"]')?.value || 'cart');

        if (intent === 'buy') {
            return;
        }

        event.preventDefault();

        const button = submitter?.classList?.contains('etic-pdp__cart') || submitter?.classList?.contains('etic-btn')
            ? submitter
            : form.querySelector('[data-pdp-cart], .etic-pdp__cart, button[type="submit"]');

        if (button) {
            button.disabled = true;
            button.dataset.originalLabel ??= (button.textContent || '').trim();
            button.textContent = 'Ekleniyor…';
        }

        const payload = new FormData(form);
        payload.set('intent', 'cart');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: payload,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (! response.ok) {
                throw new Error(data.message || 'Sepete eklenemedi.');
            }

            const name = form.dataset.productName
                || document.querySelector('.etic-pdp__title, h1')?.textContent?.trim()
                || 'Ürün';
            const image = form.dataset.productImage || cartSourceImage(form.closest('article') || document)?.src || '';
            const price = form.dataset.productPrice
                || document.querySelector('[data-pdp-price-current]')?.textContent?.trim()
                || '';

            announceCartAdded({
                name,
                image,
                price,
                href: form.dataset.cartUrl || '/sepet',
                source: cartSourceImage(form.closest('article') || document),
            });

            window.Livewire?.dispatch('cart-updated');
            markAdded(button);
        } catch (error) {
            if (button) {
                button.disabled = false;
                button.textContent = button.dataset.originalLabel || 'Sepete ekle';
            }

            const message = error instanceof Error ? error.message : 'Sepete eklenemedi.';
            let box = form.querySelector('.etic-pdp__error');

            if (! box) {
                box = document.createElement('p');
                box.className = 'etic-pdp__error';
                form.insertBefore(box, form.firstChild);
            }

            box.textContent = message;
        } finally {
            if (button && ! button.classList.contains('is-added')) {
                button.disabled = false;
            }
        }
    });
}

bindAddToCartForms();
