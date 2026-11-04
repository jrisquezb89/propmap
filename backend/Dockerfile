# syntax=docker/dockerfile:1

# ---- Stage 1: PHP dependencies (no dev packages) ----------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Install deps before copying the app so this layer is cached across code changes.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---- Stage 2: runtime -------------------------------------------------------
# FrankenPHP = PHP + Caddy in one binary: a single process listening on $PORT,
# which is what Render's free tier expects (no nginx + php-fpm + supervisor).
FROM dunglas/frankenphp:1-php8.5-alpine

RUN install-php-extensions pdo_pgsql intl opcache

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=8080

WORKDIR /app
COPY --from=vendor /app /app
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN php artisan package:discover --ansi \
    && chmod +x /usr/local/bin/entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080
ENTRYPOINT ["entrypoint"]
