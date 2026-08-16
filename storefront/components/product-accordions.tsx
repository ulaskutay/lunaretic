"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";

const shippingFallback =
  "<p>Siparişiniz özenle hazırlanır ve anlaşmalı kargo ağımız üzerinden gönderilir. Teslimat süresi bölgeye göre 1–3 iş günüdür.</p><p>500 TL üzeri siparişlerde kargo ücretsizdir. Kargo takip numarası, sipariş kargoya verildiğinde e-posta ve hesabınıza iletilir.</p>";

export function ProductAccordions({
  shippingHtml,
  productName,
}: {
  shippingHtml?: string | null;
  productName: string;
}) {
  const [status, setStatus] = useState<string | null>(null);

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());

    window.localStorage.setItem(
      "etic-product-question",
      JSON.stringify({ ...data, product: productName, at: new Date().toISOString() }),
    );
    form.reset();
    setStatus("Mesajınız alındı. En kısa sürede dönüş yapacağız.");
  }

  return (
    <div className="etic-pdp__accordion">
      <details>
        <summary>Kargo bilgisi</summary>
        <div className="etic-pdp__accordion-body">
          <div dangerouslySetInnerHTML={{ __html: shippingHtml || shippingFallback }} />
          <Link href="/sayfa/kargo">Kargo sayfasını incele</Link>
        </div>
      </details>
      <details>
        <summary>Soru sorun</summary>
        <form className="etic-pdp__ask" onSubmit={submit}>
          <p>Bu ürün hakkında beden, kumaş veya teslimat sorusu bırakın.</p>
          <label>
            Adınız
            <input name="name" type="text" required autoComplete="name" />
          </label>
          <label>
            E-posta
            <input name="email" type="email" required autoComplete="email" />
          </label>
          <label>
            Mesajınız
            <textarea name="message" rows={4} required defaultValue={`${productName} hakkında sorum var: `} />
          </label>
          <button type="submit">Gönder</button>
          {status ? <p className="etic-pdp__ask-status">{status}</p> : null}
        </form>
      </details>
    </div>
  );
}
