#!/bin/sh
set -eu

root="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$root"

env_file="${ENV_FILE:-.env.docker}"

if [ ! -f "$env_file" ]; then
    echo "$env_file is missing. Run ./docker/prepare-env.sh first." >&2
    exit 1
fi

compose() {
    docker compose --env-file "$env_file" "$@"
}

compose config --quiet
compose build --pull
compose up -d --remove-orphans --wait --wait-timeout 180
compose exec -T -u www-data app php artisan documents:check
compose ps
