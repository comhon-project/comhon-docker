FROM php:7.4.2-apache

RUN apt-get update \
    && a2enmod rewrite \
    && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql    

COPY ./vendor ./vendor
COPY ./docker/assets .
COPY ./docker/public .

RUN chown www-data:www-data ./config/*
RUN chown www-data:www-data ./manifests/*

COPY ./docker/latest/start ./start
RUN chmod -R 755 ./start

ENTRYPOINT ["/var/www/html/start/script/start.sh"]