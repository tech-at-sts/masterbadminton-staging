#!/bin/sh
set -e

# Railway (and most PaaS hosts) inject $PORT and expect the container to
# listen on it; Apache's default config only knows about port 80.
PORT="${PORT:-80}"

sed -ri "s/^Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Some container platforms (Railway included) can end up with both
# mpm_event and mpm_prefork enabled at runtime even when the image was
# built with only prefork enabled, which makes Apache refuse to start
# with "More than one MPM loaded." mod_php requires prefork, so force it
# again here, right before Apache actually starts.
a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

exec "$@"
