# Use the official PHP image with Apache
FROM php:7.4-apache

# Install PDO and MySQL PDO extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable mod_rewrite for Apache (optional, for routing purposes)
RUN a2enmod rewrite

# Copy custom php.ini configuration if needed (optional)
# COPY config/php.ini /usr/local/etc/php/

# Set the working directory
WORKDIR /var/www/html

# Copy the current directory contents into the container at /var/www/html
COPY . .

# Expose port 80 (default Apache port)
EXPOSE 80
