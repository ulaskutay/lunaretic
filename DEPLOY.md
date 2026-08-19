# Etic Commerce — Deploy on a shared VPS (IP only)

This server already runs other sites. Isolation rules:

| Existing | Leave untouched |
|----------|-----------------|
| `cokalabalik.com` | Node on **127.0.0.1:3000**, own nginx vhost |
| `eticajans.com` | PHP vhost + `/www/wwwroot/eticajans.com` |
| `nalcirealestate.com` | PHP/SSL vhost |
| aaPanel nginx/php-fpm | Shared, do not reinstall |

| This project | Isolated as |
|--------------|-------------|
| Files | `/www/wwwroot/etic-commerce` (new folder only) |
| Nginx | `server_name 95.217.160.252;` — **not** `default_server`, **not** `_` |
| Next.js | **127.0.0.1:3010** (3000 is cokalabalik) |
| DB | `omni_panel` MySQL (aaPanel) — never `eticajans` |
| Logs | `/www/wwwlogs/etic-commerce.log` |
| systemd | `etic-storefront`, `etic-queue` only |

Do **not** run `scripts/provision.sh` here (it exits if aaPanel is present).

`eticajans.com`, `cokalabalik.com`, `nalcirealestate.com` keep answering on their domains. Only `http://95.217.160.252` hits this shop.

## First upload

From the Mac, after SSH works (`ssh etic-vps`):

```bash
bash scripts/deploy.sh
```

Then install the nginx snippet **as a new file**:

`/www/server/panel/vhost/nginx/etic-commerce.conf`

(copy from `deploy/nginx-etic-commerce.conf.example`). `nginx -t` then reload. Do not edit the other vhost files.

## URLs

- Shop: `http://95.217.160.252`
- Admin: `http://95.217.160.252/lunar`

## omnipanel.co (aaPanel)

**Do not** create a new PHP site with root `/www/wwwroot/omnipanel.co` — aaPanel leaves an empty folder with no `index.php` and nginx returns **403 Forbidden**.

Use the existing app at `/www/wwwroot/etic-commerce/public`:

1. aaPanel → Website → `omnipanel.co` → **Site directory** → `/www/wwwroot/etic-commerce/public` (or `deploy/nginx-omnipanel.co.conf.example`).
2. Domains: `omnipanel.co`, `www.omnipanel.co`, `*.omnipanel.co` (wildcard DNS A → server IP). Check `server_name` has no typos (`*.omnipanel.co` not `*.omnipanle.co`).
3. SSL: wildcard `*.omnipanel.co` needs DNS-01 in aaPanel SSL panel; TXT records in Cloudflare if DNS is there.
4. Laravel `.env`:
   - `APP_URL=https://omnipanel.co`
   - `ETIC_BASE_DOMAIN=omnipanel.co`
   - `ETIC_PLATFORM_HOSTS=omnipanel.co,www.omnipanel.co`
   - `SESSION_SECURE_COOKIE=true`
5. Include `deploy/nginx/laravel-locations.conf` (admin/API/storage only) and proxy `location /` to Next.js `127.0.0.1:3010`. Storefront pages (`/urun`, `/koleksiyon`, `/sepet`, …) must not be forced to PHP.
6. Storefront SSR: `/etc/hosts` must contain `127.0.0.1 omnipanel.co www.omnipanel.co` and `storefront` `LARAVEL_URL=http://omnipanel.co`. Do not open extra ports (8081).

Production URLs:

- Vitrin: `https://omnipanel.co`
- Platform: `https://omnipanel.co/platform`
- Mağaza panel: `https://{handle}.omnipanel.co/lunar`

## MySQL (production)

aaPanel’de `omni_panel` veritabanı ve kullanıcısı oluşturulduktan sonra sunucuda `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=omni_panel
DB_USERNAME=omni_panel
DB_PASSWORD=<aaPanel şifresi>
SESSION_DRIVER=database
CACHE_STORE=database
```

SQLite’tan geçiş (mevcut mağaza verisi korunur):

```bash
cd /www/wwwroot/etic-commerce
bash scripts/migrate-to-mysql.sh
```

Script: MySQL bağlantısını doğrular → `migrate` → SQLite satırlarını kopyalar → `database.sqlite.bak.*` yedeği bırakır.
