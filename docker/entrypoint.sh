#!/bin/sh
set -e

if [ -z "$PORT" ]; then
    echo "Environment variable PORT not found. Using PORT 80"
    PORT=80
fi

# Update Apache listen port when PORT != 80
if [ "$PORT" != "80" ]; then
    sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" \
        /etc/apache2/sites-enabled/000-default.conf
fi

# ── Database setup ────────────────────────────────────────────────────────────
DRIVER="${DB_DRIVER:-sqlite}"

if [ "$DRIVER" = "mysql" ]; then
    echo "==> MySQL mode — waiting for DB at ${DB_HOST}:${DB_PORT:-3306} ..."
    until php -r "
        \$h = getenv('DB_HOST'); \$p = getenv('DB_PORT') ?: '3306';
        \$n = getenv('DB_NAME'); \$u = getenv('DB_USER'); \$pw = getenv('DB_PASS');
        try { new PDO(\"mysql:host=\$h;port=\$p;dbname=\$n\", \$u, \$pw); echo 'ok'; }
        catch (Exception \$e) { exit(1); }
    " 2>/dev/null | grep -q ok; do
        printf '.'
        sleep 2
    done
    echo ""
    echo "==> MySQL is ready."

else
    # SQLite: auto-initialize ONLY when database file is missing.
    # We never re-seed or re-init an existing database; that would clobber
    # production data on container rebuild.
    DB=/var/www/html/database/load_monitor.sqlite

    # Make sure the dir is writable by www-data (volume may be owned by root).
    mkdir -p /var/www/html/database
    chown -R www-data:www-data /var/www/html/database
    chmod 775 /var/www/html/database

    if [ ! -f "$DB" ]; then
        echo "==> No SQLite file found — first-time init..."
        cd /var/www/html && php sql/init_sqlite.php
        echo "==> Seeding staff with password@123 ..."
        php sql/seed_staff.php
        chown www-data:www-data "$DB"
        chmod 664 "$DB"
        echo "==> SQLite database created."
    else
        SIZE=$(stat -c%s "$DB" 2>/dev/null || echo 0)
        echo "==> Existing SQLite found (${SIZE} bytes) — preserved as-is."
        chown www-data:www-data "$DB"
        chmod 664 "$DB"
    fi
fi

exec "$@"
