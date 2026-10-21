# PHP dependencies
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi

# Frontend assets (the bundle imports Livewire from vendor/)
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# Runtime: FrankenPHP serves /app/public with PHP built in
FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions intl opcache pcntl zip

ARG USER=panel
RUN useradd --create-home ${USER} \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp

WORKDIR /app
COPY --from=vendor --chown=${USER}:${USER} /app /app
COPY --from=assets --chown=${USER}:${USER} /app/public/build /app/public/build
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-panel.ini
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/panel-entrypoint

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chown -R ${USER}:${USER} /data/caddy /config/caddy

ENV SERVER_NAME=:80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

USER ${USER}
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s \
    CMD php -r "exit(@file_get_contents('http://127.0.0.1/up') === false ? 1 : 0);"

ENTRYPOINT ["panel-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
