# Simulation des Produktionsservers: Ubuntu 24.04, Apache 2.4 (mpm_event, proxy_fcgi), PHP 8.4-FPM
FROM ubuntu:24.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends software-properties-common ca-certificates curl gnupg \
 && add-apt-repository -y ppa:ondrej/php \
 && apt-get update && apt-get install -y --no-install-recommends \
    apache2 php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-gd php8.4-zip php8.4-curl php8.4-intl php8.4-bcmath php8.4-opcache \
    mysql-client sudo cron rsync openssl procps unzip gnupg \
 && rm -rf /var/lib/apt/lists/*
RUN a2dismod -f mpm_prefork mpm_worker >/dev/null 2>&1 || true; a2enmod mpm_event proxy proxy_fcgi ssl headers rewrite >/dev/null \
 && a2dissite 000-default >/dev/null
COPY fixtures/systemctl /usr/local/bin/systemctl
COPY fixtures/options-ssl-apache.conf /etc/letsencrypt/options-ssl-apache.conf
COPY fixtures/old-training.conf /etc/apache2/sites-available/training.conf
COPY fixtures/wp.conf /etc/apache2/sites-available/wp-website.conf
RUN mkdir -p /etc/letsencrypt/live/example.org /var/www/wp-website /var/www/tome/api/storage/app/public/profile_images /var/www/tome/api/storage/app/public/infos /run/php \
 && openssl req -x509 -nodes -newkey rsa:2048 -days 30 -subj "/CN=training.example.org" -addext "subjectAltName=DNS:training.example.org,DNS:example.org" \
    -keyout /etc/letsencrypt/live/example.org/privkey.pem -out /etc/letsencrypt/live/example.org/fullchain.pem \
 && echo "OLD APP" > /var/www/tome/index.html && echo "WORDPRESS" > /var/www/wp-website/index.html \
 && echo "fake-image" > /var/www/tome/api/storage/app/public/profile_images/old1.png \
 && echo "<?php echo 'EXECUTED';" > /var/www/tome/api/storage/app/public/profile_images/evil.php \
 && echo "faq pdf" > /var/www/tome/api/storage/app/public/infos/regeln.pdf \
 && chmod +x /usr/local/bin/systemctl \
 && a2ensite training wp-website >/dev/null
COPY fixtures/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
EXPOSE 80 443
CMD ["/entrypoint.sh"]
