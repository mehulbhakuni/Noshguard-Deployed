FROM php:8.1-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Enable Apache rewrite module (optional if using .htaccess)
RUN a2enmod rewrite

# Copy all project files to Apache root
COPY . /var/www/html/

EXPOSE 80
