#!/bin/sh
set -e
# Render asigna el puerto en $PORT (por defecto 10000)
PORT="${PORT:-10000}"
sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php /var/www/html/scripts/init-db-render.php || true

exec apache2-foreground
