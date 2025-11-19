FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install necessary PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set permissions
RUN chmod -R 755 . && \
    chmod 777 logs cache

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
