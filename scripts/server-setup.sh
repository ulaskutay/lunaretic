#!/usr/bin/env bash
# Run on the server inside /var/www/etic-commerce
# Nginx:80 (server IP). Next.js binds 127.0.0.1:3000 only.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PHP_BIN="${PHP_BIN:-php}"
OWNER="${REMOTE_OWNER:-www}"
DEPLOY_STOREFRONT="${DEPLOY_STOREFRONT:-1}"

maybe_sudo() {
  if [[ "$(id -u)" -eq 0 ]]; then
    "$@"
  elif command -v sudo >/dev/null 2>&1; then
    sudo "$@"
  else
    "$@"
  fi
}

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "PHP bulunamadı. scripts/provision.sh çalıştırın." >&2
  exit 1
fi

PHP_VERSION="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
echo "PHP $PHP_VERSION"

"$PHP_BIN" -m | grep -qi intl || {
  echo "intl eklentisi yok. apt install php${PHP_VERSION}-intl" >&2
  exit 1
}

if [[ ! -f .env ]]; then
  if [[ -f .env.production.example ]]; then
    cp .env.production.example .env
    echo ".env oluşturuldu. APP_URL=http://SUNUCU_IP ve DB_PASSWORD doldurup scripti tekrar çalıştırın."
    exit 1
  fi
  echo ".env yok." >&2
  exit 1
fi

if grep -q 'YOUR-SERVER-IP' .env || grep -q 'YOUR-DOMAIN' .env; then
  echo ".env içinde YOUR-SERVER-IP (veya eski YOUR-DOMAIN) var. APP_URL=http://SUNUCU_IP yazın." >&2
  exit 1
fi

if [[ "$DEPLOY_STOREFRONT" == "1" && -d storefront ]]; then
  if [[ ! -f storefront/.env.production && ! -f storefront/.env.local && ! -f storefront/.env ]]; then
    if [[ -f storefront/.env.production.example ]]; then
      cp storefront/.env.production.example storefront/.env.production
      echo "storefront/.env.production oluşturuldu. NEXT_PUBLIC_API_URL=http://SUNUCU_IP/api/v1 yazın."
      exit 1
    fi
  fi

  if grep -qE 'YOUR-SERVER-IP|YOUR-DOMAIN' storefront/.env.production 2>/dev/null \
    || grep -qE 'YOUR-SERVER-IP|YOUR-DOMAIN' storefront/.env 2>/dev/null; then
    echo "storefront env içinde placeholder IP/domain var." >&2
    exit 1
  fi
fi

composer_cmd() {
  if [[ -f "$ROOT_DIR/composer.phar" ]]; then
    "$PHP_BIN" "$ROOT_DIR/composer.phar" "$@"
    return
  fi
  local sys_ver
  sys_ver="$(composer --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+' | head -1 || true)"
  if [[ -n "$sys_ver" ]] && awk -v v="$sys_ver" 'BEGIN { split(v, a, "."); exit !((a[1] > 2) || (a[1] == 2 && a[2] >= 2)) }'; then
    composer "$@"
    return
  fi
  echo "Composer 2.2+ yok; composer.phar indiriliyor (yalnız bu proje)."
  curl -fsSL https://getcomposer.org/download/latest-stable/composer.phar -o "$ROOT_DIR/composer.phar"
  "$PHP_BIN" "$ROOT_DIR/composer.phar" "$@"
}

composer_cmd install --no-dev --optimize-autoloader --no-interaction

if ! grep -q '^APP_KEY=base64:' .env; then
  "$PHP_BIN" artisan key:generate --force
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache storage/app/public

"$PHP_BIN" artisan migrate --force
rm -f public/storage
ln -sfn ../storage/app/public public/storage
"$PHP_BIN" artisan filament:assets || true
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan queue:restart || true

if [[ "$DEPLOY_STOREFRONT" == "1" && -d storefront ]]; then
  if ! command -v npm >/dev/null 2>&1; then
    echo "npm yok. scripts/provision.sh veya apt/nodejs 20 kurun." >&2
    exit 1
  fi

  (
    cd storefront
    if [[ -f .env.production && ! -f .env ]]; then
      cp .env.production .env
    fi
    npm ci
    npm run build
  )
fi

maybe_sudo chown -R "$OWNER:$OWNER" storage bootstrap/cache public/storage || true
chmod -R ug+rwx storage bootstrap/cache

"$PHP_BIN" artisan up || true

maybe_sudo systemctl restart etic-queue || true
maybe_sudo systemctl restart etic-storefront || true
maybe_sudo systemctl reload "php${PHP_VERSION}-fpm" || true
maybe_sudo systemctl reload nginx || true

echo "Kurulum tamam. Vitrin: http://SUNUCU_IP  Admin: http://SUNUCU_IP/lunar"
echo "Next.js 127.0.0.1:${STOREFRONT_PORT:-3010} (cokalabalik 3000'e dokunulmaz)."
