FROM dunglas/frankenphp:latest

WORKDIR /app
COPY . /app

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN mv /app/Caddyfile /etc/caddy/Caddyfile

ENV SERVER_NAME=":8080"

EXPOSE 8080