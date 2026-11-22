#!/usr/bin/env bash
# =====================================================================
#  Bityshop — publicar una nueva versión
#
#  1. En tu Mac: guarda y sube tus cambios a GitHub (commit + push).
#  2. En el servidor:  sudo bash /var/www/bityshop/deploy/actualizar.sh
# =====================================================================
set -euo pipefail

PHP_VERSION="8.2"
APP_DIR="/var/www/bityshop"
RUN="sudo -u www-data COMPOSER_HOME=/var/www/.composer"

[ "$(id -u)" -eq 0 ] || { echo "Ejecuta con sudo."; exit 1; }
cd "$APP_DIR"

echo "==> Modo mantenimiento"
$RUN php artisan down || true

# Si algo falla, la tienda vuelve a estar en línea
trap '$RUN php artisan up || true' EXIT

echo "==> Descargando cambios de GitHub"
$RUN git pull --ff-only

echo "==> Dependencias"
$RUN composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Base de datos"
$RUN php artisan migrate --force

echo "==> Limpiando cachés"
$RUN php artisan optimize:clear
systemctl reload php${PHP_VERSION}-fpm

echo "==> Tienda en línea"
$RUN php artisan up
trap - EXIT

echo "Actualización terminada."
