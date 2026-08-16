#!/usr/bin/env bash
# Publish Etic Commerce to /var/www (plain Nginx, IP only).
# Usage: ./scripts/deploy.sh
# Optional: copy deploy.config.example to deploy.local.env

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ -f deploy.local.env ]]; then
  # shellcheck disable=SC1091
  source deploy.local.env
fi
if [[ -f deploy.config ]]; then
  # shellcheck disable=SC1091
  source deploy.config
fi

: "${REMOTE_HOST:=root@YOUR-SERVER-IP}"
: "${REMOTE_PATH:=/var/www/etic-commerce}"
: "${REMOTE_OWNER:=www}"
: "${PHP_BIN:=php}"
: "${DEPLOY_STOREFRONT:=1}"
: "${STOREFRONT_PORT:=3010}"

info() { echo "▸ $*"; }

if [[ "$REMOTE_HOST" == *"YOUR-SERVER-IP"* ]]; then
  echo "deploy.local.env içinde REMOTE_HOST=root@SUNUCU_IP yazın." >&2
  exit 1
fi

info "Vite production build"
npm run build

info "SSH $REMOTE_HOST"
ssh -o BatchMode=yes -o ConnectTimeout=20 "$REMOTE_HOST" "mkdir -p '$REMOTE_PATH'"

info "Maintenance (ignore if first deploy)"
ssh "$REMOTE_HOST" "cd '$REMOTE_PATH' && test -f artisan && $PHP_BIN artisan down --retry=60 || true"

info "rsync → $REMOTE_HOST:$REMOTE_PATH"
rsync -az --no-owner --no-group --delete \
  --include '.env.production.example' \
  --include 'storefront/.env.production.example' \
  --exclude '.env' \
  --exclude '.env.local' \
  --exclude '.env.production' \
  --exclude '.env.backup' \
  --exclude '.git/' \
  --exclude 'composer.phar' \
  --exclude 'node_modules/' \
  --exclude 'vendor/' \
  --exclude 'storefront/node_modules/' \
  --exclude 'storefront/.next/' \
  --exclude 'storefront/.env' \
  --exclude 'storefront/.env.local' \
  --exclude 'storefront/.env.production' \
  --exclude 'storage/app/public/' \
  --exclude 'storage/logs/' \
  --exclude 'storage/framework/cache/' \
  --exclude 'storage/framework/sessions/' \
  --exclude 'storage/framework/views/' \
  --exclude 'database/database.sqlite' \
  --exclude 'tests/' \
  --exclude '.cursor/' \
  --exclude 'public/hot' \
  --exclude 'public/storage' \
  --exclude 'deploy.local.env' \
  --exclude 'deploy.config' \
  -e ssh \
  "$ROOT_DIR/" "$REMOTE_HOST:$REMOTE_PATH/"

info "Sunucu kurulumu"
ssh "$REMOTE_HOST" "REMOTE_OWNER='$REMOTE_OWNER' PHP_BIN='$PHP_BIN' DEPLOY_STOREFRONT='$DEPLOY_STOREFRONT' bash '$REMOTE_PATH/scripts/server-setup.sh'"

info "Bitti."
info "IP: http://${SERVER_IP:-SUNUCU_IP}  |  Admin: /lunar"
info "Docs: DEPLOY.md"
