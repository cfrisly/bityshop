#!/usr/bin/env bash
# =====================================================================
#  Bityshop — instalación del servidor (Ubuntu 22.04 / 24.04)
#
#  Se ejecuta UNA vez dentro del servidor:
#      sudo bash servidor-instalar.sh
#
#  Instala: PHP 8.2, Composer, MariaDB, Caddy (HTTPS automático),
#  descarga la tienda desde GitHub, crea la base de datos y el .env
#  de producción, y abre los puertos 80/443.
#  Se puede volver a ejecutar sin romper nada.
# =====================================================================
set -euo pipefail

PHP_VERSION="8.2"
APP_DIR="/var/www/bityshop"
DB_NAME="bityshop"
DB_USER="bityshop"
REPO_DEFAULT="https://github.com/cfrisly/bityshop.git"
RUN="sudo -u www-data COMPOSER_HOME=/var/www/.composer"

if [ "$(id -u)" -ne 0 ]; then
  echo "Ejecuta este script con sudo:  sudo bash servidor-instalar.sh"
  exit 1
fi

paso() { echo; echo "==> $*"; }

# ---------------------------------------------------------------------
paso "Datos de la instalación"
read -rp "Dominio de la tienda (ej. bityshop.duckdns.org): " DOMAIN
read -rp "Correo para el certificado HTTPS: " ADMIN_EMAIL
read -rp "Repositorio de GitHub [$REPO_DEFAULT]: " REPO
REPO=${REPO:-$REPO_DEFAULT}
read -rp "Rama [main]: " BRANCH
BRANCH=${BRANCH:-main}
read -rsp "Token de GitHub (deja vacío y presiona Enter si el repositorio es público): " GH_TOKEN
echo

[ -n "$DOMAIN" ] || { echo "El dominio es obligatorio."; exit 1; }

export DEBIAN_FRONTEND=noninteractive

# ---------------------------------------------------------------------
paso "Memoria de intercambio (swap) de 2 GB"
if ! swapon --show | grep -q '/swapfile'; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# ---------------------------------------------------------------------
paso "Paquetes base"
apt-get update
apt-get install -y software-properties-common ca-certificates curl gnupg unzip git \
  debian-keyring debian-archive-keyring apt-transport-https openssl
echo "iptables-persistent iptables-persistent/autosave_v4 boolean true" | debconf-set-selections
echo "iptables-persistent iptables-persistent/autosave_v6 boolean true" | debconf-set-selections
apt-get install -y iptables-persistent

# ---------------------------------------------------------------------
paso "PHP ${PHP_VERSION}"
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y \
  php${PHP_VERSION}-fpm php${PHP_VERSION}-cli php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl \
  php${PHP_VERSION}-zip php${PHP_VERSION}-gd php${PHP_VERSION}-intl \
  php${PHP_VERSION}-bcmath php${PHP_VERSION}-opcache

cat > /etc/php/${PHP_VERSION}/fpm/conf.d/99-bityshop.ini <<'INI'
; Ajustes de Bityshop
upload_max_filesize = 20M
post_max_size = 32M
memory_limit = 256M
max_execution_time = 60
expose_php = Off
session.upload_progress.enabled = 0
opcache.enable = 1
opcache.memory_consumption = 128
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2
INI
systemctl enable --now php${PHP_VERSION}-fpm
systemctl restart php${PHP_VERSION}-fpm

