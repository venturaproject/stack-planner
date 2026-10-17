# Stage 1: install Composer dependencies
FROM composer:2 AS vendor

WORKDIR /app
COPY backend/composer.json backend/composer.lock* ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

# Stage 2: runtime php-fpm
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    postgresql-client supervisor \
    libpq-dev libzip-dev icu-dev \
    linux-headers $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_pgsql zip intl opcache \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && apk del linux-headers $PHPIZE_DEPS

COPY infrastructure/backend/opcache-prod.ini /usr/local/etc/php/conf.d/zz-opcache-prod.ini

WORKDIR /app

COPY backend/ .
COPY --from=vendor /app/vendor ./vendor

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative 2>/dev/null || true

ENV APP_ENV=prod

# Bakes var/cache/prod into the image so every new/scaled container starts
# with a warm DI container instead of compiling it from scratch on its first
# request — no live DB/mailer/JWT keys needed for this, %env(...)% values stay
# lazily-resolved placeholders in the compiled container either way, only
# resolved from the real runtime environment when a service actually reads them.
# php-fpm's worker processes run as www-data (see the stock pool config), not
# the root this RUN runs as — without the chown, any cache Symfony still has
# to write lazily at runtime (a Doctrine proxy or route not covered by the
# warmup) would hit a permission-denied write against a root-owned directory.
RUN php bin/console cache:warmup --env=prod --no-debug \
    && chown -R www-data:www-data var/cache

COPY infrastructure/backend/entrypoint.sh /entrypoint.sh
COPY infrastructure/backend/entrypoint.worker.sh /entrypoint.worker.sh
COPY infrastructure/backend/supervisord.conf /etc/supervisor/supervisord.conf
RUN chmod +x /entrypoint.sh /entrypoint.worker.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
