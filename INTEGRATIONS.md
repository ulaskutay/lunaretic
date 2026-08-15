# Etic Commerce — Integrations

All providers are adapters. Checkout and order code must not import iyzico or cargo SDKs directly.

## Payments

Lunar driver API: `Lunar\Facades\Payments`.

| Type | Driver class | MVP |
|------|----------------|-----|
| `offline` | Lunar offline / cash-in-hand | Yes (first) |
| `iyzico` | `App\Etic\Integrations\Payments\IyzicoPaymentType` | Yes (after abstraction) |
| PayTR / Stripe | — | Later |

Configure types in `config/lunar/payments.php` and secrets in `.env`.

Checkout selects a payment type; `Payments::driver($type)->cart($cart)->withData(...)->authorize()`.

## Shipping

`App\Etic\Integrations\Shipping\ShippingProviderInterface`

| Provider | MVP |
|----------|-----|
| Table-rate (weight/subtotal bands, TR) | Yes |
| Yurtiçi, MNG, Aras, Sürat, Hepsijet APIs | No |

Table-rate rules are edited in `/lunar` → Ayarlar → Kargo ayarları (stored in `etic_store_settings`). Defaults live in `config/etic.php`. Carrier APIs (Yurtiçi, MNG, Aras) are later adapters.

Order fulfilment stays in Lunar order status (`config/lunar/orders.php`). Etic scenario: Ödeme bekleniyor → Kapıda/havale veya Ödeme alındı → Hazırlanıyor → Kargolandı → Teslim edildi (iptal ödeme/hazırlık aşamasında). Tracking numbers are future carrier adapters.

## Marketing

`App\Etic\Integrations\Marketing\TrackingDispatcher` emits storefront events:

- `view_item`, `view_category`, `search`
- `add_to_cart`, `begin_checkout`, `add_payment_info`, `purchase`

Listeners render GA4 / GTM / Meta Pixel data layer pushes. Do not scatter tracking snippets in random controllers.

Google Merchant Center XML and Meta Conversions API are P1/P2.
