FROM dunglas/frankenphp:latest

WORKDIR /app

# Copiar todo el proyecto
COPY . /app

# Extensiones PHP necesarias (FrankenPHP trae mysqli por defecto)
RUN install-php-extensions mysqli pdo_mysql

# Caddyfile: escuchar en $PORT, servir /app como raíz PHP
RUN printf '{\n  auto_https off\n  admin off\n}\n\n:{$PORT} {\n  root * /app\n  php_server\n}\n' > /etc/caddy/Caddyfile

ENV PORT=8080

EXPOSE 8080