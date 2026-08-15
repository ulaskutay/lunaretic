# Etic Commerce — Architecture Audit

**Date:** 15 August 2026  
**Workspace:** `/Users/eticajans/Desktop/Etic Ajans/Projeler/Etic CMS Eticaret`  
**Status:** Greenfield (empty directory at audit time)

This document closes Phase 0. It records what existed before implementation and the decisions that follow.

---

## A. Current architecture

At audit time the folder contained no application files:

| Item | Status |
|------|--------|
| Laravel / PHP project | Absent |
| `composer.json` / `composer.lock` | Absent |
| Lunar | Not installed |
| Filament | Not installed |
| Database schema / migrations | Absent |
| Storefront / admin | Absent |
| Tests | Absent |
| Git repository | Absent |

**Local toolchain (host):** PHP 8.3.28, Composer 2.9.2, `pdo_mysql`, `pdo_pgsql`, `bcmath`, `intl`, `exif`.

**Nearby projects (not in this repo, must not be merged):**

- `Projeler/Etic CMS/etic-cms` — Next.js / Payload CMS
- `Projeler/CMS` — custom PHP CMS
- Shopify apps/themes — Shopify-locked; not a Lunar foundation

**Conflicts with Lunar:** none. There is no parallel commerce domain to delete or rewrite.

---

## B. Recommended architecture

| Layer | Role |
|-------|------|
| Lunar Core | Products, variants, prices, inventory, customers, carts, orders, discounts, channels, currencies |
| Etic Core (`App\Etic`) | Store context, settings, themes, CMS, SEO, integrations, tracking |
| Admin | Lunar Filament panel at `/lunar`, plus Etic resources on the same panel |
| API | Thin REST for a future Next.js storefront |
| Storefront | Blade + Livewire + Tailwind under `resources/themes/default` |

**Stack (production + speed):**

- PHP 8.3 (host)
- Laravel 13 (current `laravel/laravel` skeleton; Lunar 1.5 supports Laravel 12/13)
- Lunar `1.5.0-beta.6` (`lunarphp/lunar`)
- Filament 4.12 (via Lunar)
- MySQL 8 in production; SQLite acceptable locally
- Pest 4

**Rules:**

- Do not modify `vendor/lunarphp/**`
- Do not duplicate Lunar tables for products, orders, carts, customers, prices, inventory
- One store/channel in MVP (`boxers`); no hardcoded “there will only ever be one store”
- No full multi-tenant SaaS billing in MVP

---

## C. Files / modules to create

See [ARCHITECTURE.md](ARCHITECTURE.md) for the namespace map.

- Laravel application skeleton
- `app/Etic/**`
- `config/etic.php`
- `resources/themes/default/**`
- Etic migrations (`etic_*` tables)
- Pest tests for commerce-critical paths
- This documentation set

---

## D. Files / modules to modify (after scaffold)

- `composer.json` — pin Lunar; scripts
- `AppServiceProvider` / Etic service provider — Lunar panel, telemetry opt-out
- `app/Models/User.php` — `LunarUser`
- Published `config/lunar/*`
- `routes/web.php`, `routes/api.php`
- `.env.example`

---

## E. Files / modules that must not be touched

- Lunar vendor source
- Neighbouring Etic CMS / Shopify / legacy PHP CMS
- Visual page builders, marketplace, B2B, ERP, AI features

---

## F. Database changes

**Lunar:** `lunar_*` tables from the package installer.

**Etic-only:**

- `etic_pages`
- `etic_blog_posts`, `etic_blog_categories`, `etic_blog_tags`, pivot tables
- `etic_menus`, `etic_menu_items`
- `etic_seo` (morph)
- `etic_redirects`
- `etic_store_settings`
- `etic_tracking_settings`

---

## G. First MVP milestones

1. Foundation (Laravel + Lunar + Etic namespace)
2. Catalog via Lunar (admin)
3. Boxer storefront (home, listing, product, cart)
4. TR checkout + orders + table-rate shipping + offline then iyzico
5. CMS pages + SEO + sitemap/robots/redirects
6. iyzico Lunar payment driver
7. Central tracking event bus (GA4 / Meta)

---

## H. Risks

| Risk | Mitigation |
|------|------------|
| Lunar 1.5 still on a beta line | Pin exact versions; minimize panel overrides |
| Livewire starter kit is not production-ready | Custom theme; use kit as reference only |
| No first-party iyzico driver | Implement `Lunar` payment type in `App\Etic` |
| Channel ≠ tenant isolation | Single channel in MVP; document future isolation |
| Duplicate canonicals | Lunar `Url` for slugs; `etic_seo` for meta; one canonical builder |

---

## I. Estimated complexity by module

| Module | Complexity |
|--------|------------|
| Lunar catalog / cart / orders / discounts | Low (use Lunar) |
| Etic CMS / SEO | Medium |
| Storefront + checkout UX | High |
| iyzico driver | Medium |
| Cargo carrier APIs | High — postpone; table-rate for MVP |
| Tracking skeleton | Low; CAPI later is medium |
| Multi-store SaaS | Very high — do not build |

---

## What must not be built yet

PayTR, Yurtiçi/MNG/Aras/Sürat/Hepsijet APIs, Google Merchant XML, full blog marketing, Next.js storefront, SaaS billing, Meta CAPI production pipeline, Meilisearch faceted search, visual CMS, marketplace, B2B.
