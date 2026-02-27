FROM php:8.2-apache

WORKDIR /var/www/html
COPY . /var/www/html

RUN docker-php-ext-install mysqli pdo pdo_mysql

ENV PORT=8080
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf

EXPOSE 8080