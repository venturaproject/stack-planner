#!/bin/sh
set -e

echo "Waiting for PostgreSQL..."
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USER:-postgres}" -d "${DB_NAME:-boilerplate_symfony}" 2>/dev/null; do
    sleep 1
done
echo "PostgreSQL ready."

mkdir -p "${MEDIA_DIR:-/app/var/media}"

if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

if [ ! -f "config/jwt/private.pem" ]; then
    echo "Generating JWT keypair..."
    mkdir -p config/jwt
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
fi

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Setting up Messenger transports..."
php bin/console messenger:setup-transports

echo "Seeding initial data..."
php bin/console app:seed

exec php-fpm
