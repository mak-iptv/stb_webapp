FROM php:8.1-apache

WORKDIR /var/www/html

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Create necessary directories
RUN mkdir -p logs cache images css js api includes

# Copy application files
COPY . .

# Set proper permissions
RUN chmod -R 755 . && \
    chmod 777 logs cache

# Apache configuration for Render
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Use PORT environment variable
RUN echo 'Listen ${PORT}' > /etc/apache2/ports.conf
RUN echo '<VirtualHost *:${PORT}>\n\
    DocumentRoot /var/www/html\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
    <Directory "/var/www/html">\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:${PORT}/ || exit 1

EXPOSE 10000

# Use shell to expand PORT variable
CMD ["sh", "-c", "sed -i 's/\\${PORT}/'\"$PORT\"'/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
