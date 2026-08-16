"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { storeApi } from "@/lib/api";
import { useStorefront } from "@/lib/store";
import { CouponForm } from "@/components/cart-widgets";
import { track } from "@/components/tracking";
import { submitPaytrDirectPayment } from "@/lib/paytr";
import type { ShippingOption } from "@/lib/types";

type PayTab = "paytr" | "havale" | "kapida";

export default function CheckoutPage() {
  const { cart, token, clearCartToken } = useStorefront();
  const router = useRouter();
  const [options, setOptions] = useState<ShippingOption[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [payTab, setPayTab] = useState<PayTab>("paytr");
  const [pending, setPending] = useState(false);
  const [sameBilling, setSameBilling] = useState(true);
  const [corporateBilling, setCorporateBilling] = useState(false);

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
    setPending(true);
    const form = new FormData(event.currentTarget);
    const payload = Object.fromEntries(form.entries());

    try {
      if (payTab === "paytr") {
        const prepare = await storeApi.paytrToken(
          {
            ...payload,
            payment: "paytr",
            same_as_shipping: sameBilling,
            billing_is_corporate: corporateBilling,
          },
          token,
        );

        await submitPaytrDirectPayment(prepare, {
          cc_owner: String(payload.card_name ?? ""),
          card_number: String(payload.card_number ?? ""),
          card_expiry: String(payload.card_expiry ?? ""),
          cvv: String(payload.card_cvc ?? ""),
        });

        return;
      }

      const response = await storeApi.placeOrder(
        {
          ...payload,
          payment: "cash-in-hand",
          same_as_shipping: sameBilling,
          billing_is_corporate: corporateBilling,
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
    } finally {
      setPending(false);
    }
  }

  if (!cart || cart.lines.length === 0) {
    return (
      <section className="mx-auto max-w-6xl px-4 py-16 text-center">
        <p className="text-muted">Ödeme için sepetinizde ürün olmalı.</p>
        <Link href="/sepet" className="mt-4 inline-flex font-semibold text-brand">Sepete dön</Link>
      </section>
    );
  }

  const shippingFree = options.some((option) => (option.price?.value ?? 1) === 0);
  const tabClass = (tab: PayTab) =>
    `rounded-xl border px-3 py-3 text-left text-sm font-semibold ${
      payTab === tab ? "border-brand bg-brand/5 text-brand" : "border-neutral-200 bg-white"
    }`;

  return (
    <section className="mx-auto w-full max-w-6xl px-4 pb-16 pt-2">
      {error ? <p className="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">{error}</p> : null}

      <form onSubmit={submit} className="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_21.5rem]">
        <div className="grid gap-5">
          <section className="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 className="mb-5 text-lg font-semibold">Teslimat adresi</h2>
            <div className="grid gap-4">
              <div className="grid gap-4 sm:grid-cols-2">
                <label className="block">
                  <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Ad</span>
                  <input name="first_name" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                </label>
                <label className="block">
                  <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Soyad</span>
                  <input name="last_name" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                </label>
              </div>
              <label className="block">
                <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">E-posta</span>
                <input type="email" name="email" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
              </label>
              <label className="block">
                <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Telefon</span>
                <input name="phone" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
              </label>
              <label className="block">
                <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Adres</span>
                <input name="line_one" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
              </label>
              <div className="grid gap-4 sm:grid-cols-2">
                <label className="block">
                  <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">İl</span>
                  <input name="city" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                </label>
                <label className="block">
                  <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">İlçe</span>
                  <input name="state" className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                </label>
              </div>
              <label className="block">
                <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Posta kodu</span>
                <input name="postcode" className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
              </label>
              <label className="block">
                <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Sipariş notu</span>
                <textarea name="notes" rows={3} className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
              </label>
              {options.length ? (
                <fieldset className="grid gap-2">
                  <legend className="mb-1 text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Kargo</legend>
                  {options.map((option, index) => (
                    <label key={option.identifier} className="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-neutral-200 px-3 py-2.5 text-sm has-[:checked]:border-brand">
                      <span className="inline-flex items-center gap-2">
                        <input type="radio" name="shipping" value={option.identifier} defaultChecked={index === 0} className="accent-brand" />
                        {option.name}
                      </span>
                      <span className={(option.price?.value ?? 1) === 0 ? "font-semibold text-brand" : ""}>
                        {(option.price?.value ?? 1) === 0 ? "Ücretsiz" : option.price?.formatted}
                      </span>
                    </label>
                  ))}
                </fieldset>
              ) : null}
            </div>
          </section>

          <section className="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 className="mb-5 text-lg font-semibold">Fatura bilgileri</h2>
            <div className="grid gap-4">
              <input type="hidden" name="same_as_shipping" value={sameBilling ? "1" : "0"} />
              <label className="flex items-start gap-2 text-sm text-muted">
                <input
                  type="checkbox"
                  checked={corporateBilling}
                  onChange={(event) => setCorporateBilling(event.target.checked)}
                  className="mt-0.5 accent-brand"
                />
                Kurumsal fatura istiyorum
              </label>
              {corporateBilling ? (
                <div className="grid gap-4 rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                  <label className="block">
                    <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Firma ünvanı</span>
                    <input name="billing_company_name" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                  </label>
                  <div className="grid gap-4 sm:grid-cols-2">
                    <label className="block">
                      <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Vergi dairesi</span>
                      <input name="billing_tax_office" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                    </label>
                    <label className="block">
                      <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Vergi no / TCKN</span>
                      <input name="billing_tax_identifier" required inputMode="numeric" className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                    </label>
                  </div>
                </div>
              ) : null}
              <label className="flex items-start gap-2 text-sm text-muted">
                <input
                  type="checkbox"
                  checked={sameBilling}
                  onChange={(event) => setSameBilling(event.target.checked)}
                  className="mt-0.5 accent-brand"
                />
                Fatura adresim teslimat adresimle aynı
              </label>
              {!sameBilling ? (
                <div className="grid gap-4 rounded-lg border border-dashed border-neutral-200 p-4">
                  <div className="grid gap-4 sm:grid-cols-2">
                    <label className="block">
                      <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Fatura adı</span>
                      <input name="billing_first_name" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                    </label>
                    <label className="block">
                      <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Fatura soyadı</span>
                      <input name="billing_last_name" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                    </label>
                  </div>
                  <label className="block">
                    <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Fatura adresi</span>
                    <input name="billing_line_one" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                  </label>
                  <div className="grid gap-4 sm:grid-cols-2">
                    <label className="block">
                      <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">İl</span>
                      <input name="billing_city" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                    </label>
                    <label className="block">
                      <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">İlçe</span>
                      <input name="billing_state" className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                    </label>
                  </div>
                </div>
              ) : null}
            </div>
          </section>

          <section className="rounded-xl border border-neutral-200 bg-white p-5">
            <h2 className="mb-5 text-lg font-semibold">Ödeme yöntemi</h2>
            <div className="grid gap-3 sm:grid-cols-3">
              <button type="button" className={tabClass("paytr")} onClick={() => setPayTab("paytr")}>Kredi kartı</button>
              <button type="button" className={tabClass("havale")} onClick={() => setPayTab("havale")}>Havale / EFT</button>
              <button type="button" className={tabClass("kapida")} onClick={() => setPayTab("kapida")}>Kapıda ödeme</button>
            </div>
            {payTab === "paytr" ? (
              <div className="mt-5 grid gap-4">
                <label className="block">
                  <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Kart üzerindeki isim</span>
                  <input name="card_name" autoComplete="cc-name" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                </label>
                <label className="block">
                  <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Kart numarası</span>
                  <input name="card_number" inputMode="numeric" autoComplete="cc-number" placeholder="•••• •••• •••• ••••" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                </label>
                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="block">
                    <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">Son kullanma (AA/YY)</span>
                    <input name="card_expiry" autoComplete="cc-exp" placeholder="AA/YY" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                  </label>
                  <label className="block">
                    <span className="mb-1.5 block text-[0.65rem] font-semibold uppercase tracking-wider text-muted">CVC</span>
                    <input name="card_cvc" inputMode="numeric" autoComplete="cc-csc" required className="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm" />
                  </label>
                </div>
              </div>
            ) : (
              <p className="mt-5 rounded-lg bg-neutral-50 p-4 text-sm text-muted">
                {payTab === "havale"
                  ? "Siparişi tamamladıktan sonra havale/EFT bilgileri e-posta ile iletilir. Ödeme onaylanınca kargoya verilir."
                  : "Teslimatta nakit veya kapıda ödeme seçeneği sunulur. Tutar kargo görevlisine ödenir."}
              </p>
            )}
          </section>
        </div>

        <aside className="min-w-0 lg:sticky lg:top-32">
          <div className="grid gap-4 rounded-xl border border-neutral-200 bg-white p-5">
            <h2 className="text-lg font-semibold">Sipariş özeti</h2>
            <ul className="grid gap-3">
              {cart.lines.map((line) => (
                <li key={line.id} className="flex items-center gap-3 text-sm">
                  <div className="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-neutral-100">
                    {line.image ? (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={line.image} alt="" className="h-14 w-14 object-cover" />
                    ) : null}
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-medium">{line.name ?? line.sku}</p>
                    <p className="text-muted">Adet: {line.quantity}</p>
                  </div>
                  <span className="shrink-0 font-semibold">{line.total?.formatted}</span>
                </li>
              ))}
            </ul>
            <dl className="grid gap-3 border-t border-neutral-200 pt-4 text-sm">
              <div className="flex justify-between text-muted">
                <dt>Ara toplam</dt>
                <dd className="text-ink">{cart.subtotal?.formatted}</dd>
              </div>
              {cart.discount_total && cart.discount_total.value > 0 ? (
                <div className="flex justify-between text-emerald-700">
                  <dt>İndirim</dt>
                  <dd>-{cart.discount_total.formatted}</dd>
                </div>
              ) : null}
              <div className="flex justify-between text-muted">
                <dt>Kargo</dt>
                <dd className={shippingFree ? "font-semibold text-brand" : undefined}>{shippingFree ? "Ücretsiz" : "Seçime göre"}</dd>
              </div>
              <div className="flex justify-between pt-2 text-base font-semibold">
                <dt>Genel toplam</dt>
                <dd>{cart.total?.formatted}</dd>
              </div>
            </dl>
            <CouponForm editorial />
            <button type="submit" disabled={pending} className="inline-flex min-h-12 items-center justify-center rounded-lg bg-brand font-semibold text-brand-fg disabled:opacity-60">
              Siparişi tamamla
            </button>
            <p className="m-0 text-center text-xs text-muted">256-bit SSL şifreleme ile korunmaktadır.</p>
          </div>
        </aside>
      </form>
    </section>
  );
}
