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
  Theme/             Theme registry, manifest, tokens, Filament theme settings
  SEO/               Morph meta, sitemap, robots, redirects, schema
  Media/             Upload validation policies
  Integrations/
    Payments/        Lunar payment drivers (offline, iyzico)
    Shipping/        Table-rate adapter; future carrier adapters
    Marketing/       Event dispatcher (GA4, Meta)
  Storefront/        Blade HTTP layer + headless `/api/v1` for Next.js
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

- Blade theme (MVP / fallback): `resources/themes/{handle}/` — `theme.json` manifest, CSS tokens, Blade layouts/components
- Next.js storefront: `storefront/` — App Router, talks to `/api/v1` and consumes `bootstrap.theme` tokens
- Theme config (logo, colours, fonts, social) in `etic_store_settings` group `theme`, schema in `theme.json`
- Commerce logic stays in Lunar + Etic services; views stay dumb

A theme is a folder under `resources/themes/{handle}` with `theme.json`, `css/theme.css`, `js/theme.js`, `components/`, `pages/`. Stores pick a handle in `/lunar` → Mağazalar. Tokens are edited in `/lunar` → Tema ayarları. New storefront designs should copy `default` and restyle tokens + Blade components without touching Lunar.

## Admin

Single Filament panel from Lunar (`/lunar`). Etic Filament resources register onto that panel (pages, menus, SEO, redirects, settings). No second `/admin` panel in MVP.

## API

Versioned JSON under `/api/v1/*` for bootstrap, products, collections, cart, checkout, CMS, blog, and account. Controllers call the same Etic/Lunar services as the Blade storefront. Headless carts use `X-Cart-Token`. CORS origins: `ETIC_STOREFRONT_ORIGINS` (default `http://localhost:3000`).

## Multi-store

Shared database. Host → `etic_stores` → Lunar `Channel`. Theme, CMS, redirects, tracking, shipping, and iyzico credentials are per store. Catalog visibility uses Lunar channelables. Not a separate tenant database and not SaaS billing.

Admin: `/lunar` → Ayarlar → Mağazalar. Unknown hosts fall back to the default store.
