FROM php:8.2-apache

# Copy all your project files into the web server directory
COPY . /var/www/html/

# Update Apache configuration to use Render's dynamic port assignment
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 80
