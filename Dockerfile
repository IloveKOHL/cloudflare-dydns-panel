FROM php:8.2-apache

# Install required PHP extensions and utilities
RUN apt-get update && \
    apt-get install -y \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        zip \
        unzip \
        git \
        curl \
        cron \
        nano \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy project files to Apache web root
COPY . /var/www/html/

# Set permissions (optional, adjust as needed)
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Add entrypoint script to start both Apache and cron
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Use the custom entrypoint script
CMD ["/docker-entrypoint.sh"]
