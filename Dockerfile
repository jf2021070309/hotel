FROM dunglas/frankenphp:latest

WORKDIR /app
COPY . /app

# Configuración correcta para Railway SIN HTTPS interno
RUN printf "{\nauto_https off\n}\n:8080 {\nroot * /app\nphp_server\n}\n" > /etc/caddy/Caddyfile

EXPOSE 8080