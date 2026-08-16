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
| DB | new `etic_commerce` or sqlite in this folder — never `eticajans` |
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
