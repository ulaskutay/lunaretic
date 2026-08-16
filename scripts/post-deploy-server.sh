#!/usr/bin/env bash
# Run on the server after deploy: bash scripts/post-deploy-server.sh
set -euo pipefail

ROOT="/www/wwwroot/etic-commerce"
cd "$ROOT"

cp "$ROOT/deploy/nginx-etic-commerce-ip.conf" /www/server/panel/vhost/nginx/etic-commerce.conf

nginx -t
systemctl reload nginx

php artisan migrate --force
php artisan config:clear
php artisan optimize

if [ ! -f /etc/cron.d/etic-commerce ]; then
  cp "$ROOT/deploy/cron/etic-commerce" /etc/cron.d/etic-commerce
  chmod 644 /etc/cron.d/etic-commerce
fi

php artisan up || true
systemctl restart etic-queue etic-storefront || true

echo "Post-deploy tamam."
echo "Shop: http://95.217.160.252"
echo "Admin: http://95.217.160.252/lunar"
