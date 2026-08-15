#!/usr/bin/env bash
# Run on the server inside /www/wwwroot/etic-commerce
# Uses existing Nginx/PHP-FPM (ports 80/443). Does not start artisan serve or bind extra ports.

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PHP_BIN="${PHP_BIN:-php}"
OWNER="${REMOTE_OWNER:-www}"

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  echo "PHP bulunamadı." >&2
  exit 1
fi

PHP_VERSION="$("$PHP_BIN" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
echo "PHP $PHP_VERSION"

"$PHP_BIN" -m | grep -qi intl || {
  echo "intl eklentisi yok. aaPanel → App Store → PHP $PHP_VERSION → intl'i açın." >&2
  exit 1
}

if [[ ! -f .env ]]; then
  if [[ -f .env.production.example ]]; then
    cp .env.production.example .env
    echo ".env oluşturuldu. APP_URL, DB_* ve APP_KEY değerlerini doldurun, sonra bu scripti tekrar çalıştırın."
    exit 1
  fi
  echo ".env yok." >&2
  exit 1
fi

if grep -q 'YOUR-DOMAIN' .env || grep -q '^APP_KEY=$' .env; then
  echo ".env içinde YOUR-DOMAIN veya boş APP_KEY var. Doldurmadan devam edilmez." >&2
  exit 1
fi

if [[ ! -d vendor ]]; then
  composer install --no-dev --optimize-autoloader --no-interaction
fi

if ! grep -q '^APP_KEY=base64:' .env; then
  "$PHP_BIN" artisan key:generate --force
fi

"$PHP_BIN" artisan migrate --force
"$PHP_BIN" artisan storage:link --force || true
"$PHP_BIN" artisan filament:assets || true
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R "$OWNER:$OWNER" storage bootstrap/cache public/storage || true
chmod -R ug+rwx storage bootstrap/cache

echo "Kurulum tamam. Nginx kökü: $ROOT_DIR/public"
echo "Yeni port açılmadı; siteyi aaPanel'de 80/443 üzerinden ekleyin."
