export type CartAddedDetail = {
  name: string;
  image?: string | null;
  price?: string | null;
  href?: string;
  source?: Element | null;
};

const EVENT = "etic:cart-added";

export function cartSourceImage(): HTMLElement | null {
  return document.querySelector<HTMLElement>("[data-etic-cart-source], .etic-pdp__stage img");
}

export function pulseCartTarget() {
  const target = document.querySelector<HTMLElement>("[data-etic-cart-target]");

  if (!target) {
    return;
  }

  target.classList.remove("is-cart-pulse");
  void target.offsetWidth;
  target.classList.add("is-cart-pulse");
  window.setTimeout(() => target.classList.remove("is-cart-pulse"), 700);
}

export function flyProductToCart(source: Element | null | undefined, imageSrc: string | null | undefined) {
  const target = document.querySelector<HTMLElement>("[data-etic-cart-target]");
  const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  if (reduced || !target || !imageSrc) {
    pulseCartTarget();
    return Promise.resolve();
  }

  const origin = (source ?? cartSourceImage())?.getBoundingClientRect();
  const dest = target.getBoundingClientRect();

  if (!origin || origin.width < 8 || dest.width < 8) {
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

  const flyer = document.createElement("div");
  flyer.className = "etic-cart-flyer";
  flyer.setAttribute("aria-hidden", "true");
  const img = document.createElement("img");
  img.src = imageSrc;
  img.alt = "";
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
        transform: "translate(-50%, -50%) translate(0, 0) scale(1) rotate(0deg)",
        opacity: 1,
        borderRadius: "0.35rem",
      },
      {
        transform: `translate(-50%, -50%) translate(${dx * 0.42}px, ${dy * 0.18 - 72}px) scale(0.42) rotate(-10deg)`,
        opacity: 1,
        borderRadius: "1.1rem",
        offset: 0.52,
      },
      {
        transform: `translate(-50%, -50%) translate(${dx}px, ${dy}px) scale(0.08) rotate(16deg)`,
        opacity: 0.12,
        borderRadius: "999px",
      },
    ],
    {
      duration: 780,
      easing: "cubic-bezier(0.22, 0.61, 0.36, 1)",
      fill: "forwards",
    },
  );

  return animation.finished
    .catch(() => undefined)
    .finally(() => {
      flyer.remove();
      pulseCartTarget();
    });
}

export function announceCartAdded(detail: CartAddedDetail) {
  void flyProductToCart(detail.source ?? cartSourceImage(), detail.image ?? null);
  window.dispatchEvent(new CustomEvent<CartAddedDetail>(EVENT, { detail }));
}

export function onCartAdded(listener: (detail: CartAddedDetail) => void) {
  const handler = (event: Event) => {
    listener((event as CustomEvent<CartAddedDetail>).detail);
  };

  window.addEventListener(EVENT, handler);

  return () => window.removeEventListener(EVENT, handler);
}
