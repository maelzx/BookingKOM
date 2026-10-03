#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Ensure a SQLite database exists when using the default connection.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    touch "${DB_DATABASE:-/var/www/html/database/database.sqlite}"
fi

# Generate an application key for first-run convenience. For production you
# should provide APP_KEY explicitly rather than relying on this.
if [ -z "${APP_KEY:-}" ]; then
    echo "WARNING: APP_KEY is not set — generating a temporary key." >&2
    php artisan key:generate --force
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true

php artisan migrate --force

if [ "${BOOKINGKOM_RUN_SEEDER:-false}" = "true" ]; then
    php artisan db:seed --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link 2>/dev/null || true

exec "$@"
