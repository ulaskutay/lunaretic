"use client";

import { FormEvent, useState } from "react";
import { useStorefront } from "@/lib/store";
import { ApiError } from "@/lib/api";

export function CouponForm({ editorial = false }: { editorial?: boolean }) {
  const { cart, applyCoupon, removeCoupon } = useStorefront();
  const [code, setCode] = useState("");
  const [error, setError] = useState<string | null>(null);

  async function submit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    try {
      await applyCoupon(code);
      setCode("");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Kupon uygulanamadı.");
    }
  }

  if (editorial) {
    return (
      <div className="etic-cart__coupon">
        {cart?.coupon_code ? (
          <div className="etic-cart__coupon-applied">
            <span>
              Kupon <strong>{cart.coupon_code}</strong>
            </span>
            <button type="button" onClick={() => removeCoupon()}>
              Kaldır
            </button>
          </div>
        ) : (
          <form onSubmit={submit} className="etic-cart__coupon-form">
            <input
              value={code}
              onChange={(event) => setCode(event.target.value)}
              placeholder="İndirim kodu"
              autoComplete="off"
              aria-label="İndirim kodu"
            />
            <button type="submit">Uygula</button>
          </form>
        )}
        {error ? <p className="etic-cart__error">{error}</p> : null}
      </div>
    );
  }

  return (
    <div className="rounded-2xl bg-white p-4 text-sm">
      {cart?.coupon_code ? (
        <div className="flex items-center justify-between">
          <span>Kupon: {cart.coupon_code}</span>
          <button className="underline" onClick={() => removeCoupon()}>
            Kaldır
          </button>
        </div>
      ) : (
        <form onSubmit={submit} className="flex gap-2">
          <input
            value={code}
            onChange={(event) => setCode(event.target.value)}
            placeholder="Kupon kodu"
            className="flex-1 rounded border px-3 py-2"
          />
          <button className="rounded-full bg-neutral-900 px-4 py-2 text-white">Uygula</button>
        </form>
      )}
      {error ? <p className="mt-2 text-red-700">{error}</p> : null}
    </div>
  );
}

export function CartTotals({ showShipping = false, editorial = false }: { showShipping?: boolean; editorial?: boolean }) {
  const { cart } = useStorefront();

  if (!cart) {
    return null;
  }

  const shippingFree = Boolean(cart.free_shipping?.unlocked);

  return (
    <dl className={editorial ? "etic-cart__totals" : "space-y-1 text-sm"}>
      <div className={editorial ? undefined : "flex justify-between"}>
        <dt>Ara toplam</dt>
        <dd>{cart.subtotal?.formatted}</dd>
      </div>
      {cart.discount_total && cart.discount_total.value > 0 ? (
        <div className={editorial ? "is-discount" : "flex justify-between text-emerald-700"}>
          <dt>İndirim</dt>
          <dd>-{cart.discount_total.formatted}</dd>
        </div>
      ) : null}
      {editorial ? (
        <div>
          <dt>Kargo</dt>
          <dd className={shippingFree ? "is-free" : undefined}>{shippingFree ? "Ücretsiz" : "Ödeme adımında"}</dd>
        </div>
      ) : showShipping && cart.shipping_total ? (
        <div className="flex justify-between">
          <dt>Kargo</dt>
          <dd>{cart.shipping_total.formatted}</dd>
        </div>
      ) : null}
      <div className={editorial ? "is-total" : "flex justify-between font-medium"}>
        <dt>{editorial ? "Genel toplam" : "Toplam"}</dt>
        <dd>{cart.total?.formatted}</dd>
      </div>
    </dl>
  );
}
