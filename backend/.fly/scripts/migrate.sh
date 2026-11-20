#!/usr/bin/env bash
# Apply migrations before nginx listens. Free Render Postgres can take a
# minute to accept connections on a cold start, so connection failures retry.
# A schema error stops immediately.
set -eu

cd "${APP_ROOT:-/var/www/html}"

connection="${DB_CONNECTION:-sqlite}"

if [ "$connection" = "sqlite" ]; then
    mkdir -p database
    if [ ! -f database/database.sqlite ]; then
        touch database/database.sqlite
    fi
    if [ "$(id -u)" -eq 0 ]; then
        chown www-data:www-data database database/database.sqlite
    fi
fi

attempt=1
max=40
while true; do
    set +e
    output="$(php artisan migrate --force --no-ansi 2>&1)"
    status=$?
    set -e
    printf '%s\n' "$output"
    if [ "$status" -eq 0 ]; then
        exit 0
    fi
    if ! printf '%s\n' "$output" | grep -Eq 'SQLSTATE\[08006\]|SQLSTATE\[08001\]|SQLSTATE\[HY000\].*[Cc]onnection refused|could not connect|Connection refused|Connection timed out|server closed the connection|temporary failure|Name or service not known|could not translate host name|getaddrinfo'; then
        exit "$status"
    fi
    if [ "$attempt" -ge "$max" ]; then
        echo "migrate failed after ${max} attempts" >&2
        exit 1
    fi
    echo "database is not ready (attempt ${attempt}/${max}); retrying" >&2
    attempt=$((attempt + 1))
    sleep 3
done
