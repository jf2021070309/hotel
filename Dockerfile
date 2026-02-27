FROM dunglas/frankenphp:latest

WORKDIR /app

COPY . /app

RUN printf ":{$PORT} {\nroot * /app\nphp_server\n}\n" > /etc/caddy/Caddyfile

ENV PORT=8080
ENV CADDY_GLOBAL_OPTIONS="auto_https off"

EXPOSE 8080