FROM dunglas/frankenphp:latest

WORKDIR /app

COPY . /app

RUN install-php-extensions mysqli pdo_mysql

# FrankenPHP usará el puerto que Railway asigna automáticamente
ENV SERVER_NAME=":{$PORT}"
ENV DOCUMENT_ROOT=/app

EXPOSE 8080