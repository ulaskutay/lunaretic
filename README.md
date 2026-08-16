# Etic Commerce

Commercial, Lunar-based e-commerce platform for Etic Ajans. First production storefront: a Turkish boxer/clothing brand. Lunar remains the commerce engine; Etic (`App\Etic`) is CMS, SEO, theming, and Turkish integrations.

## Stack

- PHP 8.3, Laravel 13, MySQL 8 (SQLite works locally)
- Lunar 1.5 (Filament 4 admin at `/lunar`)
- Lunar 1.5 (Filament 4 admin at `/lunar`)
- Blade + Livewire storefront (`resources/themes/{handle}`) and Next.js (`storefront/`)
- Pest tests

## Documentation

| File | Purpose |
|------|---------|
| [ARCHITECTURE_AUDIT.md](ARCHITECTURE_AUDIT.md) | Phase 0 audit |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Layers and extension rules |
| [SETUP.md](SETUP.md) | Install, env, migrate, run |
| [DEPLOY.md](DEPLOY.md) | Production (server IP, Nginx, no panel) |
| [ROADMAP.md](ROADMAP.md) | P0 / P1 / P2 |
| [INTEGRATIONS.md](INTEGRATIONS.md) | Payments, shipping, marketing |

## Quick start

See [SETUP.md](SETUP.md).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
composer serve
```

- Storefront (Blade): `http://localhost:8000`
- Storefront (Next): `http://localhost:3000`
- Admin: `http://localhost:8000/lunar`

## Rules

- Do not edit `vendor/lunarphp/**`
- Do not commit `.env` or payment secrets
- Prefer extending Lunar over duplicating it
