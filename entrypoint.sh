#!/bin/sh
set -eu
PUBLIC_DIR="/opt/findingaid/public"
CACHE_DIR="$PUBLIC_DIR/cache"

chmod 755 "$PUBLIC_DIR"

umask 002
mkdir -p "$CACHE_DIR"
chown -R www-data:www-data "$CACHE_DIR"
find "$CACHE_DIR" -type d -exec chmod 2775 "{}" \;
find "$CACHE_DIR" -type f -exec chmod 0664 "{}" \;

# APP_ENV set in compose file
if [ "${APP_ENV:-}" = "development" ]; then
    echo "Building js for dev"
    bash /opt/findingaid/exe/build.sh
fi

exec "$@"
