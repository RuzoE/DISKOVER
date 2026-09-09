# syntax=docker/dockerfile:1
#
# Imagen de producción de DSLE. Multi-stage: dependencias PHP, assets con Vite y
# runtime php-fpm. El mismo binario sirve para los servicios app, scheduler y
# queue (cambia sólo el comando). Ver ADR-0016.

# ---------- 1. Dependencias PHP (sin dev) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev --no-scripts --no-interaction --prefer-dist \
        --optimize-autoloader --no-progress

# ---------- 2. Assets de frontend ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.* ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- 3. Runtime ----------
FROM php:8.4-fpm-alpine AS runtime

# Extensiones PHP mínimas para Laravel 13 + MySQL.
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql mbstring bcmath gd zip intl opcache pcntl

# mysql-client para `dsle:backup` (mysqldump).
RUN apk add --no-cache mysql-client tzdata

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-dsle.ini

# Permisos de escritura sólo donde Laravel los necesita.
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint

USER www-data

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
