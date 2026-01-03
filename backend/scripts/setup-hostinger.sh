#!/bin/bash

# Initial Setup Script for Hostinger Shared Hosting
# Run this script once after first upload

echo "========================================="
echo "Laravel Hostinger Initial Setup"
echo "========================================="

# Configuration - Update these paths
APP_DIR="/home/username/laravel-app/backend"
PHP_BIN="php"

# Navigate to Laravel directory
cd "$APP_DIR" || exit 1

echo "Current directory: $(pwd)"

# Check if .env exists
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example..."
    cp .env.example .env
    echo "⚠️  Please edit .env file with your configuration!"
else
    echo ".env file already exists"
fi

# Generate application key if not set
echo "Generating application key..."
$PHP_BIN artisan key:generate --force

# Generate JWT secret if not set
echo "Generating JWT secret..."
$PHP_BIN artisan jwt:secret --force

# Set permissions
echo "Setting file permissions..."
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache

# Clear caches
echo "Clearing caches..."
$PHP_BIN artisan config:clear
$PHP_BIN artisan cache:clear
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:clear

# Run migrations
echo "Running migrations..."
read -p "Do you want to run migrations now? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    $PHP_BIN artisan migrate --force
    echo "Do you want to seed the database? (y/n) "
    read -p "" -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        $PHP_BIN artisan db:seed --class=RoleSeeder
    fi
fi

# Optimize
echo "Optimizing application..."
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache

echo "========================================="
echo "Setup completed!"
echo "========================================="
echo "Next steps:"
echo "1. Edit .env file with your database and mail settings"
echo "2. Test your API endpoints"
echo "3. Set up cron jobs in hPanel"
echo "4. Configure GitHub Actions secrets"
echo "========================================="

