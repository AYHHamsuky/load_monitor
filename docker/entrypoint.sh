#!/bin/sh
set -e

# Resolve PORT — emit the same message the platform expects
if [ -z "$PORT" ]; then
    echo "Environment variable PORT not found. Using PORT 80"
    export PORT=80
else
    echo "Using PORT $PORT"
fi

# Substitute only ${PORT} in the nginx template; leave nginx's own $variables intact
envsubst '${PORT}' \
    < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/http.d/default.conf

# Remove the nginx Alpine default server block so ours is the only one
rm -f /etc/nginx/http.d/default.conf.bak

exec "$@"
