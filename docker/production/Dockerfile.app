# SOS Mecânica — imagem de aplicação, sem Sail, segredos ou código montado do host.
# Build: docker build -f docker/production/Dockerfile.app -t sosmecanica-app:<commit> .
FROM node:24-bookworm-slim AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.5-fpm-bookworm AS php-base
# Limita compilacao para evitar varios processos pesados de CC/LD simultaneos.
ARG PHP_BUILD_JOBS=1
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libicu-dev \
        libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"${PHP_BUILD_JOBS}" \
        bcmath exif gd intl pcntl pdo_mysql soap zip \
    && rm -rf /var/lib/apt/lists/*
# Manter bibliotecas de desenvolvimento neste primeiro build reduz risco de remover
# bibliotecas compartilhadas necessárias; pode ser otimizado depois da homologação.

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
    && php artisan package:discover --ansi \
    && chown -R 1000:1000 bootstrap/cache
USER 1000:1000
EXPOSE 9000
CMD ["php-fpm", "-F"]
