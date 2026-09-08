# syntax=docker/dockerfile:1.7
FROM composer:2 AS composer

FROM dunglas/frankenphp:1-php8.5-alpine AS build

RUN install-php-extensions dom intl opcache
WORKDIR /app

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

COPY . .
RUN composer dump-autoload --classmap-authoritative --no-dev --no-scripts \
    && APP_ENV=prod APP_DEBUG=0 php bin/console tailwind:build --minify \
    && APP_ENV=prod APP_DEBUG=0 php bin/console asset-map:compile \
    && APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear

FROM dunglas/frankenphp:1-php8.5-alpine AS runtime

RUN install-php-extensions dom intl opcache
WORKDIR /app
COPY --from=build --chown=www-data:www-data /app /app
COPY docker/Caddyfile /etc/frankenphp/Caddyfile
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

ENV APP_ENV=prod APP_DEBUG=0 SERVER_NAME=:8080
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=3s --start-period=10s --retries=3 CMD wget -qO- http://127.0.0.1:8080/de/ >/dev/null || exit 1
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
