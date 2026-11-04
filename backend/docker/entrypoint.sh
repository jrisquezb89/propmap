#!/bin/sh
set -e

# Config is cached at boot, not build time: Render injects env vars at runtime.
php artisan config:cache
php artisan route:cache

# Render's free tier has no pre-deploy hook or shell, so migrate and seed here.
# Both are idempotent: migrate skips applied migrations, seed only fills an empty table.
php artisan migrate --force
php artisan propmap:seed-if-empty

exec frankenphp php-server --root public/ --listen ":${PORT:-8080}"
