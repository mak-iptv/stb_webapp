#!/bin/bash

echo "🔧 Setting up Stalker Player on Render..."

# Check PHP version
php -v

# Create directory structure
echo "📁 Creating directory structure..."
mkdir -p logs cache images api includes css js

# Set proper permissions
echo "🔒 Setting permissions..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type f -name "*.js" -exec chmod 644 {} \;
find . -type f -name "*.css" -exec chmod 644 {} \;
chmod 755 *.php *.sh

# Make directories writable
chmod 755 logs cache

# Create necessary files if they don't exist
echo "📄 Creating necessary files..."
[ -f "composer.json" ] || echo '{"require":{}}' > composer.json

echo "✅ Build completed!"
