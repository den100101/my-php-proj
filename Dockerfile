
FROM php:8.3-apache

# Install required system packages and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    default-mysql-client \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Set the application directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . /var/www/html/

# Configure Apache to allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

# Set permissions for the web application
RUN chown -R www-data:www-data /var/www/html

# Render uses the PORT environment variable
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
