#!/usr/bin/env bash
set -euo pipefail

# The SQLite file lives on the Fly volume. A missing mount must not fall
# through to an empty database on the container root filesystem.
if ! grep -q ' /data ' /proc/mounts; then
    if [ "${APP_ENV:-}" = "production" ]; then
        echo "SQLite volume is not mounted at /data" >&2
        exit 1
    fi
    exit 0
fi

db="${DB_DATABASE:-/data/database.sqlite}"
dir="$(dirname "$db")"
mkdir -p "$dir"
if [ ! -f "$db" ]; then
    touch "$db"
fi
chown -R www-data:www-data "$dir"
chmod 775 "$dir"
chmod 664 "$db"

php /var/www/html/artisan migrate --force --no-ansi

chown -R www-data:www-data "$dir"
chmod 664 "$db"
