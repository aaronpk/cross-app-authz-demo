### Setup PHP server
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


### Setup web server
COPY ./docker/app.fly.nginx.conf /etc/nginx/conf.d/default.conf
COPY ./src /var/www

RUN apt update
RUN apt install nginx -y
RUN rm /etc/nginx/sites-enabled/default 
RUN ln -sf /dev/stdout /var/log/nginx/access.log && ln -sf /dev/stderr /var/log/nginx/error.log

CMD php-fpm -D && nginx -g "daemon off;"
