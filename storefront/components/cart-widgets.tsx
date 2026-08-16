"use client";

import { FormEvent, useState } from "react";
import { useStorefront } from "@/lib/store";
import { ApiError } from "@/lib/api";

export function CouponForm() {
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

export function CartTotals({ showShipping = false }: { showShipping?: boolean }) {
  const { cart } = useStorefront();

  if (!cart) {
    return null;
  }

  return (
    <dl className="space-y-1 text-sm">
      <div className="flex justify-between">
        <dt>Ara toplam</dt>
        <dd>{cart.subtotal?.formatted}</dd>
      </div>
      {cart.discount_total && cart.discount_total.value > 0 ? (
        <div className="flex justify-between text-emerald-700">
          <dt>İndirim</dt>
          <dd>-{cart.discount_total.formatted}</dd>
        </div>
      ) : null}
      {showShipping && cart.shipping_total ? (
        <div className="flex justify-between">
          <dt>Kargo</dt>
          <dd>{cart.shipping_total.formatted}</dd>
        </div>
      ) : null}
      <div className="flex justify-between font-medium">
        <dt>Toplam (KDV dahil)</dt>
        <dd>{cart.total?.formatted}</dd>
      </div>
    </dl>
  );
}
