# Etic Commerce — Setup

## Requirements

- PHP 8.3+ with `bcmath`, `exif`, `intl`, `pdo_mysql`

Homebrew PHP 8.5 on this machine was built with `--disable-intl`. Lunar price formatting needs `intl`. `php artisan serve` automatically prefers a PHP binary that has it (typically `php@8.3`).
- Composer 2
- MySQL 8 (or SQLite for local/dev)
- Node 20+ (Vite / Tailwind)
- Laravel 13 + Lunar 1.5 + Filament 4

## Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database and set `DB_*` in `.env`.

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan lunar:install   # if Lunar installer was not run yet
```

On a fresh clone after Lunar is already in Composer, `migrate --seed` is enough if migrations and seeders are committed.

```bash
npm run dev
composer serve
```

`composer serve` raises PHP upload limits (`upload_max_filesize` / `post_max_size`) so product images larger than 2 MB can be saved in `/lunar`. Plain `php artisan serve` uses PHP’s default 2 MB cap and shows “yüklenemedi”.

## Environment

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Primary site URL (canonical host; www or apex, pick one) |
| `DB_*` | MySQL |
| `ETIC_STORE_HANDLE` | Lunar channel handle (`boxers`) |
| `ETIC_CURRENCY` | `TRY` |
| `ETIC_LOCALE` | `tr` |
| `IYZICO_API_KEY` / `IYZICO_SECRET_KEY` / `IYZICO_BASE_URL` | Payment (never commit real values) |
| `GA4_MEASUREMENT_ID` | Google Analytics 4 |
| `GTM_CONTAINER_ID` | Google Tag Manager |
| `META_PIXEL_ID` | Meta Pixel |

Lunar table prefix defaults to `lunar_`. Do not change it without a migration plan.

## Admin

1. Open `/lunar`
2. Sign in with the seeded staff user (see seeder output / `.env` `ADMIN_EMAIL` / `ADMIN_PASSWORD` for local only)

## Tests

```bash
php artisan test
```

## Deployment notes

- `APP_ENV=production`, `APP_DEBUG=false`
- Queue worker if jobs are enabled
- `php artisan sitemap:generate` (or scheduled) for `public/sitemap.xml`
- Point DNS to a single canonical host; redirects handle the other
