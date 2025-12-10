# Use official PHP image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy Laravel project
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Optimize Laravel
RUN php artisan config:cache || true
RUN php artisan route:cache || true

# Expose port
EXPOSE 8000

# Start server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
