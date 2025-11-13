# Use official PHP image with Apache
FROM php:8.1-apache

# Install required PHP extensions for both MySQL and PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mysqli

# Install GD extension for QR code generation
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files to Apache document root
COPY . /var/www/html/

# Create and set permissions for upload directories
RUN mkdir -p /var/www/html/uploads /var/www/html/qr_codes \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/qr_codes \
    && chmod -R 755 /var/www/html/uploads /var/www/html/qr_codes

# Set proper permissions for web directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
