#!/bin/sh

chown -R www-data:www-data /app/public/cache

exec "$@"
