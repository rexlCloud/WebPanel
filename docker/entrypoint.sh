#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with:" >&2
    echo "  docker compose run --rm --entrypoint php panel artisan key:generate --show" >&2
    echo "and put it into the .env file next to docker-compose.yml." >&2
    exit 1
fi

# The storage volume starts empty on first use
mkdir -p storage/app storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
