#!/usr/bin/env bash
# Production: SQLite → MySQL geçişi (aaPanel omni_panel DB hazır olmalı).
# 1) .env içine DB_PASSWORD yazın
# 2) bash scripts/migrate-to-mysql.sh

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

PHP_BIN="${PHP_BIN:-php}"

echo "=== MySQL geçiş ön kontrol ==="

if ! grep -q '^DB_CONNECTION=mysql' .env; then
  echo "DB_CONNECTION=mysql değil. .env güncelleyin:" >&2
  echo "  DB_CONNECTION=mysql" >&2
  echo "  DB_HOST=127.0.0.1" >&2
  echo "  DB_PORT=3306" >&2
  echo "  DB_DATABASE=omni_panel" >&2
  echo "  DB_USERNAME=omni_panel" >&2
  echo "  DB_PASSWORD=<aaPanel şifresi>" >&2
  exit 1
fi

if grep -q '^DB_PASSWORD=$' .env || grep -q '^DB_PASSWORD=\s*$' .env; then
  echo "DB_PASSWORD boş. aaPanel şifresini .env içine yazın, sonra scripti tekrar çalıştırın." >&2
  exit 1
fi

"$PHP_BIN" artisan config:clear

echo "=== Bağlantı testi ==="
"$PHP_BIN" artisan tinker --execute="DB::connection('mysql')->getPdo(); echo 'mysql:ok'.PHP_EOL;" || {
  echo "MySQL bağlantısı başarısız. DB bilgilerini kontrol edin." >&2
  exit 1
}

echo "=== SQLite → MySQL ==="
"$PHP_BIN" artisan etic:migrate-sqlite-to-mysql --force

echo "=== Cache & servisler ==="
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan queue:restart || true

if command -v systemctl >/dev/null 2>&1; then
  systemctl restart etic-queue etic-storefront 2>/dev/null || true
fi

if [[ -f /etc/init.d/php-fpm-83 ]]; then
  /etc/init.d/php-fpm-83 reload 2>/dev/null || true
fi

echo ""
echo "Geçiş tamam. SQLite yedeği: database/database.sqlite.bak.*"
echo "Doğrulama: curl -H 'Host: omnipanel.co' http://127.0.0.1/api/v1/bootstrap"
