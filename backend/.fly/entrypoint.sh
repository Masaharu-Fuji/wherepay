#!/usr/bin/env sh
set -eu

# Fly listens on 8080. Honor PORT when the host injects one.
port="${PORT:-8080}"
case "$port" in
    ''|*[!0-9]*)
        echo "PORT must be a number" >&2
        exit 1
        ;;
esac
nginx_site="${NGINX_SITE:-/etc/nginx/sites-available/default}"
sed -i \
    -e "s/listen 8080 default_server;/listen ${port} default_server;/" \
    -e "s/listen \\[::\\]:8080 default_server;/listen [::]:${port} default_server;/" \
    "$nginx_site"

# Laravel accepts the key only with this prefix.
if [ -n "${APP_KEY:-}" ]; then
    case "$APP_KEY" in
        base64:*) ;;
        *) export APP_KEY="base64:${APP_KEY}" ;;
    esac
fi

# A Postgres URL with no driver still means Postgres.
if [ -z "${DB_CONNECTION:-}" ] && [ -n "${DATABASE_URL:-}" ]; then
    case "$DATABASE_URL" in
        postgres:*|postgresql:*|pgsql:*)
            export DB_CONNECTION=pgsql
            ;;
    esac
fi

# Run user scripts, if they exist
scripts_dir="${FLY_SCRIPTS:-/var/www/html/.fly/scripts}"
for f in "$scripts_dir"/*.sh; do
    # Bail out this loop if any script exits with non-zero status code
    bash -e "$f" || exit 1
done

if [ "$#" -gt 0 ]; then
    # If we passed a command, run it as root
    exec "$@"
else
    exec supervisord -c /etc/supervisor/supervisord.conf
fi
