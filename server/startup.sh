#!/bin/sh
set -e

# Clear cache files
rm -f /var/www/html/bootstrap/cache/*.php

# Run migrations if database is up
php /var/www/html/artisan migrate --force 2>/dev/null || true

# Start PHP-FPM
exec php-fpm
