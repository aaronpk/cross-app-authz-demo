FROM composer:latest AS composer
WORKDIR /var/www

COPY src/composer.json ./
COPY src/composer.lock ./

RUN composer install --no-dev --no-scripts --no-interaction

FROM php:fpm
WORKDIR /var/www

COPY --from=composer /var/www/vendor/ vendor

RUN docker-php-ext-install pdo pdo_mysql
RUN pecl install xdebug && docker-php-ext-enable xdebug
ENV ENV .wiki.env.docker

