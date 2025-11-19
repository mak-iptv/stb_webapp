FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Create necessary directories before setting permissions
RUN mkdir -p logs cache images

# Copy application files
COPY . .

# Install necessary PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Set permissions (now directories exist)
RUN chmod -R 755 . && \
    chmod 777 logs cache

# Create a simple test file if index.php doesn't exist
RUN if [ ! -f "index.php" ]; then \
        echo "<?php echo '<h1>Stalker Player - Ready!</h1><p>PHP is working on Docker</p>'; ?>" > index.php; \
    fi

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
