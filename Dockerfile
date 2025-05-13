FROM php:8.1-apache

# Copy all project files into the Apache web root
COPY . /var/www/html/

# Enable Apache rewrite module (if using .htaccess)
RUN a2enmod rewrite

EXPOSE 80
