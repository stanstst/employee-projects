#!/bin/sh
set -e

cd /var/www/html

[ -f .env ] || cp .env.example .env

# Generate a key only when none is provided via the environment or .env.
if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan package:discover --ansi

if [ "$APP_ENV" = "production" ]; then
    php artisan optimize
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
