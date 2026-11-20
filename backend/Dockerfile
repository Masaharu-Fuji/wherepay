# syntax = docker/dockerfile:experimental

ARG PHP_VERSION=8.5
ARG NODE_VERSION=22
FROM ubuntu:22.04 as base
LABEL fly_launch_runtime="laravel"

# PHP_VERSION needs to be repeated here
# See https://docs.docker.com/engine/reference/builder/#understand-how-arg-and-from-interact
ARG PHP_VERSION
ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer \
    COMPOSER_MAX_PARALLEL_HTTP=24 \
    PHP_PM_MAX_CHILDREN=2 \
    PHP_PM_START_SERVERS=1 \
    PHP_MIN_SPARE_SERVERS=1 \
    PHP_MAX_SPARE_SERVERS=1 \
    PHP_DATE_TIMEZONE=UTC \
    PHP_DISPLAY_ERRORS=Off \
    PHP_ERROR_REPORTING=22527 \
    PHP_MEMORY_LIMIT=128M \
    PHP_MAX_EXECUTION_TIME=90 \
    PHP_POST_MAX_SIZE=8M \
    PHP_UPLOAD_MAX_FILE_SIZE=8M \
    PHP_ALLOW_URL_FOPEN=Off

# Prepare base container: 
# 1. Install PHP, Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY .fly/php/ondrej_ubuntu_php.gpg /etc/apt/trusted.gpg.d/ondrej_ubuntu_php.gpg
ADD .fly/php/packages/${PHP_VERSION}.txt /tmp/php-packages.txt

RUN apt-get update \
    && apt-get install -y --no-install-recommends gnupg2 ca-certificates git-core curl zip unzip \
    rsync vim-tiny htop sqlite3 nginx supervisor \
    && ln -sf /usr/bin/vim.tiny /etc/alternatives/vim \
    && ln -sf /etc/alternatives/vim /usr/bin/vim \
    && echo "deb http://ppa.launchpad.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ondrej-ubuntu-php-focal.list \
    && apt-get update \
    && apt-get -y --no-install-recommends install $(cat /tmp/php-packages.txt) \
    && ln -sf /usr/sbin/php-fpm${PHP_VERSION} /usr/sbin/php-fpm \
    && php -r 'if (version_compare(PHP_VERSION, "8.5.13", "<")) { fwrite(STDERR, "PHP ".PHP_VERSION." is older than the 2026-11-19 security release 8.5.13\n"); exit(1); }' \
    && mkdir -p /var/www/html/public && echo "index" > /var/www/html/public/index.php \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# 2. Copy config files to proper locations
COPY .fly/nginx/ /etc/nginx/
COPY .fly/fpm/ /etc/php/${PHP_VERSION}/fpm/
COPY .fly/supervisor/ /etc/supervisor/
COPY .fly/entrypoint.sh /entrypoint
COPY .fly/start-nginx.sh /usr/local/bin/start-nginx
RUN ln -sfn /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default \
    && chmod 754 /usr/local/bin/start-nginx

# 3. Copy application code, skipping files based on .dockerignore
COPY . /var/www/html
WORKDIR /var/www/html

# 4. Setup application dependencies 
# Build has no .env / DB; default CACHE_STORE=database would require database.sqlite.
RUN composer install --optimize-autoloader --no-dev \
    && mkdir -p storage/logs \
    && CACHE_STORE=array php artisan optimize:clear \
    && chown -R www-data:www-data /var/www/html \
    && if [ -d .fly ]; then cp .fly/entrypoint.sh /entrypoint; chmod +x /entrypoint; fi




# Multi-stage build: Build static assets
# This allows us to not include Node within the final container
FROM node:${NODE_VERSION} as node_modules_go_brrr

RUN mkdir /app

RUN mkdir -p  /app
WORKDIR /app
COPY . .
COPY --from=base /var/www/html/vendor /app/vendor

# Use yarn or npm depending on what type of
# lock file we might find. Defaults to
# NPM if no lock file is found.
# Note: We run "production" for Mix and "build" for Vite
RUN if [ -f "vite.config.js" ] || [ -f "vite.config.ts" ]; then \
    ASSET_CMD="build"; \
    else \
    ASSET_CMD="production"; \
    fi; \
    if [ -f "yarn.lock" ]; then \
    yarn install --frozen-lockfile; \
    yarn $ASSET_CMD; \
    elif [ -f "pnpm-lock.yaml" ]; then \
    corepack enable && corepack prepare pnpm@latest-8 --activate; \
    pnpm install --frozen-lockfile; \
    pnpm run $ASSET_CMD; \
    elif [ -f "package-lock.json" ]; then \
    npm ci --no-audit; \
    npm run $ASSET_CMD; \
    else \
    npm install; \
    npm run $ASSET_CMD; \
    fi;

# From our base container created above, we
# create our final image, adding in static
# assets that we generated above
FROM base

# Packages like Laravel Nova may have added assets to the public directory
# or maybe some custom assets were added manually! Either way, we merge
# in the assets we generated above rather than overwrite them
COPY --from=node_modules_go_brrr /app/public /var/www/html/public-npm
RUN rsync -ar /var/www/html/public-npm/ /var/www/html/public/ \
    && rm -rf /var/www/html/public-npm \
    && chown -R www-data:www-data /var/www/html

# 5. Setup Entrypoint
EXPOSE 8080

ENTRYPOINT ["/entrypoint"]
