FROM dunglas/frankenphp:latest

WORKDIR /app

# Copiar todo el proyecto
COPY . /app

# Extensiones PHP necesarias
RUN install-php-extensions mysqli pdo_mysql

# Usar nuestro Caddyfile que desactiva HTTPS (Railway maneja TLS en el edge)
COPY Caddyfile /etc/caddy/Caddyfile

ENV PORT=8080

EXPOSE 8080