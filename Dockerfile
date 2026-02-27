FROM dunglas/frankenphp:latest

WORKDIR /app
COPY . /app

COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 8080