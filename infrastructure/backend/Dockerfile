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
COPY backend/ ./
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

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
RUN touch .env \
    && php -r "require 'vendor/autoload.php';" \
    && APP_SECRET=build-only-placeholder-32-chars \
        APP_URL=https://planner.invalid \
        COOKIE_SECURE=true \
        COOKIE_SAME_SITE=Lax \
        CORS_ALLOW_ORIGIN='^https?://localhost(:[0-9]+)?$' \
        DATABASE_URL='postgresql://planner:planner@127.0.0.1:5432/planner?serverVersion=16&charset=utf8' \
        DEFAULT_URI=https://planner.invalid \
        JWT_ACCESS_TTL_SECS=900 \
        JWT_PUBLIC_KEY=/tmp/jwt/public.pem \
        JWT_REFRESH_TTL_SECS=604800 \
        JWT_SECRET=build-only-placeholder-secret \
        JWT_SECRET_KEY=/tmp/jwt/private.pem \
        LOCK_DSN=flock \
        MAILER_DSN=null://null \
        MEDIA_DIR=/tmp/media \
        MESSENGER_TRANSPORT_DSN=in-memory:// \
        php bin/console cache:warmup --env=prod --no-debug \
    && chown -R www-data:www-data var/cache

COPY infrastructure/backend/entrypoint.sh /entrypoint.sh
COPY infrastructure/backend/entrypoint.worker.sh /entrypoint.worker.sh
COPY infrastructure/backend/supervisord.conf /etc/supervisor/supervisord.conf
RUN chmod +x /entrypoint.sh /entrypoint.worker.sh

EXPOSE 9000

ENTRYPOINT ["/entrypoint.sh"]
