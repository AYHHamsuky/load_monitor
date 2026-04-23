FROM php:8.3-apache

# pdo_mysql (production) + pdo_sqlite (local dev fallback)
RUN apt-get update && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Apache modules: rewrite, headers, expires
RUN a2enmod rewrite headers expires

# Point document root at public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
        /etc/apache2/sites-available/000-default.conf && \
    sed -ri -e 's|/var/www/html|${APACHE_DOCUMENT_ROOT}|g' \
        /etc/apache2/apache2.conf

# Allow .htaccess overrides
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copy application
COPY . /var/www/html
WORKDIR /var/www/html

# Writable dirs (logs + sqlite fallback dir)
RUN mkdir -p /var/www/html/logs /var/www/html/database && \
    chown -R www-data:www-data /var/www/html/logs /var/www/html/database && \
    chmod -R 775 /var/www/html/logs

# Default: empty base path (serves from domain root)
ENV APP_BASE_PATH=

# Default: SQLite (override with DB_DRIVER=mysql + DB_HOST etc. for production)
ENV DB_DRIVER=sqlite

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
