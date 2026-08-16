import Link from "next/link";
import type { Order, OrderAddress } from "@/lib/types";

function formatAddressLine(address: OrderAddress) {
  return [address.state, address.city, address.postcode].filter(Boolean).join(", ");
}

function billingDiffers(shipping: OrderAddress | null | undefined, billing: OrderAddress | null | undefined) {
  if (!billing || !shipping) {
    return false;
  }

  return (
    billing.line_one !== shipping.line_one
    || billing.city !== shipping.city
    || Boolean(billing.company_name)
    || Boolean(billing.tax_identifier)
  );
}

function formatOrderDate(value?: string | null) {
  if (!value) {
    return null;
  }

  return new Date(value).toLocaleDateString("tr-TR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function OrderSuccess({ order }: { order: Order }) {
  const reference = order.reference ?? String(order.id);
  const shipping = order.shipping_address;
  const billing = order.billing_address;
  const lines = order.lines ?? [];
  const shippingFree = (order.shipping_total?.value ?? 1) === 0;
  const email = shipping?.contact_email;
  const showBilling = billingDiffers(shipping, billing);
  const createdAt = formatOrderDate(order.created_at);

  return (
    <section className="etic-success">
      <header className="etic-success__hero">
        <div className="etic-success__icon" aria-hidden="true">
          <svg viewBox="0 0 48 48">
            <circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" strokeWidth="2" />
            <path d="M15 24.5 21 30.5 33 18" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </div>
        <p className="etic-success__kicker">Teşekkür ederiz</p>
        <h1 className="etic-success__title">Siparişiniz alındı</h1>
        <p className="etic-success__lead">
          Sipariş numaranız <strong>#{reference}</strong>
          {email ? (
            <>
              {" "}
              · Onay e-postası <strong>{email}</strong> adresine gönderilecektir.
            </>
          ) : null}
        </p>
        <span className="etic-success__status">{order.status_label}</span>
      </header>

      <div className="etic-success__layout">
        <div className="etic-success__main">
          <section className="etic-success__card">
            <h2 className="etic-success__card-title">Sonraki adımlar</h2>
            <p className="etic-success__card-copy">{order.status_message}</p>
            <ol className="etic-success__timeline">
              <li className="is-done">
                <span className="etic-success__timeline-dot" aria-hidden="true" />
                <div>
                  <p className="etic-success__timeline-title">Sipariş onaylandı</p>
                  <p className="etic-success__timeline-copy">{createdAt ?? "Az önce"}</p>
                </div>
              </li>
              <li>
                <span className="etic-success__timeline-dot" aria-hidden="true" />
                <div>
                  <p className="etic-success__timeline-title">Hazırlanıyor</p>
                  <p className="etic-success__timeline-copy">Ürünleriniz paketlenmeye başlandığında bilgilendirileceksiniz.</p>
                </div>
              </li>
              <li>
                <span className="etic-success__timeline-dot" aria-hidden="true" />
                <div>
                  <p className="etic-success__timeline-title">Kargoya verildi</p>
                  <p className="etic-success__timeline-copy">Takip bilgisi e-posta ile paylaşılır.</p>
                </div>
              </li>
            </ol>
          </section>

          {shipping ? (
            <section className="etic-success__card">
              <h2 className="etic-success__card-title">Teslimat adresi</h2>
              <address className="etic-success__address">
                <p className="etic-success__address-name">{[shipping.first_name, shipping.last_name].filter(Boolean).join(" ")}</p>
                {shipping.line_one ? <p>{shipping.line_one}</p> : null}
                {shipping.line_two ? <p>{shipping.line_two}</p> : null}
                {formatAddressLine(shipping) ? <p>{formatAddressLine(shipping)}</p> : null}
                {shipping.contact_phone ? <p className="etic-success__address-meta">{shipping.contact_phone}</p> : null}
              </address>
            </section>
          ) : null}

          {showBilling && billing ? (
            <section className="etic-success__card">
              <h2 className="etic-success__card-title">Fatura bilgileri</h2>
              <address className="etic-success__address">
                {billing.company_name ? <p className="etic-success__address-name">{billing.company_name}</p> : null}
                {billing.tax_office || billing.tax_identifier ? (
                  <p className="etic-success__address-meta">
                    {[billing.tax_office, billing.tax_identifier].filter(Boolean).join(" · ")}
                  </p>
                ) : null}
                <p className="etic-success__address-name">{[billing.first_name, billing.last_name].filter(Boolean).join(" ")}</p>
                {billing.line_one ? <p>{billing.line_one}</p> : null}
                {billing.line_two ? <p>{billing.line_two}</p> : null}
                {formatAddressLine(billing) ? <p>{formatAddressLine(billing)}</p> : null}
              </address>
            </section>
          ) : null}
        </div>

        <aside className="etic-success__aside">
          <div className="etic-success__summary">
            <h2 className="etic-success__summary-title">Sipariş özeti</h2>
            <ul className="etic-success__items">
              {lines.map((line) => (
                <li key={line.id} className="etic-success__item">
                  <div className="etic-success__item-thumb">
                    {line.image ? (
                      <img src={line.image} alt="" width={56} height={56} loading="lazy" decoding="async" />
                    ) : null}
                  </div>
                  <div className="etic-success__item-copy">
                    <p className="etic-success__item-name">{line.description}</p>
                    <p className="etic-success__item-qty">Adet: {line.quantity}</p>
                  </div>
                  <span className="etic-success__item-price">{line.total?.formatted}</span>
                </li>
              ))}
            </ul>
            <dl className="etic-success__totals">
              <div>
                <dt>Ara toplam</dt>
                <dd>{order.subtotal?.formatted}</dd>
              </div>
              {(order.discount_total?.value ?? 0) > 0 ? (
                <div className="is-discount">
                  <dt>İndirim</dt>
                  <dd>− {order.discount_total?.formatted}</dd>
                </div>
              ) : null}
              <div>
                <dt>Kargo</dt>
                <dd className={shippingFree ? "is-free" : undefined}>
                  {shippingFree ? "Ücretsiz" : order.shipping_total?.formatted}
                </dd>
              </div>
              <div className="is-total">
                <dt>Genel toplam</dt>
                <dd>{order.total?.formatted}</dd>
              </div>
            </dl>
            {(order.tax_total?.value ?? 0) > 0 ? (
              <p className="etic-success__tax">KDV dahil {order.tax_total?.formatted}</p>
            ) : null}
            <div className="etic-success__actions">
              <Link href="/koleksiyon" className="etic-success__cta">Alışverişe devam et</Link>
              <Link href="/hesabim" className="etic-success__cta etic-success__cta--ghost">Hesabım</Link>
            </div>
          </div>
        </aside>
      </div>
    </section>
  );
}
