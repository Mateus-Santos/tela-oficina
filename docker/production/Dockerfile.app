# Laravel production: PHP base precompilado, frontend e Composer em estagios.
# A build exige PHP_BASE_IMAGE (definido pelo workflow ou build_images.sh).
ARG PHP_BASE_IMAGE
FROM node:24-bookworm-slim AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM ${PHP_BASE_IMAGE} AS php-base
FROM php-base AS composer-deps
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

FROM php-base AS runtime
WORKDIR /var/www/html
RUN groupadd --gid 1000 sos && useradd --uid 1000 --gid 1000 --create-home --shell /usr/sbin/nologin sos
COPY docker/production/php.ini /usr/local/etc/php/conf.d/99-sosmecanica.ini
COPY docker/production/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY --chown=1000:1000 . /var/www/html
COPY --from=composer-deps --chown=1000:1000 /var/www/html/vendor /var/www/html/vendor
COPY --from=frontend --chown=1000:1000 /build/public/build /var/www/html/public/build
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
    storage/framework/views storage/logs bootstrap/cache \
    && chown -R 1000:1000 storage bootstrap/cache \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage \
    && php scripts/production/verificar_imagens.php \
    && php artisan package:discover --ansi \
    && chown -R 1000:1000 bootstrap/cache
USER 1000:1000
EXPOSE 9000
CMD ["php-fpm", "-F"]
