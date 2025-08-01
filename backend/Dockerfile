FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create required directories and set permissions
RUN mkdir -p storage/logs storage/cache storage/session storage/uploads \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# Copy application files (using volume mount in docker-compose)
# COPY . .

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]