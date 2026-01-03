# Laravel Shared Hosting Deployment Guide

Complete guide for deploying your Laravel backend to shared hosting with CI/CD using GitHub Actions.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Shared Hosting Requirements](#shared-hosting-requirements)
3. [Initial Setup](#initial-setup)
4. [GitHub Actions CI/CD Setup](#github-actions-cicd-setup)
5. [Deployment Configuration](#deployment-configuration)
6. [Environment Configuration](#environment-configuration)
7. [Database Setup](#database-setup)
8. [File Permissions](#file-permissions)
9. [Testing the Deployment](#testing-the-deployment)
10. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### 1. Shared Hosting Account

Ensure your hosting provider supports:
- **PHP 8.2+** (check with `php -v`)
- **Composer** (or ability to upload vendor folder)
- **MySQL/MariaDB** database
- **SSH access** (recommended for easier deployment)
- **Cron jobs** (for scheduled tasks)
- **.htaccess** support (for URL rewriting)

### 2. GitHub Repository

- Repository with `develop` and `main` branches
- GitHub Actions enabled
- SSH keys or deploy tokens configured

### 3. Local Development

- Git installed
- Composer installed
- Access to your hosting control panel

---

## Shared Hosting Requirements

### Minimum PHP Extensions Required

```php
- php8.2 (or higher)
- php8.2-cli
- php8.2-common
- php8.2-mysql
- php8.2-mbstring
- php8.2-xml
- php8.2-curl
- php8.2-zip
- php8.2-gd
- php8.2-bcmath
- php8.2-intl
- php8.2-opcache
```

### Check PHP Version and Extensions

Create a temporary `phpinfo.php` file:

```php
<?php
phpinfo();
```

Upload to your hosting and visit it in a browser to check:
- PHP version
- Loaded extensions
- `open_basedir` restrictions
- Memory limits

---

## Initial Setup

### 1. Directory Structure on Shared Hosting

Most shared hosting uses this structure:

```
public_html/          (or www/ or htdocs/)
├── api/              (Laravel public folder)
│   ├── index.php
│   ├── .htaccess
│   └── ...
├── storage/          (Laravel storage - outside public_html)
└── app/              (Laravel application - outside public_html)
```

**Recommended Structure:**

```
/home/username/
├── public_html/
│   └── api/              (Symlink to ../app/public)
├── app/                  (Laravel root)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/           (Actual Laravel public folder)
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env
│   └── artisan
└── storage/              (Symlink to app/storage)
```

### 2. Create Deployment Scripts

We'll create deployment scripts that GitHub Actions will use.

---

## GitHub Actions CI/CD Setup

### 1. Create GitHub Actions Workflows

Create `.github/workflows/` directory in your repository root.

### 2. Testing Environment Workflow (develop branch)

Create `.github/workflows/deploy-testing.yml`:

```yaml
name: Deploy to Testing Environment

on:
  push:
    branches:
      - develop

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, curl, zip, gd, bcmath, intl, mysql
      
      - name: Install dependencies
        run: |
          cd backend
          composer install --no-dev --optimize-autoloader --no-interaction
      
      - name: Create deployment package
        run: |
          cd backend
          tar -czf ../deploy-testing.tar.gz \
            --exclude='.git' \
            --exclude='.env' \
            --exclude='node_modules' \
            --exclude='tests' \
            --exclude='.phpunit.cache' \
            --exclude='storage/logs/*' \
            --exclude='storage/framework/cache/*' \
            --exclude='storage/framework/sessions/*' \
            --exclude='storage/framework/views/*' \
            .
      
      - name: Deploy to testing server
        uses: SamKirkland/FTP-Deploy-Action@v4.3.4
        with:
          server: ${{ secrets.TESTING_FTP_SERVER }}
          username: ${{ secrets.TESTING_FTP_USERNAME }}
          password: ${{ secrets.TESTING_FTP_PASSWORD }}
          local-dir: ./backend/
          server-dir: /app/
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/.env
            **/tests/**
            **/.phpunit.cache
            **/storage/logs/*
            **/storage/framework/cache/*
            **/storage/framework/sessions/*
            **/storage/framework/views/*
      
      - name: Run deployment commands via SSH
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.TESTING_SSH_HOST }}
          username: ${{ secrets.TESTING_SSH_USERNAME }}
          password: ${{ secrets.TESTING_SSH_PASSWORD }}
          script: |
            cd /home/username/app
            php artisan down
            php artisan config:clear
            php artisan cache:clear
            php artisan route:clear
            php artisan view:clear
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan up
```

### 3. Production Environment Workflow (main branch)

Create `.github/workflows/deploy-production.yml`:

```yaml
name: Deploy to Production Environment

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mbstring, xml, curl, zip, gd, bcmath, intl, mysql
      
      - name: Install dependencies
        run: |
          cd backend
          composer install --no-dev --optimize-autoloader --no-interaction
      
      - name: Deploy to production server
        uses: SamKirkland/FTP-Deploy-Action@v4.3.4
        with:
          server: ${{ secrets.PRODUCTION_FTP_SERVER }}
          username: ${{ secrets.PRODUCTION_FTP_USERNAME }}
          password: ${{ secrets.PRODUCTION_FTP_PASSWORD }}
          local-dir: ./backend/
          server-dir: /app/
          exclude: |
            **/.git*
            **/.git*/**
            **/node_modules/**
            **/.env
            **/tests/**
            **/.phpunit.cache
            **/storage/logs/*
            **/storage/framework/cache/*
            **/storage/framework/sessions/*
            **/storage/framework/views/*
      
      - name: Run deployment commands via SSH
        uses: appleboy/ssh-action@v1.0.0
        with:
          host: ${{ secrets.PRODUCTION_SSH_HOST }}
          username: ${{ secrets.PRODUCTION_SSH_USERNAME }}
          password: ${{ secrets.PRODUCTION_SSH_PASSWORD }}
          script: |
            cd /home/username/app
            php artisan down
            php artisan config:clear
            php artisan cache:clear
            php artisan route:clear
            php artisan view:clear
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            php artisan queue:restart
            php artisan up
```

### 4. Configure GitHub Secrets

Go to your GitHub repository → **Settings** → **Secrets and variables** → **Actions**

Add the following secrets:

**For Testing Environment:**
- `TESTING_FTP_SERVER` - Your FTP server (e.g., `ftp.yourhosting.com`)
- `TESTING_FTP_USERNAME` - FTP username
- `TESTING_FTP_PASSWORD` - FTP password
- `TESTING_SSH_HOST` - SSH host (if available)
- `TESTING_SSH_USERNAME` - SSH username
- `TESTING_SSH_PASSWORD` - SSH password (or use SSH key)

**For Production Environment:**
- `PRODUCTION_FTP_SERVER` - Your FTP server
- `PRODUCTION_FTP_USERNAME` - FTP username
- `PRODUCTION_FTP_PASSWORD` - FTP password
- `PRODUCTION_SSH_HOST` - SSH host
- `PRODUCTION_SSH_USERNAME` - SSH username
- `PRODUCTION_SSH_PASSWORD` - SSH password

---

## Deployment Configuration

### 1. Create Deployment Script

Create `backend/deploy.sh`:

```bash
#!/bin/bash

# Laravel Deployment Script for Shared Hosting
# Run this script on your server after deployment

echo "Starting Laravel deployment..."

# Navigate to Laravel directory
cd /home/username/app || exit

# Put application in maintenance mode
php artisan down

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Optimize application
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage bootstrap/cache
chown -R username:username storage bootstrap/cache

# Bring application back up
php artisan up

echo "Deployment completed successfully!"
```

### 2. Create .htaccess for Public Directory

Ensure `backend/public/.htaccess` exists:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 3. Update public/index.php for Shared Hosting

If your Laravel app is not in the root, update `backend/public/index.php`:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
```

---

## Environment Configuration

### 1. Create .env Files

**Testing Environment (.env.testing):**

```env
APP_NAME="SMS Testing"
APP_ENV=testing
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=true
APP_URL=https://testing-api.yourschool.com

LOG_CHANNEL=daily
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=testing_sms_db
DB_USERNAME=testing_db_user
DB_PASSWORD=testing_db_password

JWT_SECRET=your-jwt-secret-key-here
JWT_TTL=60

MAIL_MAILER=smtp
MAIL_HOST=mail.yourhosting.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourschool.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourschool.com
MAIL_FROM_NAME="${APP_NAME}"

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

**Production Environment (.env.production):**

```env
APP_NAME="School Management System"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://api.yourschool.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=production_sms_db
DB_USERNAME=production_db_user
DB_PASSWORD=production_db_password

JWT_SECRET=your-production-jwt-secret-key-here
JWT_TTL=60

MAIL_MAILER=smtp
MAIL_HOST=mail.yourhosting.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourschool.com
MAIL_PASSWORD=your-email-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourschool.com
MAIL_FROM_NAME="${APP_NAME}"

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### 2. Generate Application Key

On your server:

```bash
cd /home/username/app
php artisan key:generate
```

### 3. Generate JWT Secret

```bash
php artisan jwt:secret
```

---

## Database Setup

### 1. Create Databases

In your hosting control panel (cPanel, Plesk, etc.):

1. Create database for testing: `testing_sms_db`
2. Create database for production: `production_sms_db`
3. Create database users with appropriate permissions

### 2. Run Migrations

**Testing:**
```bash
cd /home/username/app
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
```

**Production:**
```bash
cd /home/username/app
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
```

### 3. Create Super Admin

```bash
php artisan tinker
```

Then in tinker:
```php
$user = \App\Models\User::create([
    'name' => 'Super Admin',
    'email' => 'admin@yourschool.com',
    'password' => bcrypt('secure-password'),
]);
$user->roles()->attach(\App\Models\Role::where('name', 'super_admin')->first()->id);
```

---

## File Permissions

### 1. Set Correct Permissions

```bash
# Navigate to Laravel root
cd /home/username/app

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Special permissions for storage and cache
chmod -R 775 storage bootstrap/cache
chown -R username:username storage bootstrap/cache
```

### 2. Create Storage Symlinks (if needed)

If storage needs to be outside public_html:

```bash
# Create symlink
ln -s /home/username/app/storage/app/public /home/username/public_html/storage

# Run Laravel command
php artisan storage:link
```

---

## Testing the Deployment

### 1. Test API Endpoints

```bash
# Health check
curl https://api.yourschool.com/api/health

# Test authentication
curl -X POST https://api.yourschool.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### 2. Check Logs

```bash
tail -f storage/logs/laravel.log
```

### 3. Verify Environment

```bash
php artisan env
php artisan config:show
```

---

## Troubleshooting

### Common Issues

#### 1. 500 Internal Server Error

**Check:**
- File permissions
- `.env` file exists and is configured
- `APP_KEY` is set
- PHP version and extensions
- Error logs: `storage/logs/laravel.log`

#### 2. Database Connection Error

**Check:**
- Database credentials in `.env`
- Database exists
- User has proper permissions
- Host is correct (use `127.0.0.1` instead of `localhost` if needed)

#### 3. Permission Denied

**Fix:**
```bash
chmod -R 775 storage bootstrap/cache
chown -R username:username storage bootstrap/cache
```

#### 4. Route Not Found

**Fix:**
```bash
php artisan route:clear
php artisan route:cache
php artisan config:cache
```

#### 5. Composer Autoload Issues

**Fix:**
```bash
composer dump-autoload -o
```

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Strong database passwords
- [ ] JWT secret key set
- [ ] `.env` file not accessible via web
- [ ] File permissions set correctly
- [ ] HTTPS enabled
- [ ] CORS configured properly
- [ ] Rate limiting enabled
- [ ] SQL injection protection (Laravel handles this)
- [ ] XSS protection enabled

---

## Maintenance Mode

### Enable Maintenance Mode

```bash
php artisan down
```

### Disable Maintenance Mode

```bash
php artisan up
```

### With Secret (bypass for admins)

```bash
php artisan down --secret="maintenance-bypass-token"
```

Then access: `https://api.yourschool.com/maintenance-bypass-token`

---

## Cron Jobs Setup

Add to your hosting cron jobs (via cPanel or similar):

```bash
# Run scheduler every minute
* * * * * cd /home/username/app && php artisan schedule:run >> /dev/null 2>&1

# Clean up old logs (daily)
0 0 * * * cd /home/username/app && php artisan log:clear >> /dev/null 2>&1
```

---

## Backup Strategy

### 1. Database Backups

Create `backend/scripts/backup-db.sh`:

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/username/backups"
DB_NAME="production_sms_db"
DB_USER="production_db_user"
DB_PASS="production_db_password"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "db_backup_*.sql" -mtime +7 -delete
```

Add to cron:
```bash
0 2 * * * /home/username/app/scripts/backup-db.sh
```

### 2. File Backups

```bash
tar -czf /home/username/backups/files_backup_$(date +%Y%m%d).tar.gz /home/username/app
```

---

## Monitoring

### 1. Error Log Monitoring

Set up log rotation in `config/logging.php`:

```php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => env('LOG_LEVEL', 'error'),
    'days' => 14,
],
```

### 2. Health Check Endpoint

Create `routes/api.php`:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '1.0.0',
    ]);
});
```

---

## Next Steps

1. Set up GitHub Actions workflows
2. Configure secrets in GitHub
3. Test deployment to testing environment
4. Verify all endpoints work
5. Deploy to production
6. Set up monitoring and backups
7. Configure SSL certificates
8. Set up domain DNS

---

**Last Updated**: 2025-01-20

