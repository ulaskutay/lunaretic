#!/usr/bin/env bash
# First-time VPS bootstrap. Run as root on Ubuntu 22.04/24.04.
# No aaPanel. Binds the site to port 80 (server IP).

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SITE_ROOT="${REMOTE_PATH:-/var/www/etic-commerce}"
OWNER="${REMOTE_OWNER:-www-data}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "root olarak çalıştırın." >&2
  exit 1
fi

if [[ -d /www/server/panel ]]; then
  echo "aaPanel var. provision.sh çalıştırma — diğer sitelerin Nginx/PHP kurulumunu bozar." >&2
  echo "Bu sunucuda yalnızca yeni vhost + /www/wwwroot/etic-commerce kullan." >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y ca-certificates curl gnupg unzip git software-properties-common nginx mysql-server composer

if ! php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);' 2>/dev/null; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update
  apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath php8.3-exif
  update-alternatives --set php /usr/bin/php8.3 || true
else
  apt-get install -y php-fpm php-cli php-mysql php-xml php-mbstring php-curl php-zip php-gd php-intl php-bcmath php-exif
fi

if ! command -v node >/dev/null 2>&1 || ! node -v | grep -Eq 'v(2[0-9]|[3-9])'; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
fi

PHP_VER="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
FPM_SOCK="/run/php/php${PHP_VER}-fpm.sock"

if [[ ! -S "$FPM_SOCK" ]]; then
  echo "PHP-FPM soketi yok: $FPM_SOCK" >&2
  exit 1
fi

php -m | grep -qi intl || {
  echo "intl yok. apt install php${PHP_VER}-intl" >&2
  exit 1
}

mkdir -p "$SITE_ROOT" /etc/nginx/sites-available /etc/nginx/sites-enabled
mkdir -p /etc/php/"$PHP_VER"/fpm/conf.d

cp "$ROOT_DIR/deploy/php/99-etic.ini" /etc/php/"$PHP_VER"/fpm/conf.d/99-etic.ini

NGINX_SRC="$ROOT_DIR/deploy/nginx-etic-commerce.conf.example"
NGINX_DST="/etc/nginx/sites-available/etic-commerce"
sed \
  -e "s#/var/www/etic-commerce#$SITE_ROOT#g" \
  -e "s#unix:/run/php/php8.3-fpm.sock#unix:$FPM_SOCK#g" \
  "$NGINX_SRC" > "$NGINX_DST"

rm -f /etc/nginx/sites-enabled/default
ln -sfn "$NGINX_DST" /etc/nginx/sites-enabled/etic-commerce
nginx -t
systemctl enable --now nginx "php${PHP_VER}-fpm"

cp "$ROOT_DIR/deploy/cron/etic-commerce" /etc/cron.d/etic-commerce
sed -i "s#/var/www/etic-commerce#$SITE_ROOT#g" /etc/cron.d/etic-commerce

for unit in etic-queue etic-storefront; do
  sed "s#/var/www/etic-commerce#$SITE_ROOT#g" "$ROOT_DIR/deploy/systemd/${unit}.service" > "/etc/systemd/system/${unit}.service"
done
systemctl daemon-reload
systemctl enable etic-queue etic-storefront

if command -v ufw >/dev/null 2>&1; then
  ufw allow OpenSSH || true
  ufw allow 80/tcp || true
  ufw --force enable || true
fi

if ! mysql -e "USE etic_commerce" >/dev/null 2>&1; then
  DB_PASS="$(openssl rand -base64 18 | tr -d '/+=' | head -c 20)"
  mysql <<SQL
CREATE DATABASE IF NOT EXISTS etic_commerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'etic_commerce'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON etic_commerce.* TO 'etic_commerce'@'localhost';
FLUSH PRIVILEGES;
SQL
  echo "MySQL kullanıcı etic_commerce oluşturuldu. Şifreyi .env DB_PASSWORD olarak yazın:"
  echo "  $DB_PASS"
fi

chown -R "$OWNER:$OWNER" "$SITE_ROOT" || true

systemctl reload "php${PHP_VER}-fpm"
systemctl reload nginx

echo "Provision bitti. Site: http://$(hostname -I | awk '{print $1}')"
echo "Sonraki: .env doldur, sonra Mac'ten bash scripts/deploy.sh"
