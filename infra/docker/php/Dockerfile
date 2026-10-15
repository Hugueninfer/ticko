# syntax=docker/dockerfile:1.7
#
# Ticko API image (PHP-FPM). Build context: repository root.
#
#   base  -> PHP 8.4 FPM + extensions, non-root user
#   dev   -> base + pcov + composer (used for tests, tooling and local dev)
#   vendor-> production composer dependencies
#   prod  -> immutable runtime image
#   spa   -> builds the web app (apps/web) with the generated API client
#   web   -> Caddy serving the web app, public/ and proxying PHP to the api service

ARG PHP_IMAGE=php:8.4-fpm-alpine3.24
ARG CADDY_IMAGE=caddy:2.11-alpine
ARG COMPOSER_IMAGE=composer:2.10
ARG NODE_IMAGE=node:22.22-alpine3.23

FROM ${COMPOSER_IMAGE} AS composer

FROM ${PHP_IMAGE} AS base

ARG UID=1000
ARG GID=1000

# Pinned extension installer (mlocati/docker-php-extension-installer 2.12.0)
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.12.0/install-php-extensions /usr/local/bin/

RUN install-php-extensions bcmath exif gd intl opcache pcntl pdo_pgsql redis zip \
    && apk add --no-cache fcgi tini \
    && addgroup -g "${GID}" ticko \
    && adduser -D -u "${UID}" -G ticko -h /home/ticko ticko \
    && mkdir -p /var/www/html \
    && chown ticko:ticko /var/www/html

COPY infra/docker/php/conf.d/ticko.ini /usr/local/etc/php/conf.d/zz-ticko.ini
COPY infra/docker/php/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY --chmod=0755 infra/docker/php/healthcheck.sh /usr/local/bin/php-fpm-healthcheck

WORKDIR /var/www/html

ENTRYPOINT ["/sbin/tini", "--"]
CMD ["php-fpm", "--nodaemonize"]

# ---------------------------------------------------------------------------
FROM base AS dev

RUN install-php-extensions pcov \
    && apk add --no-cache git unzip
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY infra/docker/php/conf.d/dev.ini /usr/local/etc/php/conf.d/zz-ticko-dev.ini

USER ticko
ENV COMPOSER_HOME=/home/ticko/.composer

# ---------------------------------------------------------------------------
FROM dev AS vendor

COPY --chown=ticko:ticko apps/api/composer.json apps/api/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress
COPY --chown=ticko:ticko apps/api/ ./
# Dev-only files (tests, Scribe config) are not shipped.
RUN rm -rf tests .env .env.* storage/logs/* bootstrap/cache/*.php config/scribe.php \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction

# ---------------------------------------------------------------------------
FROM base AS prod

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

COPY --from=vendor --chown=root:root /var/www/html /var/www/html
# Only storage/ and bootstrap/cache/ are writable by the runtime user.
RUN chown -R ticko:ticko storage bootstrap/cache \
    && chmod -R u=rwX,g=rX,o= storage bootstrap/cache

USER ticko

HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD ["php-fpm-healthcheck"]

# ---------------------------------------------------------------------------
FROM ${NODE_IMAGE} AS spa

WORKDIR /app
RUN corepack enable
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
COPY apps/web/package.json apps/web/
COPY apps/mobile/package.json apps/mobile/
COPY packages/api-client/package.json packages/api-client/
RUN pnpm install --frozen-lockfile --ignore-scripts --filter "@ticko/web..."
COPY apps/api/resources/openapi/openapi.json apps/api/resources/openapi/
COPY packages/api-client packages/api-client
COPY apps/web apps/web
RUN pnpm --filter @ticko/api-client generate && pnpm --filter @ticko/web build

# ---------------------------------------------------------------------------
FROM ${CADDY_IMAGE} AS web

COPY infra/docker/caddy/Caddyfile /etc/caddy/Caddyfile
COPY --from=vendor /var/www/html/public /var/www/html/public
COPY --from=spa /app/apps/web/dist /var/www/spa

# Port 8080 needs no privileges: drop the binary's file capability so it can
# run with all capabilities removed and no-new-privileges.
RUN apk add --no-cache libcap \
    && setcap -r /usr/bin/caddy \
    && apk del libcap \
    && addgroup -S caddy && adduser -S -G caddy caddy \
    && chown -R caddy:caddy /data /config
USER caddy

EXPOSE 8080
