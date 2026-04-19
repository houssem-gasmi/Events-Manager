FROM php:8.4-cli

ENV APP_ENV=prod \
    APP_DEBUG=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libpq-dev libxml2-dev \
    && docker-php-ext-install intl pdo pdo_mysql pdo_pgsql zip opcache dom xml \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

COPY . .

EXPOSE 10000

CMD sh -c "php bin/console doctrine:schema:update --force --complete --no-interaction && php -d display_errors=0 -d variables_order=EGPCS -S 0.0.0.0:${PORT:-10000} -t public public/index.php"
