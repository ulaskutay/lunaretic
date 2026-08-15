# Etic Commerce — Architecture

Lunar is the commerce engine. Etic is the commercial product layer (CMS, SEO, Turkish integrations, theming, store settings).

```
LUNAR CORE  →  ETIC COMMERCE CORE  →  CMS / SEO / INTEGRATIONS  →  ADMIN + API + STOREFRONT
```

## Principles

1. Prefer Composer packages, service providers, events, policies, actions, and config — never a Lunar fork.
2. All Etic code lives under `App\Etic`.
3. Do not duplicate products, variants, prices, inventory, carts, orders, customers, or discounts.
4. Design for multiple stores: domain → `etic_stores` → Lunar channel, with per-store theme and credentials on a shared database.
5. Every feature must help launch the first boxer store or sell the platform to the next client.

## Namespaces

```
app/Etic/
  Support/           StoreContext, EticServiceProvider, permissions
  Store/             Settings, theme configuration
  CMS/               Pages, blog, menus
  SEO/               Morph meta, sitemap, robots, redirects, schema
  Media/             Upload validation policies
  Integrations/
    Payments/        Lunar payment drivers (offline, iyzico)
    Shipping/        Table-rate adapter; future carrier adapters
    Marketing/       Event dispatcher (GA4, Meta)
  Storefront/        HTTP layer for the Blade theme
```

## Lunar mapping

| Business concept | Lunar |
|------------------|--------|
| Category | `Collection` (nested) |
| Brand | `Brand` |
| Product / variant / SKU / barcode / stock | `Product`, `ProductVariant` |
| Price / compare-at | `Price` |
| Coupon | `Discount` |
| Customer / address | `Customer`, `Address` |
| Cart / order | `Cart`, `Order` |
| Storefront identity (MVP) | `Channel` |
| Slug | `Url` |
| Product media | Spatie Media Library |
| Payments | `Lunar\Facades\Payments` drivers |
| Shipping | Lunar shipping + Etic adapters |

## Storefront

- Path: `resources/themes/default/`
- Blade + Livewire + Tailwind
- Theme config (logo, colours, social) in `etic_store_settings` / `config/etic.php`
- Commerce logic stays in Lunar + Etic services; views stay dumb

## Admin

Single Filament panel from Lunar (`/lunar`). Etic Filament resources register onto that panel (pages, menus, SEO, redirects, settings). No second `/admin` panel in MVP.

## API

Versioned JSON under `/api/v1/*` for products, collections, cart, pages. Controllers call the same Etic/Lunar services as the Blade storefront.

## Multi-store

Shared database. Host → `etic_stores` → Lunar `Channel`. Theme, CMS, redirects, tracking, shipping, and iyzico credentials are per store. Catalog visibility uses Lunar channelables. Not a separate tenant database and not SaaS billing.

Admin: `/lunar` → Ayarlar → Mağazalar. Unknown hosts fall back to the default store.
