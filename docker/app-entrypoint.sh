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

if [ ! -f public/build/manifest.json ] \
    || [ package.json -nt public/build/manifest.json ] \
    || [ vite.config.js -nt public/build/manifest.json ] \
    || find resources -type f -newer public/build/manifest.json -print -quit | grep -q .; then
    npm install --no-audit --no-fund --no-package-lock
    npm run build
fi

if [ -f .env ] && ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --seed --force
fi

exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
