# Use an official PHP image with Apache
FROM php:8.2-apache

# Install MySQL extension for PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy your PHP application files into the container
COPY . /var/www/html/

# Expose port 80 for Apache
EXPOSE 80