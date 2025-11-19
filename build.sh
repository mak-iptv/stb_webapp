#!/bin/bash

echo "=== Starting Build Process ==="

# Create necessary directories
echo "Creating directories..."
mkdir -p logs
mkdir -p cache
mkdir -p images

# Set permissions
echo "Setting permissions..."
chmod 755 .
chmod 755 *.php
chmod -R 755 css/ js/ includes/ api/

# For writable directories
chmod 777 logs
chmod 777 cache

# Create default images if they don't exist
if [ ! -f "images/default-logo.png" ]; then
    echo "Creating placeholder images..."
    # Create a simple placeholder image using base64 (very small transparent PNG)
    echo "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=" | base64 -d > images/default-logo.png 2>/dev/null || true
fi

echo "=== Build Completed Successfully ==="
