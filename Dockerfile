FROM php:8.2-apache

# Configure Apache to listen on the $PORT environment variable (default for Cloud Run is 8080)
# This replaces 80 with the value of $PORT in both the default site configuration and ports configuration
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install required PHP extensions for the database connection (pdo_mysql)
RUN docker-php-ext-install pdo pdo_mysql

# Copy application files to the document root
COPY . /var/www/html/

# Expose the port (informative)
EXPOSE 8080