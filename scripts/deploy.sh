#!/usr/bin/env bash
# Publish Etic Commerce to aaPanel wwwroot without extra listen ports.
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

: "${REMOTE_HOST:=eticajans}"
: "${REMOTE_PATH:=/www/wwwroot/etic-commerce}"
: "${REMOTE_OWNER:=www}"

info() { echo "▸ $*"; }

info "Vite production build"
npm run build

info "SSH $REMOTE_HOST"
ssh -o BatchMode=yes -o ConnectTimeout=20 "$REMOTE_HOST" "mkdir -p '$REMOTE_PATH'"

info "rsync → $REMOTE_HOST:$REMOTE_PATH"
rsync -az --delete \
  --include '.env.production.example' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude '.git/' \
  --exclude 'node_modules/' \
  --exclude 'vendor/' \
  --exclude 'storage/logs/*' \
  --exclude 'storage/framework/cache/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'database/database.sqlite' \
  --exclude 'tests/' \
  --exclude '.cursor/' \
  --exclude 'public/hot' \
  --exclude 'public/storage' \
  -e ssh \
  "$ROOT_DIR/" "$REMOTE_HOST:$REMOTE_PATH/"

info "Sunucu kurulumu (composer/migrate, ekstra port yok)"
ssh "$REMOTE_HOST" "REMOTE_OWNER='$REMOTE_OWNER' bash '$REMOTE_PATH/scripts/server-setup.sh'"

info "Bitti. aaPanel → Website → Add site"
info "  Path: $REMOTE_PATH/public"
info "  PHP: 8.3+ (mevcut PHP-FPM; artisan serve kullanmayın)"
info "  Ports: 80 / 443 (mevcut Nginx)"
