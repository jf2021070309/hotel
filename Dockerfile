FROM dunglas/frankenphp:latest

WORKDIR /app
COPY . /app

ENV SERVER_NAME=":8080"
ENV FRANKENPHP_CONFIG="worker /app/index.php"

EXPOSE 8080