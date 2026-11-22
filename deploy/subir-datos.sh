#!/usr/bin/env bash
# =====================================================================
#  Bityshop — copiar la base de datos y las imágenes de tu Mac al servidor
#
#  Se ejecuta en tu Mac, desde la carpeta del proyecto:
#      bash deploy/subir-datos.sh
#
#  ATENCIÓN: reemplaza TODA la base de datos del servidor por la de tu Mac.
#  Úsalo para la primera publicación (o cuando quieras volver a copiar todo).
# =====================================================================
set -euo pipefail

cd "$(dirname "$0")/.."
[ -f .env ] || { echo "Ejecuta este script desde la carpeta del proyecto (no encuentro .env)."; exit 1; }

leer_env() { grep -E "^$1=" .env | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'; }

DB_HOST=$(leer_env DB_HOST);         DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=$(leer_env DB_PORT);         DB_PORT=${DB_PORT:-3306}
DB_DATABASE=$(leer_env DB_DATABASE)
DB_USERNAME=$(leer_env DB_USERNAME)
DB_PASSWORD=$(leer_env DB_PASSWORD)

read -rp "IP pública del servidor: " SERVER_IP
read -rp "Ruta de la llave SSH [~/.ssh/oracle-bityshop.key]: " SSH_KEY
SSH_KEY=${SSH_KEY:-~/.ssh/oracle-bityshop.key}
SSH_KEY=${SSH_KEY/#\~/$HOME}
SERVER_USER=ubuntu

command -v mysqldump >/dev/null 2>&1 || {
  echo "No encuentro mysqldump. Instálalo con:  brew install mysql-client"
  echo "y vuelve a intentar con:  PATH=\"\$(brew --prefix mysql-client)/bin:\$PATH\" bash deploy/subir-datos.sh"
  exit 1
}

echo
echo "Se va a REEMPLAZAR la base de datos del servidor ${SERVER_IP}"
echo "con tu base local '${DB_DATABASE}'."
read -rp "¿Continuar? (escribe si): " OK
[ "$OK" = "si" ] || { echo "Cancelado."; exit 0; }

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

echo "==> Exportando la base de datos local"
MYSQL_PWD="$DB_PASSWORD" mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
  --single-transaction --routines --no-tablespaces --default-character-set=utf8mb4 \
  "$DB_DATABASE" | gzip > "$TMP/db.sql.gz"

echo "==> Empaquetando imágenes de productos (storage/app/public)"
tar -czf "$TMP/public-files.tgz" -C storage/app public

echo "==> Subiendo al servidor"
scp -i "$SSH_KEY" "$TMP/db.sql.gz" "$TMP/public-files.tgz" "${SERVER_USER}@${SERVER_IP}:/tmp/"

echo "==> Importando en el servidor"
ssh -i "$SSH_KEY" "${SERVER_USER}@${SERVER_IP}" 'sudo bash -s' <<'REMOTO'
set -euo pipefail
APP_DIR=/var/www/bityshop
cd "$APP_DIR"
sudo -u www-data php artisan down || true
gunzip -c /tmp/db.sql.gz | mysql bityshop
tar -xzf /tmp/public-files.tgz -C "$APP_DIR/storage/app"
chown -R www-data:www-data "$APP_DIR/storage"
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan up
rm -f /tmp/db.sql.gz /tmp/public-files.tgz
echo "Datos importados."
REMOTO

echo
echo "Listo. Tus productos, imágenes y usuarios ya están en el servidor."
echo "Recuerda cambiar la contraseña del administrador (GUIA.md, paso 9)."
