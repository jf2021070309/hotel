FROM dunglas/frankenphp:latest

WORKDIR /app
COPY . /app

RUN printf '{\nauto_https off\n}\n:${PORT} {\nroot * /app\nphp_server\n}\n' > /etc/caddy/Caddyfile

EXPOSE 8080