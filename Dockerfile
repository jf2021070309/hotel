FROM dunglas/frankenphp:latest

WORKDIR /app
COPY . /app

RUN printf ":8080 {\nroot * /app\nphp_server\n}\n" > /etc/caddy/Caddyfile

EXPOSE 8080