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

exec "$@"
