"use client";

import { useState } from "react";
import Link from "next/link";
import { AnimatePresence, motion } from "motion/react";
import { useStorefront } from "@/lib/store";
import { CartTotals, CouponForm } from "@/components/cart-widgets";
import { productPath } from "@/lib/paths";
import type { CartLine } from "@/lib/types";

function optionLabel(line: CartLine) {
  return (line.values ?? [])
    .map((value) => value.name)
    .filter(Boolean)
    .join(" / ");
}

function MinusIcon() {
  return (
    <svg viewBox="0 0 16 16" aria-hidden="true">
      <path d="M3 8h10" fill="none" stroke="currentColor" strokeWidth="1.4" />
    </svg>
  );
}

function PlusIcon() {
  return (
    <svg viewBox="0 0 16 16" aria-hidden="true">
      <path d="M8 3v10M3 8h10" fill="none" stroke="currentColor" strokeWidth="1.4" />
    </svg>
  );
}

function TrashIcon() {
  return (
    <svg viewBox="0 0 20 20" aria-hidden="true">
      <path d="M4.5 6.2h11M8.2 6.2V4.8A1.3 1.3 0 0 1 9.5 3.5h1a1.3 1.3 0 0 1 1.3 1.3v1.4M7.2 8.1l.4 7.1M12.8 8.1l-.4 7.1M6.1 6.2l.5 9.2a1.4 1.4 0 0 0 1.4 1.3h4a1.4 1.4 0 0 0 1.4-1.3l.5-9.2" fill="none" stroke="currentColor" strokeWidth="1.35" strokeLinecap="round" strokeLinejoin="round" />
    </svg>
  );
}

function LockIcon() {
  return (
    <svg viewBox="0 0 20 20" aria-hidden="true">
      <rect x="4.5" y="9" width="11" height="7.5" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.4" />
      <path d="M7 9V7.2A3 3 0 0 1 10 4.2 3 3 0 0 1 13 7.2V9" fill="none" stroke="currentColor" strokeWidth="1.4" />
    </svg>
  );
}

function CartLineRow({ line }: { line: CartLine }) {
  const { updateLine, removeLine } = useStorefront();
  const [pending, setPending] = useState(false);
  const href = line.slug ? productPath(line.slug) : null;
  const meta = optionLabel(line);

  async function changeQuantity(next: number) {
    if (pending || next < 1) {
      return;
    }

    setPending(true);
    try {
      await updateLine(line.id, next);
    } finally {
      setPending(false);
    }
  }

  async function remove() {
    setPending(true);
    try {
      await removeLine(line.id);
    } finally {
      setPending(false);
    }
  }

  const title = <h2 className="etic-cart__name">{line.name ?? line.sku}</h2>;

  return (
    <motion.article
      layout
      className="etic-cart__line"
      initial={{ opacity: 0, y: 8 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, height: 0, marginBottom: 0, paddingTop: 0, paddingBottom: 0 }}
      transition={{ duration: 0.22, ease: [0.22, 1, 0.36, 1] }}
    >
      <div className="etic-cart__thumb">
        {href ? (
          <Link href={href} aria-label={line.name ?? line.sku}>
            {line.image ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={line.image} alt="" />
            ) : (
              <span />
            )}
          </Link>
        ) : line.image ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={line.image} alt="" />
        ) : (
          <span />
        )}
      </div>
      <div className="etic-cart__line-body">
        <div className="etic-cart__line-copy">
          {href ? <Link href={href}>{title}</Link> : title}
          {meta ? <p className="etic-cart__meta">{meta}</p> : null}
          {line.unit_price ? <p className="etic-cart__unit">{line.unit_price.formatted}</p> : null}
        </div>
        <div className="etic-cart__line-tools">
          <div className="etic-cart__qty" aria-label="Adet">
            <button type="button" onClick={() => changeQuantity(line.quantity - 1)} disabled={pending || line.quantity <= 1} aria-label="Azalt">
              <MinusIcon />
            </button>
            <span>{line.quantity}</span>
            <button type="button" onClick={() => changeQuantity(line.quantity + 1)} disabled={pending} aria-label="Artır">
              <PlusIcon />
            </button>
          </div>
          <button type="button" className="etic-cart__remove" onClick={() => void remove()} disabled={pending} aria-label="Ürünü kaldır">
            <TrashIcon />
          </button>
        </div>
      </div>
    </motion.article>
  );
}

export default function CartPage() {
  const { cart, cartCount } = useStorefront();

  if (!cart) {
    return (
      <section className="etic-cart is-loading" aria-busy="true">
        <div className="etic-cart__skeleton" />
      </section>
    );
  }

  if (cart.lines.length === 0) {
    return (
      <section className="etic-cart is-empty">
        <div className="etic-cart__empty">
          <h1>Sepetiniz boş</h1>
          <p>Koleksiyondan bir ürün eklediğinizde burada görünecek.</p>
          <Link href="/koleksiyon" className="etic-cart__cta">
            Alışverişe devam et
          </Link>
        </div>
      </section>
    );
  }

  return (
    <section className="etic-cart">
      <div className="etic-cart__layout">
        <div className="etic-cart__list">
          <header className="etic-cart__list-head">
            <h1>Sepetiniz ({cartCount} ürün)</h1>
          </header>
          <div className="etic-cart__lines">
            <AnimatePresence initial={false}>
              {cart.lines.map((line) => (
                <CartLineRow key={line.id} line={line} />
              ))}
            </AnimatePresence>
          </div>
          <Link href="/koleksiyon" className="etic-cart__continue">
            ← Alışverişe devam et
          </Link>
        </div>
        <aside className="etic-cart__aside">
          <div className="etic-cart__summary">
            <h2>Sipariş özeti</h2>
            <CartTotals editorial />
            <CouponForm editorial />
            <Link href="/odeme" className="etic-cart__cta">
              <LockIcon />
              Güvenli ödemeye geç
            </Link>
            <p className="etic-cart__ssl">256-bit SSL şifreleme ile güvende</p>
          </div>
        </aside>
      </div>
      <div className="etic-cart__dock">
        <div>
          <span>Toplam</span>
          <strong>{cart.total?.formatted}</strong>
        </div>
        <Link href="/odeme">Ödemeye geç</Link>
      </div>
    </section>
  );
}
