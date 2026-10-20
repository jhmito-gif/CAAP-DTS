#!/bin/sh
set -eu

root="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
cd "$root"

target=.env.docker
url="${1:-http://10.20.2.115}"

if [ -e "$target" ]; then
    echo "$target already exists; leaving it unchanged." >&2
    exit 1
fi

command -v openssl >/dev/null 2>&1 || {
    echo "openssl is required to generate application secrets." >&2
    exit 1
}

cp .env.docker.example "$target"

app_key="$(openssl rand -base64 32 | tr -d '\n')"
db_password="$(openssl rand -hex 24)"
root_password="$(openssl rand -hex 24)"

sed -i "s|^APP_URL=.*$|APP_URL=$url|" "$target"
sed -i "s|^APP_KEY=.*$|APP_KEY=base64:$app_key|" "$target"
sed -i "s|^DB_PASSWORD=.*$|DB_PASSWORD=$db_password|" "$target"
sed -i "s|^MYSQL_PASSWORD=.*$|MYSQL_PASSWORD=$db_password|" "$target"
sed -i "s|^MYSQL_ROOT_PASSWORD=.*$|MYSQL_ROOT_PASSWORD=$root_password|" "$target"

chmod 0600 "$target"
echo "Created $target for $url."
