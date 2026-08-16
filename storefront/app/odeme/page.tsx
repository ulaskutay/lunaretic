"use client";

import { FormEvent, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { storeApi } from "@/lib/api";
import { useStorefront } from "@/lib/store";
import { CartTotals, CouponForm } from "@/components/cart-widgets";
import { track } from "@/components/tracking";
import type { ShippingOption } from "@/lib/types";

export default function CheckoutPage() {
  const { cart, token, clearCartToken } = useStorefront();
  const router = useRouter();
  const [options, setOptions] = useState<ShippingOption[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    storeApi.checkout(token).then((response) => {
      setOptions(response.shipping_options);
      response.events.forEach((item) => {
        const { event, ...params } = item;
        track(event, params);
      });
    }).catch(() => undefined);
  }, [token]);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    const form = new FormData(event.currentTarget);
    const payload = Object.fromEntries(form.entries());

    try {
      const response = await storeApi.placeOrder(
        {
          ...payload,
          same_as_shipping: true,
        },
        token,
      );
      response.events.forEach((item) => {
        const { event: name, ...params } = item;
        track(name, params);
      });
      clearCartToken();
      router.push(`/siparis/${response.data.id}`);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Sipariş tamamlanamadı.");
    }
  }

  if (!cart || cart.lines.length === 0) {
    return <p>Ödeme için sepetinizde ürün olmalı.</p>;
  }

  return (
    <>
      <h1 className="mb-6 text-2xl font-semibold">Ödeme</h1>
      {error ? <p className="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-800">{error}</p> : null}
      <div className="grid gap-8 md:grid-cols-2">
        <form onSubmit={submit} className="space-y-3">
          <div className="grid grid-cols-2 gap-3">
            <input name="first_name" placeholder="Ad" className="rounded border px-3 py-2" required />
            <input name="last_name" placeholder="Soyad" className="rounded border px-3 py-2" required />
          </div>
          <input type="email" name="email" placeholder="E-posta" className="w-full rounded border px-3 py-2" required />
          <input name="phone" placeholder="Telefon" className="w-full rounded border px-3 py-2" required />
          <input name="line_one" placeholder="Adres" className="w-full rounded border px-3 py-2" required />
          <div className="grid grid-cols-2 gap-3">
            <input name="city" placeholder="İl" className="rounded border px-3 py-2" required />
            <input name="state" placeholder="İlçe" className="rounded border px-3 py-2" />
          </div>
          <input name="postcode" placeholder="Posta kodu" className="w-full rounded border px-3 py-2" />
          <textarea name="notes" placeholder="Sipariş notu" className="w-full rounded border px-3 py-2" />
          <label className="block text-sm">
            Kargo
            <select name="shipping" className="mt-1 w-full rounded border px-3 py-2">
              {options.map((option) => (
                <option key={option.identifier} value={option.identifier}>
                  {option.name} — {option.price?.formatted}
                </option>
              ))}
            </select>
          </label>
          <label className="block text-sm">
            Ödeme
            <select name="payment" className="mt-1 w-full rounded border px-3 py-2">
              <option value="cash-in-hand">Kapıda / havale (offline)</option>
              <option value="iyzico">iyzico</option>
            </select>
          </label>
          <input type="hidden" name="payment_token" value="test-token" />
          <button className="rounded-full bg-neutral-900 px-6 py-3 text-white">Siparişi tamamla</button>
        </form>
        <aside className="space-y-4">
          <div className="rounded-2xl bg-white p-4">
            <h2 className="font-medium">Özet</h2>
            <ul className="mt-4 space-y-2 text-sm">
              {cart.lines.map((line) => (
                <li key={line.id} className="flex justify-between gap-3">
                  <span>{line.sku} × {line.quantity}</span>
                  <span>{line.total?.formatted}</span>
                </li>
              ))}
            </ul>
            <div className="mt-4">
              <CartTotals showShipping />
            </div>
          </div>
          <CouponForm />
        </aside>
      </div>
    </>
  );
}
