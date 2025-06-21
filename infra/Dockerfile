FROM php:8.4-apache

ARG USERNAME=docker
ARG USER_UID=1000
ARG USER_GID=1000

RUN apt-get update && apt-get install -y git zip \
    && apt-get install -y libyaml-dev \
    && pecl install yaml && docker-php-ext-enable yaml

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html