# ---------------------------------------------------------------------
paso "Composer"
if ! command -v composer >/dev/null 2>&1; then
  EXPECTED=$(curl -fsSL https://composer.github.io/installer.sig)
  curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
  ACTUAL=$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")
  if [ "$EXPECTED" != "$ACTUAL" ]; then
    echo "La firma del instalador de Composer no coincide. Se detiene la instalación."
    exit 1
  fi
  php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi
mkdir -p /var/www/.composer
chown -R www-data:www-data /var/www/.composer

# ---------------------------------------------------------------------
paso "Base de datos (MariaDB)"
apt-get install -y mariadb-server
systemctl enable --now mariadb

# Si ya existe un .env, se conserva su contraseña
DB_PASS=""
if [ -f "$APP_DIR/.env" ]; then
  DB_PASS=$(grep -E '^DB_PASSWORD=' "$APP_DIR/.env" | cut -d= -f2- | tr -d '"')
fi
[ -n "$DB_PASS" ] || DB_PASS=$(openssl rand -hex 16)

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ---------------------------------------------------------------------
paso "Descargando la tienda desde GitHub"
if [ -n "$GH_TOKEN" ]; then
  CLONE_URL="https://x-access-token:${GH_TOKEN}@${REPO#https://}"
else
  CLONE_URL="$REPO"
fi

mkdir -p "$APP_DIR"
chown www-data:www-data "$APP_DIR"
if [ ! -d "$APP_DIR/.git" ]; then
  sudo -u www-data git clone -b "$BRANCH" "$CLONE_URL" "$APP_DIR"
else
  sudo -u www-data git -C "$APP_DIR" remote set-url origin "$CLONE_URL"
  sudo -u www-data git -C "$APP_DIR" pull --ff-only origin "$BRANCH"
fi

# ---------------------------------------------------------------------
paso "Archivo .env de producción"
if [ ! -f "$APP_DIR/.env" ]; then
  cat > "$APP_DIR/.env" <<ENV
APP_NAME="Bityshop"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://${DOMAIN}

LOG_CHANNEL=daily
LOG_LEVEL=warning
APP_LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
QUEUE_DRIVER=sync
QUEUE_CONNECTION=sync
FILESYSTEM_DRIVER=local

# Correo: mientras no configures SMTP, los correos se guardan en
# storage/logs. Ver GUIA.md, paso 8, para enviarlos de verdad.
MAIL_MAILER=log
MAIL_DRIVER=log
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=${ADMIN_EMAIL}
MAIL_FROM_NAME="Bityshop"

# Pasarelas de pago: siempre en modo de prueba
ADYEN_IS_TEST=true
ENV
fi
chown www-data:www-data "$APP_DIR/.env"
chmod 640 "$APP_DIR/.env"

# ---------------------------------------------------------------------
paso "Dependencias de PHP (composer install)"
cd "$APP_DIR"
$RUN composer install --no-dev --optimize-autoloader --no-interaction

paso "Preparando Laravel"
if ! grep -qE '^APP_KEY=.+' .env; then
  $RUN php artisan key:generate --force
fi
$RUN php artisan migrate --force
[ -L public/storage ] || $RUN php artisan storage:link
$RUN php artisan optimize:clear

# Permisos: todo pertenece a www-data
chown -R www-data:www-data "$APP_DIR"
find "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;

# Tareas programadas de Laravel
cat > /etc/cron.d/bityshop <<CRON
* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1
CRON

# ---------------------------------------------------------------------
paso "Servidor web Caddy con HTTPS automático"
if ! command -v caddy >/dev/null 2>&1; then
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' \
    | gpg --dearmor --yes -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
  curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' \
    > /etc/apt/sources.list.d/caddy-stable.list
  apt-get update
  apt-get install -y caddy
fi
usermod -aG www-data caddy

cat > /etc/caddy/Caddyfile <<CADDY
{
    email ${ADMIN_EMAIL}
}

${DOMAIN} {
    root * ${APP_DIR}/public
    encode gzip

    request_body {
        max_size 32MB
    }

    header {
        Strict-Transport-Security "max-age=31536000"
        X-Content-Type-Options "nosniff"
        X-Frame-Options "SAMEORIGIN"
        Referrer-Policy "strict-origin-when-cross-origin"
        -Server
    }

    php_fastcgi unix//run/php/php${PHP_VERSION}-fpm.sock
    file_server
}
CADDY
systemctl enable caddy
systemctl restart caddy

# ---------------------------------------------------------------------
paso "Abriendo los puertos 80 y 443 en el firewall del servidor"
for PORT in 80 443; do
  iptables -C INPUT -p tcp --dport "$PORT" -m conntrack --ctstate NEW -j ACCEPT 2>/dev/null \
    || iptables -I INPUT -p tcp --dport "$PORT" -m conntrack --ctstate NEW -j ACCEPT
done
netfilter-persistent save

# ---------------------------------------------------------------------
echo
echo "====================================================================="
echo " Instalación terminada."
echo
echo " Tu tienda:  https://${DOMAIN}"
echo
echo " La base de datos está vacía. Para copiar tus productos, imágenes"
echo " y usuarios, ejecuta en tu Mac:  bash deploy/subir-datos.sh"
echo
echo " Si la página no abre, revisa en Oracle que la regla de entrada para"
echo " los puertos 80 y 443 esté creada (GUIA.md, paso 3)."
echo "====================================================================="
