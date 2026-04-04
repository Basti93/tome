FROM php:8.4-fpm-alpine

# Install system dependencies and build tools
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    mysql-client \
    oniguruma-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mbstring \
    gd \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Copy startup script
COPY startup.sh /usr/local/bin/startup.sh
RUN chmod +x /usr/local/bin/startup.sh

EXPOSE 9000

CMD ["/usr/local/bin/startup.sh"]
