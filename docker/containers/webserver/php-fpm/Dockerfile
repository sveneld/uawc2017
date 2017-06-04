FROM php:7.1.5-fpm

WORKDIR /www

RUN apt-get update \
    && apt-get install -y \
    curl \
    libpcre3-dev \
    libssl-dev \
    libz-dev \
    mc \
    nano \
    vim \
    wget \
    zip

RUN docker-php-ext-install \
    zip

# Install lib composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer global require "hirak/prestissimo:^0.3"

#RUN apt-get update \
#    && apt-get install -y \
#    && php-sqlite3