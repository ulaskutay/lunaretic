# Etic Commerce — Roadmap

Priority filter: *Does this help launch the first boxer store or sell the platform to the next customer?*

## P0 — First sale

- [x] Phase 0 audit documentation
- [x] Laravel 13 + Lunar 1.5 + Filament 4 foundation
- [x] Products, variants (colour × size), inventory, collections, brands
- [x] Customers, guest checkout, addresses
- [x] Cart, checkout (TR fields), orders
- [x] Payment abstraction + offline, then iyzico
- [x] Table-rate shipping
- [x] Lunar admin + Etic CMS/SEO/settings resources
- [x] Responsive Blade storefront
- [x] Basic CMS pages + SEO (title, description, canonical, sitemap, robots)

## P1 — Important after first sale

- [x] Coupon UX on storefront (Lunar discounts)
- [x] Blog
- [x] Redirect manager
- [x] GA4 / Meta event bus wiring
- [x] Google Merchant feed
- [x] Advanced product filtering

## P2 — Later

- [x] Multi-store (domain → channel, theme, isolated credentials; shared DB)
- Multi-tenant SaaS billing
- Additional payment (PayTR, Stripe)
- Cargo APIs (Yurtiçi, MNG, Aras, Sürat, Hepsijet)
- Marketplace, B2B, ERP, CRM
- Next.js storefront
- Visual page builder
- AI features
- Meilisearch / Algolia

## Suggested sequence after this repository scaffold

1. Import real boxer product photos and copy
2. Configure production `.env`, SSL, primary host (www vs apex)
3. iyzico live keys + 3DS test orders
4. Legal pages review (KVKK, mesafeli satış)
5. First paid traffic (GA4 + Pixel verification)
