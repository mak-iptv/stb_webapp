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

# Configure Apache for Render
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Use PORT environment variable
RUN echo "Listen ${PORT}" > /etc/apache2/ports.conf

EXPOSE 10000

# Start Apache
CMD ["sh", "-c", "sed -i 's/\\${PORT}/'\"$PORT\"'/g' /etc/apache2/ports.conf && apache2-foreground"]
