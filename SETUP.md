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
| `ETIC_STORE_HANDLE` | Default Lunar channel / store handle (`boxers`) |

Mağazalar `/lunar` → Ayarlar → Mağazalar üzerinden eklenir. Host eşleşmezse varsayılan mağaza kullanılır. iyzico ve piksel kimlikleri mağaza bazında `etic_store_settings` içinde tutulur (`.env` yedek).
| `ETIC_CURRENCY` | `TRY` |
| `ETIC_LOCALE` | `tr` |
| `LUNAR_STORE_INCLUSIVE_OF_TAX` | `true` — vitrin ve admin fiyatları KDV dahil; ödeme adımında vergi üste eklenmez |
| `IYZICO_API_KEY` / `IYZICO_SECRET_KEY` / `IYZICO_BASE_URL` | Payment (never commit real values) |
| `GA4_MEASUREMENT_ID` | Google Analytics 4 |
| `GTM_CONTAINER_ID` | Google Tag Manager |
| `META_PIXEL_ID` | Meta Pixel |
| `META_CAPI_ENABLED` | `true` to send Conversions API events (or enable in panel) |
| `META_CAPI_ACCESS_TOKEN` | Meta CAPI access token (never commit; panel can override) |
| `META_TEST_EVENT_CODE` | Optional Events Manager test code |
| `GOOGLE_SITE_VERIFICATION` | Search Console meta tag |

Merchant feed (no extra env): `https://{host}/feed/google-merchant.xml`

Lunar table prefix defaults to `lunar_`. Do not change it without a migration plan.

## Admin

1. Open `/lunar`
2. Sign in with the seeded staff user (see seeder output / `.env` `ADMIN_EMAIL` / `ADMIN_PASSWORD` for local only)
3. İçerik: sayfalar, blog yazıları, blog kategorileri, menüler
4. SEO: yönlendirmeler
5. Ayarlar: kargo ayarları, pazarlama ayarları (GA4 / GTM / Pixel / CAPI / Merchant feed URL)

## Tests

```bash
php artisan test
```

## Deployment notes

Production uses the existing web server (Nginx 80/443 + PHP-FPM). Do **not** run `php artisan serve` on the server; that would collide with other sites.

Target directory: `/www/wwwroot/etic-commerce` (unique folder; other wwwroot sites stay untouched).

Nginx document root must be `public/`, not the project root.

```bash
# aaPanel: Website → Add site
# Path: /www/wwwroot/etic-commerce/public
# PHP 8.3+ with intl, pdo_mysql, bcmath, exif
# Database name/user: etic_commerce (new DB, do not reuse another site)

bash scripts/deploy.sh
```

On first run, copy `.env.production.example` to `.env` on the server, set `APP_URL`, `APP_KEY`, and MySQL credentials, then run `bash scripts/server-setup.sh` again.

Optional Nginx snippet: `deploy/nginx-etic-commerce.conf.example` (same 80/443, unique `server_name`).

- `APP_ENV=production`, `APP_DEBUG=false`
- Queue worker if jobs are enabled (`queue:work`); default production example uses the `database` driver without a new port
- `php artisan sitemap:generate` (or scheduled) for `public/sitemap.xml`
- Point DNS to a single canonical host; redirects handle the other
