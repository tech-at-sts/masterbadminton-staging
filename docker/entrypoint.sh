#!/bin/sh
set -e

# Railway (and most PaaS hosts) inject $PORT and expect the container to
# listen on it; Apache's default config only knows about port 80.
PORT="${PORT:-80}"

sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec "$@"
