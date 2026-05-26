#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true

if [ "${RUN_STARTUP_TASKS:-true}" = "true" ]; then
    php artisan config:clear --no-interaction || true
    php artisan migrate --force --no-interaction
    php artisan storage:link --no-interaction || true

    if [ "${AUTO_DB_SEED:-true}" = "true" ]; then
        SHOULD_SEED="${AUTO_DB_SEED_FORCE:-false}"

        if [ "$SHOULD_SEED" != "true" ]; then
            USER_COUNT="$(php artisan tinker --execute='echo \Illuminate\Support\Facades\Schema::hasTable("users") ? \Illuminate\Support\Facades\DB::table("users")->count() : 0;' 2>/dev/null || echo 0)"

            if [ "$USER_COUNT" = "0" ]; then
                SHOULD_SEED="true"
            fi
        fi

        if [ "$SHOULD_SEED" = "true" ]; then
            php artisan db:seed --force --no-interaction
        else
            echo "Skipping db:seed because users table is not empty."
        fi
    fi

    php artisan optimize --no-interaction || true
fi

exec "$@"
