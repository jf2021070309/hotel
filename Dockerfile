FROM dunglas/frankenphp

WORKDIR /app

COPY . /app

ENV SERVER_ROOT=/app

EXPOSE 80