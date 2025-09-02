FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mysqli mbstring exif pcntl bcmath gd zip intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --ignore-platform-req=ext-intl --no-dev --optimize-autoloader --no-interaction

# Create required directories and set permissions
RUN mkdir -p storage/logs storage/cache storage/session storage/uploads \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# Copy application files (using volume mount in docker-compose)
# COPY . .

# Expose port
EXPOSE 8080

# Start CodeIgniter 4 spark serve
CMD ["php", "spark", "serve", "--host", "0.0.0.0", "--port", "8080"]