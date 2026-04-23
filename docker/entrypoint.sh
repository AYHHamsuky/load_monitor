#!/bin/sh
set -e

if [ -z "$PORT" ]; then
    echo "Environment variable PORT not found. Using PORT 80"
    PORT=80
fi

# Generate nginx config directly — no envsubst or template files needed
mkdir -p /etc/nginx/http.d

cat > /etc/nginx/http.d/default.conf << NGINX_EOF
server {
    listen ${PORT} default_server;
    server_name _;

    root /app/public;
    index index.php index.html;

    add_header X-Content-Type-Options  "nosniff"                         always;
    add_header X-Frame-Options         "SAMEORIGIN"                      always;
    add_header X-XSS-Protection        "1; mode=block"                   always;
    add_header Referrer-Policy         "strict-origin-when-cross-origin" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass         127.0.0.1:9000;
        fastcgi_index        index.php;
        include              fastcgi_params;
        fastcgi_param        SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param        PATH_INFO       \$fastcgi_path_info;
        fastcgi_buffers      16 16k;
        fastcgi_buffer_size  32k;
        fastcgi_read_timeout 60;
    }

    # Block hidden files (.git, .env, .htaccess)
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Block direct HTTP access to app/ and database/
    location ^~ /app/ { deny all; }
    location ^~ /database/ { deny all; }

    gzip on;
    gzip_types text/plain text/css application/json application/javascript;

    client_max_body_size 10M;
}
NGINX_EOF

echo "nginx config written to /etc/nginx/http.d/default.conf (port ${PORT})"

exec "$@"
