# Use official PHP image with Apache web server
FROM php:8.2-apache

# Install PostgreSQL extension dependencies required for PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Change Apache's default port from 80 to 10000 (Render's required port)
RUN sed -i 's/Listen 80/Listen 10000/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:10000>/g' /etc/apache2/sites-available/000-default.conf

# 🛠️ FIX: Directs Apache to treat login.html as the root homepage
RUN echo "DirectoryIndex login.html" >> /etc/apache2/apache2.conf

# Enable Apache rewrite module for clean routing
RUN a2enmod rewrite

# Copy all your project files into the Apache web directory
COPY . /var/www/html/

# Ensure proper permissions for Apache web server files
RUN chown -R www-data:www-data /var/www/html/

# Inform Docker that the container listens on port 10000
EXPOSE 10000
