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

Next.js vitrin (ayrı süreç):

```bash
cd storefront
cp .env.example .env.local
npm install
npm run dev
```

`http://localhost:3000` Laravel `/api/v1` kullanır. Laravel `.env` içinde `ETIC_STOREFRONT_ORIGINS=http://localhost:3000` olmalı.

`composer serve` raises PHP upload limits (`upload_max_filesize` / `post_max_size`) so product images larger than 2 MB can be saved in `/lunar`. Plain `php artisan serve` uses PHP’s default 2 MB cap and shows “yüklenemedi”.

## Environment

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Public URL. Production on IP: `http://x.x.x.x` |
| `DB_*` | MySQL |
| `ETIC_STORE_HANDLE` | Default Lunar channel / store handle (`omnipanel`) |
| `ETIC_STOREFRONT_ORIGINS` | Next.js origin list for CORS (`http://localhost:3000`) |

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
| `SCOUT_DRIVER` | `null` (SQL fallback), `database`, or `meilisearch` |
| `MEILISEARCH_HOST` / `MEILISEARCH_KEY` | Meilisearch instance (local: `docker compose up -d`) |
| `SCOUT_QUEUE` | `true` in production to queue index sync |
| `ETIC_SEARCH_MAX_RESULTS` | Max product IDs returned from search (default `1000`) |

Merchant feed (no extra env): `https://{host}/feed/google-merchant.xml`

Lunar table prefix defaults to `lunar_`. Do not change it without a migration plan.

## Admin

1. Open `/lunar`
2. Sign in with the seeded staff user (see seeder output / `.env` `ADMIN_EMAIL` / `ADMIN_PASSWORD` for local only)
3. İçerik: sayfalar, blog yazıları, blog kategorileri, menüler
4. SEO: yönlendirmeler
5. Ayarlar: kargo ayarları, pazarlama ayarları (GA4 / GTM / Pixel / CAPI / Merchant feed URL)

Toplu ürün yükleme ve görseller kuyrukta işlenir. `QUEUE_CONNECTION=database` iken ayrı bir süreçte:

```bash
php artisan queue:work --timeout=900
```

`composer serve` bunu başlatmaz. Üretimde `etic-queue` systemd servisi çalışır.

## Search (Meilisearch)

Storefront catalog search uses Laravel Scout. With `SCOUT_DRIVER=null` (default), search uses SQL (name, slug, SKU). Meilisearch queries use a short HTTP timeout and fall back to SQL if the engine is down.

Local Meilisearch:

```bash
docker compose up -d
```

`.env`:

```bash
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=dev-master-key
```

Index setup and import:

```bash
php artisan lunar:meilisearch:setup
php artisan lunar:search:index "App\Etic\Catalog\Models\Product" --refresh
```

Re-import after bulk product changes. With `SCOUT_QUEUE=true`, run `queue:work` so index updates stay async.

## Tests

```bash
php artisan test
```

## Deployment notes

See [DEPLOY.md](DEPLOY.md) — server IP only, Nginx + PHP-FPM, no aaPanel / no domain.

```bash
bash scripts/deploy.sh
```
