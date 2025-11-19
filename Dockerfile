FROM php:8.1-apache

WORKDIR /var/www/html

# Enable Apache rewrite module
RUN a2enmod rewrite
RUN a2enmod headers

# Create necessary directories
RUN mkdir -p logs cache images css js api includes

# Copy application files
COPY . .

# Set proper permissions
RUN chmod -R 755 . && \
    chmod 777 logs cache

# Configure Apache to use port 10000 (Render's expected port)
RUN echo "Listen 10000" > /etc/apache2/ports.conf

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

# Start Apache
CMD ["apache2-foreground"]
