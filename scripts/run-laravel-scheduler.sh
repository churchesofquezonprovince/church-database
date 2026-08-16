#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/home/jovicliwag2/church-database/app"
CONTAINER="church-app"

cd "$APP_DIR"

mkdir -p storage/logs

docker exec "$CONTAINER" sh -lc "cd /var/www/html && php artisan schedule:run --no-interaction" \
    >> "$APP_DIR/storage/logs/laravel-scheduler-host.log" 2>&1
