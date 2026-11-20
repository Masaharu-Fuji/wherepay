#!/usr/bin/env bash
# SQLite lives on the Fly Volume mounted at /data.
# A Postgres URL can refuse connections while that server wakes up.
set -euo pipefail

cd "${APP_ROOT:-/var/www/html}"

connection="${DB_CONNECTION:-sqlite}"

if [ "$connection" = "sqlite" ]; then
    db="${DB_DATABASE:-/data/database.sqlite}"
    case "$db" in
        /data|/data/*)
            if ! grep -q ' /data ' /proc/mounts; then
                if [ "${APP_ENV:-}" = "production" ]; then
                    echo "SQLite volume is not mounted at /data" >&2
                    exit 1
                fi
                exit 0
            fi
            ;;
    esac
    dir="$(dirname "$db")"
    mkdir -p "$dir"
    if [ ! -f "$db" ]; then
        touch "$db"
    fi
    if [ "$(id -u)" -eq 0 ]; then
        chown -R www-data:www-data "$dir"
        chmod 775 "$dir"
        chmod 664 "$db"
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
        break
    fi
    case "$connection" in
        pgsql|postgres|postgresql) ;;
        *)
            exit "$status"
            ;;
    esac
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

if [ "$connection" = "sqlite" ]; then
    case "$db" in
        /data|/data/*)
            if [ "$(id -u)" -eq 0 ]; then
                chown -R www-data:www-data "$dir"
                chmod 664 "$db"
            fi
            ;;
    esac
fi
