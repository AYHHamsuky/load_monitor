FROM php:8.2-fpm-alpine

# System packages: nginx, supervisord, sqlite3 headers for pdo_sqlite
RUN apk add --no-cache nginx supervisor sqlite-dev

# Enable SQLite PDO extension (needs sqlite-dev headers)
RUN docker-php-ext-install pdo_sqlite

# supervisord config — runs php-fpm + nginx together
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint generates the nginx config at startup and starts supervisord
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Copy application source
COPY . /app
WORKDIR /app

# Ensure writable directories have correct ownership
RUN mkdir -p /app/logs /app/database && \
    chown -R www-data:www-data /app/logs /app/database && \
    chmod -R 775 /app/logs && \
    { [ -f /app/database/load_monitor.sqlite ] && \
      chown www-data:www-data /app/database/load_monitor.sqlite && \
      chmod 664 /app/database/load_monitor.sqlite; } || true

# APP_BASE_PATH= (empty) tells bootstrap.php the web root IS public/
ENV APP_BASE_PATH=

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
