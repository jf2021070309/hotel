FROM dunglas/frankenphp:latest

WORKDIR /app
COPY . /app

RUN echo ':8080 {
    root * /app
    php_server
}' > /etc/caddy/Caddyfile

EXPOSE 8080