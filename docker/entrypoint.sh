#!/bin/sh
set -e

if [ -z "$PORT" ]; then
    echo "Environment variable PORT not found. Using PORT 80"
    PORT=80
fi

# Update Apache to listen on the correct port when PORT != 80
if [ "$PORT" != "80" ]; then
    sed -i "s/^Listen 80$/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/" \
        /etc/apache2/sites-enabled/000-default.conf
fi

# ── Auto-initialize database on first start ──────────────────────────────────
# The SQLite file is created empty by PDO on first connect; we detect "no tables"
# to know the schema has never been loaded.
DB=/var/www/html/database/load_monitor.sqlite

HAS_TABLES=$(php -r "
try {
    \$db = new PDO('sqlite:$DB');
    echo \$db->query('SELECT COUNT(*) FROM sqlite_master WHERE type=\"table\"')->fetchColumn();
} catch (Exception \$e) { echo 0; }
")

if [ "$HAS_TABLES" = "0" ]; then
    echo "==> First start: database is empty — running schema init..."
    cd /var/www/html && php sql/init_sqlite.php
    echo "==> Seeding all 180 staff with default password (password@123)..."
    php sql/seed_staff.php
    # Ensure Apache (www-data) can read and write the database
    chown www-data:www-data "$DB"
    chmod 664 "$DB"
    echo "==> Database ready."
else
    echo "==> Database already initialized ($HAS_TABLES tables found)."
fi

exec "$@"
