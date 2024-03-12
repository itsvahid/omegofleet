FROM php:8.3-fpm

RUN apt-get update \
    && apt-get install -qq -y --no-install-recommends \
    && apt-get upgrade -y \
     locales coreutils apt-utils libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev zlib1g-dev libpq-dev;


RUN docker-php-ext-install pdo pdo_pgsql pdo_mysql mysqli gd opcache intl zip calendar dom mbstring zip gd xsl
RUN docker-php-ext-configure intl

# Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .


