FROM php:8.2-apache

# Configure Apache to listen on port 8080 (Cloud Run default)
# Hardcoding 8080 avoids environment variable parsing issues in some Apache versions
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install required PHP extensions for the database connection (pdo_mysql)
RUN docker-php-ext-install pdo pdo_mysql

# Copy application files to the document root
COPY . /var/www/html/

# Expose the port (informative)
EXPOSE 8080