# Etic Commerce — Integrations

All providers are adapters. Checkout and order code must not import iyzico or cargo SDKs directly.

## Payments

Lunar driver API: `Lunar\Facades\Payments`.

| Type | Driver class | MVP |
|------|----------------|-----|
| `offline` | Lunar offline / cash-in-hand | Yes (first) |
| `iyzico` | `App\Etic\Integrations\Payments\IyzicoPaymentType` | Yes (after abstraction) |
| PayTR / Stripe | — | Later |

Configure types in `config/lunar/payments.php`. Default secrets live in `.env`; per-store iyzico keys can be stored in `etic_store_settings` (group `payments`).

Checkout selects a payment type; `Payments::driver($type)->cart($cart)->withData(...)->authorize()`.

## Shipping

`App\Etic\Integrations\Shipping\ShippingProviderInterface`

| Provider | MVP |
|----------|-----|
| Table-rate (weight/subtotal bands, TR) | Yes |
| Yurtiçi, MNG, Aras, Sürat, Hepsijet APIs | No |

Table-rate rules are edited in `/lunar` → Ayarlar → Kargo ayarları (stored in `etic_store_settings`). Defaults live in `config/etic.php`. Listed rates include KDV (`LUNAR_STORE_INCLUSIVE_OF_TAX=true`). Carrier APIs (Yurtiçi, MNG, Aras) are later adapters.

Order fulfilment stays in Lunar order status (`config/lunar/orders.php`). Etic scenario: Ödeme bekleniyor → Kapıda/havale veya Ödeme alındı → Hazırlanıyor → Kargolandı → Teslim edildi (iptal ödeme/hazırlık aşamasında). Tracking numbers are future carrier adapters.

## Marketing

`App\Etic\Integrations\Marketing\TrackingDispatcher` emits storefront events:

- `view_item`, `view_category`, `search`
- `add_to_cart`, `begin_checkout`, `add_payment_info`, `purchase`

Listeners render GA4 / GTM / Meta Pixel data layer pushes via `TrackingDispatcher`. IDs are edited in `/lunar` → Ayarlar → Pazarlama ayarları (stored in `etic_store_settings`, `.env` as fallback). POST events (`add_to_cart`, `purchase`) are flashed onto the next page. Livewire add-to-cart calls `window.eticTrack`.

Meta Conversions API (CAPI) is an adapter (`MetaConversionsClient`). Enable it on the same marketing page with Pixel ID + access token. Server events (`Purchase`, `AddToCart`, `InitiateCheckout`, `AddPaymentInfo`, `ViewContent`, `Search`) send after the HTTP response. Email/phone are SHA-256 hashed. Pixel and CAPI share `event_id` so Meta deduplicates. Optional `META_TEST_EVENT_CODE` / panel Test Event Code is for Events Manager testing only.

Google Merchant Center XML: `GET /feed/google-merchant.xml` (published variants, `item_group_id` = product). Enable/disable and copy the URL from the same marketing settings page.
