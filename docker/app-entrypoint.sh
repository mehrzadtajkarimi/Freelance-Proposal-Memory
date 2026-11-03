#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ -f .env ] && ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --seed --force
fi

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
