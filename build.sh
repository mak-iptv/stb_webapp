#!/bin/bash

echo "Installing PHP dependencies..."
# Install necessary PHP extensions if needed

echo "Setting up project structure..."
mkdir -p logs
mkdir -p cache

echo "Setting permissions..."
chmod -R 755 .
chmod 777 logs
chmod 777 cache

echo "Build completed successfully!"
