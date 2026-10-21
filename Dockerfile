FROM php:8.3-cli-alpine

# pdo_sqlite is bundled with the image; add MySQL for APP_ENV=production
RUN docker-php-ext-install pdo_mysql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first so this layer is cached until composer files change.
# Pass --build-arg COMPOSER_FLAGS="--no-dev" for a production image.
ARG COMPOSER_FLAGS=""
COPY composer.json composer.lock ./
RUN composer install ${COMPOSER_FLAGS} --no-interaction --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize ${COMPOSER_FLAGS}

EXPOSE 8000

# The built-in server is fine for development only.
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]