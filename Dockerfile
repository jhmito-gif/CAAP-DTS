# syntax=docker/dockerfile:1.7

FROM node:24-bookworm-slim AS node-runtime


FROM php:8.4-fpm-bookworm AS php-base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    HOME=/tmp \
    NODE_BINARY=/usr/local/bin/node \
    XDG_CACHE_HOME=/tmp/.cache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        fonts-dejavu-core \
        git \
        gosu \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        curl \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node-runtime /usr/local/bin/node /usr/local/bin/node

WORKDIR /var/www/html


FROM php-base AS vendor-build

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist


FROM node-runtime AS node-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY postcss.config.js tailwind.config.js vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor-build /var/www/html/vendor ./vendor

RUN npm run build \
    && npm prune --omit=dev


FROM php-base AS app

COPY --from=vendor-build /var/www/html/vendor ./vendor
COPY . .
COPY --from=node-build /app/node_modules ./node_modules
COPY --from=node-build /app/public/build ./public/build

RUN composer dump-autoload \
        --classmap-authoritative \
        --no-dev \
        --no-interaction \
        --no-scripts \
    && php artisan package:discover --ansi \
    && php artisan filament:upgrade \
    && php artisan vendor:publish --tag=livewire:assets --force --no-interaction \
    && rm -rf public/storage \
    && ln -s ../storage/app/public public/storage \
    && mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/app/ocr \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/99-caap-dts.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-caap-dts.conf
COPY docker/entrypoint.sh /usr/local/bin/caap-dts-entrypoint

RUN chmod +x /usr/local/bin/caap-dts-entrypoint

ENTRYPOINT ["caap-dts-entrypoint"]
CMD ["php-fpm"]


FROM nginx:1.27-alpine AS web

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app --chown=nginx:nginx /var/www/html/public /var/www/html/public

EXPOSE 80

HEALTHCHECK --interval=10s --timeout=5s --retries=6 \
    CMD wget --quiet --tries=1 --spider http://127.0.0.1/up || exit 1
