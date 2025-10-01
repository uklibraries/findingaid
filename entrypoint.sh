#!/bin/sh
set -euo
CACHE_DIR="/public/cache"

umask 002
mkdir -p "$CACHE_DIR"
chown -R www-data:www-data "$CACHE_DIR"
find "$CACHE_DIR" -type d -exec chmod 2775 "{}" \;
find "$CACHE_DIR" -type f -exec chmod 0664 "{}" \;

bash /exe/build.sh

exec "$@"
