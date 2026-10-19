#!/bin/sh
mkdir -p /run/php /run/apache2
php-fpm8.4 -D
cron
exec apachectl -D FOREGROUND
