#!/bin/bash

# Laravel Deployment Script for Hostinger Shared Hosting
# Run this script on your Hostinger server after deployment

echo "========================================="
echo "Laravel Deployment Script for Hostinger"
echo "========================================="

# Configuration - Update these paths
APP_DIR="/home/username/laravel-app/backend"
PHP_BIN="php"

# Navigate to Laravel directory
cd "$APP_DIR" || exit 1

echo "Current directory: $(pwd)"

# Put application in maintenance mode
echo "Putting application in maintenance mode..."
$PHP_BIN artisan down || echo "Warning: Could not enable maintenance mode"

# Clear all caches
echo "Clearing caches..."
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear

# Run migrations
echo "Running migrations..."
$PHP_BIN artisan migrate --force --no-interaction

# Optimize application
echo "Optimizing application..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

# Set permissions
echo "Setting permissions..."
chmod -R 755 storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Create storage link if needed
echo "Creating storage link..."
$PHP_BIN artisan storage:link || echo "Storage link already exists or not needed"

# Bring application back up
echo "Bringing application back online..."
$PHP_BIN artisan up

echo "========================================="
echo "Deployment completed successfully!"
echo "========================================="

