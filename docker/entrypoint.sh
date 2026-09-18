#!/bin/sh
set -eu

install -d -o www-data -g www-data -m 0775 \
    storage \
    storage/app \
    storage/app/private \
    storage/app/public \
    storage/app/ocr \
    storage/framework \
    storage/framework/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chown www-data:www-data bootstrap/cache
chmod 0775 bootstrap/cache

case "${1:-}" in
    php-fpm*) exec "$@" ;;
    *) exec gosu www-data "$@" ;;
esac
