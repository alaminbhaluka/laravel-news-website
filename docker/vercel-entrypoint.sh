#!/bin/sh
set -eu

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan vercel:migrate
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-80}"
