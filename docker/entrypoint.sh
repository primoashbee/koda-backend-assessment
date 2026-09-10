#!/bin/sh
set -e

# Database settings come from docker-compose environment variables, which take precedence over .env.
if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

php artisan migrate --force

# Seeders are idempotent: they reset the test user and the 12 sample projects without duplicating them.
php artisan db:seed --force

exec "$@"
