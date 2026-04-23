FROM php:8.2-fpm-alpine

# Install nginx, supervisord, gettext (for envsubst)
RUN apk add --no-cache nginx supervisor gettext

# Enable SQLite PDO extension (bundled with PHP, just needs enabling)
RUN docker-php-ext-install pdo pdo_sqlite

# nginx config template (PORT is substituted at startup)
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template

# supervisord config to run php-fpm + nginx together
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint: handles PORT env var and generates nginx config
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copy application source
COPY . /app
WORKDIR /app

# Ensure writable directories exist and have correct ownership
RUN mkdir -p /app/logs /app/database && \
    chown -R www-data:www-data /app/logs /app/database && \
    chmod -R 775 /app/logs && \
    [ -f /app/database/load_monitor.sqlite ] && \
        chown www-data:www-data /app/database/load_monitor.sqlite && \
        chmod 664 /app/database/load_monitor.sqlite || true

# Set APP_BASE_PATH to empty so Docker deployment serves from root (fixes port-80 detection bug)
ENV APP_BASE_PATH=

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
