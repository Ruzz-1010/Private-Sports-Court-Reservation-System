#!/usr/bin/env sh
set -eu

SERVICE_MODE="${SERVICE_MODE:-web}"
APP_PORT="${PORT:-8080}"

mkdir -p \
    storage/app/private/receipts \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/cache/locks \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data storage bootstrap/cache

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache

case "$SERVICE_MODE" in
    web)
        php artisan migrate --force
        sed -i "s/^Listen .*/Listen $APP_PORT/" /etc/apache2/ports.conf
        sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:$APP_PORT>/" /etc/apache2/sites-available/000-default.conf
        exec apache2-foreground
        ;;
    worker)
        exec php artisan queue:work --sleep=3 --tries=3 --timeout=120 --max-time=3600
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        echo "Unknown SERVICE_MODE: $SERVICE_MODE" >&2
        exit 1
        ;;
esac
