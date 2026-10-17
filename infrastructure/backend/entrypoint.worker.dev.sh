#!/bin/sh
set -e

echo "Waiting for PostgreSQL..."
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USER:-postgres}" -d "${DB_NAME:-boilerplate_symfony}" 2>/dev/null; do
    sleep 1
done
echo "PostgreSQL ready."

if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

echo "Ensuring Messenger transports exist..."
php bin/console messenger:setup-transports

echo "Starting worker (supervisord)..."
exec supervisord -c /etc/supervisor/supervisord.conf
