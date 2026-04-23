FROM php:8.3-apache

# SQLite3 dev headers needed for pdo_sqlite
RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Apache modules: rewrite (URL routing), headers (security headers), expires (cache)
RUN a2enmod rewrite headers expires

# Point document root at public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
        /etc/apache2/sites-available/000-default.conf && \
    sed -ri -e 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
        /etc/apache2/apache2.conf

# Allow .htaccess overrides everywhere under /var/www
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copy application
COPY . /var/www/html
WORKDIR /var/www/html

# Writable dirs
RUN mkdir -p /var/www/html/logs /var/www/html/database && \
    chown -R www-data:www-data /var/www/html/logs /var/www/html/database && \
    chmod -R 775 /var/www/html/logs && \
    { [ -f /var/www/html/database/load_monitor.sqlite ] && \
      chown www-data:www-data /var/www/html/database/load_monitor.sqlite && \
      chmod 664 /var/www/html/database/load_monitor.sqlite; } || true

# Empty base path so bootstrap.php serves from domain root
ENV APP_BASE_PATH=

# Entrypoint handles PORT env var then starts Apache
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
