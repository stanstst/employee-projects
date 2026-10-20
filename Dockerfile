# --- Dependencies -----------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-scripts

# --- PHP-FPM ----------------------------------------------------------------
FROM php:8.5-fpm-alpine AS app

# revalidate_freq=0: pick up code changes synced in by Compose Watch immediately.
# clear_env=no: pass container environment variables (APP_ENV, ...) through to PHP.
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && echo "opcache.revalidate_freq=0" > "$PHP_INI_DIR/conf.d/opcache.ini" \
    && printf '[www]\nclear_env = no\n' > /usr/local/etc/php-fpm.d/zz-env.conf

WORKDIR /var/www/html
COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && rm -f bootstrap/cache/*.php

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# --- Nginx ------------------------------------------------------------------
FROM nginx:stable-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public /var/www/html/public
