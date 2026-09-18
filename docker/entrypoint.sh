#!/bin/sh
# ====================================================================
# mi ERP - Entrypoint del contenedor de aplicación
#  1. Prepara directorios de escritura (volúmenes persistentes)
#  2. Provisiona la base de datos y el admin en el primer arranque
#     (docker/init_db.php — idempotente, no actúa si ya hay instalación)
#  3. Arranca Apache en primer plano
# ====================================================================
set -e

APP_DIR="/var/www/html"

mkdir -p "$APP_DIR/storage/backups" "$APP_DIR/storage/lic" "$APP_DIR/logs"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/config" "$APP_DIR/logs" 2>/dev/null || true

echo "===================================================="
echo "  mi ERP - iniciando contenedor"
echo "===================================================="
php "$APP_DIR/docker/init_db.php"

exec apache2-foreground
