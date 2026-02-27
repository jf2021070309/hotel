FROM dunglas/frankenphp:latest

WORKDIR /app

COPY . /app

# Configuración correcta para Railway
RUN printf ":80 {\nroot * /app\nencode gzip\nfile_server\nphp_server\n}\n" > /etc/caddy/Caddyfile

ENV CADDY_GLOBAL_OPTIONS="auto_https off"

EXPOSE 80