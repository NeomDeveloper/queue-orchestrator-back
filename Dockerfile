FROM php:8.4-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev libzip-dev curl procps supervisor \
    && docker-php-ext-install pdo pdo_mysql zip

# Create Supervisor log directory
RUN mkdir -p /var/log/supervisor

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy Laravel backend code
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Fix permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache

# Copy Supervisor queue worker config
COPY ./docker/supervisor/laravel-worker.conf /etc/supervisor/conf.d/laravel-worker.conf
COPY ./docker/supervisor/php-fpm.conf /etc/supervisor/conf.d/php-fpm.conf

# Start Supervisor instead of php-fpm directly
CMD ["/usr/bin/supervisord", "-n"]
