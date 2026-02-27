FROM dunglas/frankenphp:latest

WORKDIR /app

COPY . /app

RUN install-php-extensions mysqli pdo_mysql

# SERVER_NAME=":8080" → HTTP puro, sin hostname → desactiva auto-HTTPS
# DOCUMENT_ROOT=/app  → raíz en /app (no en /app/public que es el default)
ENV PORT=8080
ENV SERVER_NAME=":8080"
ENV DOCUMENT_ROOT=/app

EXPOSE 8080