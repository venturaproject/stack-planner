#!/bin/sh
set -e

echo "Waiting for PostgreSQL..."
until pg_isready -h "${DB_HOST:-postgres}" -p "${DB_PORT:-5432}" -U "${DB_USER:-postgres}" -d "${DB_NAME}" 2>/dev/null; do
    sleep 1
done
echo "PostgreSQL ready."

mkdir -p "${MEDIA_DIR:-/app/var/media}"

exec php-fpm
