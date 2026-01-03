# Laravel API Deployment Guide for Hostinger Shared Hosting

Complete step-by-step guide for deploying your Laravel backend API to Hostinger shared hosting.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Hostinger Account Setup](#hostinger-account-setup)
3. [Directory Structure on Hostinger](#directory-structure-on-hostinger)
4. [Initial Server Setup](#initial-server-setup)
5. [Uploading Your Laravel Application](#uploading-your-laravel-application)
6. [Database Configuration](#database-configuration)
7. [Environment Configuration](#environment-configuration)
8. [File Permissions](#file-permissions)
9. [Domain and Subdomain Setup](#domain-and-subdomain-setup)
10. [Testing Your API](#testing-your-api)
11. [GitHub Actions CI/CD Setup](#github-actions-cicd-setup)
12. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- Hostinger shared hosting account (Business or higher recommended)
- Domain name (or subdomain)
- Access to Hostinger hPanel
- FTP/SFTP credentials
- SSH access (if available on your plan)
- GitHub repository with your Laravel code

---

## Hostinger Account Setup

### 1. Check Your Hosting Plan

Hostinger shared hosting typically includes:
- **PHP 8.2+** support
- **MySQL/MariaDB** database
- **FTP/SFTP** access
- **SSH** access (on Business plans and above)
- **Cron jobs** support
- **.htaccess** support

### 2. Access hPanel

1. Log in to your Hostinger account
2. Navigate to **hPanel** (hosting control panel)
3. Select your hosting account

---

## Directory Structure on Hostinger

Hostinger uses this structure:

```
/home/username/
├── public_html/          (Web root - accessible via domain)
│   └── api/              (Your Laravel public folder)
│       ├── index.php
│       ├── .htaccess
│       └── ...
├── domains/              (Alternative location)
└── [other folders]
```

**Recommended Structure for Laravel:**

```
/home/username/
├── public_html/
│   └── api/              (Symlink to ../laravel-app/public)
├── laravel-app/          (Laravel root - outside public_html)
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
└── storage/              (Symlink to laravel-app/storage)
```

---

## Initial Server Setup

### Step 1: Check PHP Version

1. In hPanel, go to **Advanced** → **PHP Configuration**
2. Select **PHP 8.2** or **PHP 8.3** (recommended)
3. Enable required extensions:
   - `mbstring`
   - `xml`
   - `curl`
   - `zip`
   - `gd`
   - `bcmath`
   - `intl`
   - `pdo_mysql`
   - `mysqli`

### Step 2: Create Database

1. In hPanel, go to **Databases** → **MySQL Databases**
2. Click **Create New Database**
3. Enter database name: `sms_db` (or your preferred name)
4. Click **Create**
5. Create a database user:
   - Go to **MySQL Users**
   - Click **Create New User**
   - Enter username and strong password
   - Click **Create**
6. Add user to database:
   - Go to **Add User to Database**
   - Select your user and database
   - Grant **ALL PRIVILEGES**
   - Click **Add**

**Note down:**
- Database name
- Database username
- Database password
- Database host (usually `localhost`)

### Step 3: Enable SSH Access (if available)

1. In hPanel, go to **Advanced** → **SSH Access**
2. If SSH is not enabled, enable it
3. Generate SSH key or set password
4. Note your SSH credentials

---

## Uploading Your Laravel Application

### Method 1: Using File Manager (Recommended for First Time)

1. **Prepare your Laravel files locally:**

```bash
# On your local machine
cd backend

# Remove unnecessary files
rm -rf node_modules
rm -rf tests
rm -rf .git
rm -rf storage/logs/*
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# Create a zip file
cd ..
zip -r laravel-app.zip backend/ -x "backend/.git/*" "backend/node_modules/*" "backend/tests/*" "backend/.env*"
```

2. **Upload via File Manager:**
   - In hPanel, go to **Files** → **File Manager**
   - Navigate to `/home/username/` (root directory)
   - Click **Upload**
   - Upload `laravel-app.zip`
   - Right-click the zip file → **Extract**
   - Rename extracted folder to `laravel-app`

### Method 2: Using FTP/SFTP

1. **Get FTP credentials:**
   - In hPanel, go to **Files** → **FTP Accounts**
   - Note your FTP host, username, and password

2. **Connect using FTP client (FileZilla, WinSCP, etc.):**
   - Host: `ftp.yourdomain.com` or IP address
   - Username: Your FTP username
   - Password: Your FTP password
   - Port: `21` (FTP) or `22` (SFTP)

3. **Upload files:**
   - Navigate to `/home/username/`
   - Create folder `laravel-app`
   - Upload all Laravel files to this folder

### Method 3: Using Git (if SSH available)

1. **Connect via SSH:**
```bash
ssh username@yourdomain.com
```

2. **Clone your repository:**
```bash
cd /home/username/
git clone https://github.com/yourusername/your-repo.git laravel-app
cd laravel-app/backend
```

3. **Install dependencies:**
```bash
composer install --no-dev --optimize-autoloader
```

---

## Database Configuration

### Step 1: Import Database Schema

**Option A: Using phpMyAdmin**

1. In hPanel, go to **Databases** → **phpMyAdmin**
2. Select your database
3. Click **Import**
4. Upload your database SQL file or run migrations via SSH

**Option B: Using SSH (Recommended)**

```bash
# Connect via SSH
ssh username@yourdomain.com

# Navigate to Laravel directory
cd /home/username/laravel-app/backend

# Run migrations
php artisan migrate --force

# Seed roles
php artisan db:seed --class=RoleSeeder
```

---

## Environment Configuration

### Step 1: Create .env File

1. **Via File Manager:**
   - Navigate to `/home/username/laravel-app/backend/`
   - Create new file `.env`
   - Copy content from `.env.example` and modify

2. **Via SSH:**
```bash
cd /home/username/laravel-app/backend
cp .env.example .env
nano .env
```

### Step 2: Configure .env File

Edit `.env` with your Hostinger settings:

```env
APP_NAME="School Management System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

JWT_SECRET=
JWT_TTL=60

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database

# File storage
FILESYSTEM_DISK=local
```

### Step 3: Generate Application Key

```bash
cd /home/username/laravel-app/backend
php artisan key:generate
```

### Step 4: Generate JWT Secret

```bash
php artisan jwt:secret
```

---

## File Permissions

### Set Correct Permissions

```bash
# Connect via SSH
ssh username@yourdomain.com

# Navigate to Laravel directory
cd /home/username/laravel-app/backend

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Special permissions for storage and cache
chmod -R 775 storage bootstrap/cache
chown -R username:username storage bootstrap/cache
```

**If SSH is not available, use File Manager:**
1. Right-click `storage` folder → **Change Permissions** → Set to `775`
2. Right-click `bootstrap/cache` folder → **Change Permissions** → Set to `775`

---

## Domain and Subdomain Setup

### Option 1: Using Subdomain (Recommended)

1. **Create Subdomain:**
   - In hPanel, go to **Domains** → **Subdomains**
   - Create subdomain: `api.yourdomain.com`
   - Document root: `/home/username/laravel-app/backend/public`

2. **Update .env:**
```env
APP_URL=https://api.yourdomain.com
```

### Option 2: Using Subdirectory

1. **Create Symlink:**
```bash
# Connect via SSH
ssh username@yourdomain.com

# Create symlink from public_html/api to Laravel public folder
ln -s /home/username/laravel-app/backend/public /home/username/public_html/api
```

2. **Update .env:**
```env
APP_URL=https://yourdomain.com/api
```

### Step 3: Update public/index.php (if needed)

If your Laravel app is in a subdirectory, ensure `public/index.php` has correct paths:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
```

### Step 4: Create/Update .htaccess

Ensure `public/.htaccess` exists:

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

---

## Testing Your API

### Step 1: Clear Caches

```bash
cd /home/username/laravel-app/backend
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
```

### Step 2: Test API Endpoint

```bash
# Health check
curl https://api.yourdomain.com/api/health

# Or test in browser
# Visit: https://api.yourdomain.com/api/health
```

### Step 3: Test Authentication

```bash
curl -X POST https://api.yourdomain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

### Step 4: Check Logs

```bash
tail -f /home/username/laravel-app/backend/storage/logs/laravel.log
```

---

## GitHub Actions CI/CD Setup

### Step 1: Get Hostinger FTP Credentials

1. In hPanel, go to **Files** → **FTP Accounts**
2. Note:
   - FTP Server/Host
   - FTP Username
   - FTP Password
   - Port (usually 21)

### Step 2: Configure GitHub Secrets

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Click **New repository secret**

**Add these secrets for Testing (develop branch):**

- `TESTING_FTP_SERVER` - Your FTP host (e.g., `ftp.yourdomain.com`)
- `TESTING_FTP_USERNAME` - FTP username
- `TESTING_FTP_PASSWORD` - FTP password
- `TESTING_SERVER_DIR` - `/laravel-app/backend/` (path on server)
- `TESTING_SSH_HOST` - Your domain (if SSH available)
- `TESTING_SSH_USERNAME` - SSH username
- `TESTING_SSH_PASSWORD` - SSH password (or use key)
- `TESTING_APP_DIR` - `/home/username/laravel-app/backend`

**Add these secrets for Production (main branch):**

- `PRODUCTION_FTP_SERVER` - Your FTP host
- `PRODUCTION_FTP_USERNAME` - FTP username
- `PRODUCTION_FTP_PASSWORD` - FTP password
- `PRODUCTION_SERVER_DIR` - `/laravel-app/backend/`
- `PRODUCTION_SSH_HOST` - Your domain
- `PRODUCTION_SSH_USERNAME` - SSH username
- `PRODUCTION_SSH_PASSWORD` - SSH password
- `PRODUCTION_APP_DIR` - `/home/username/laravel-app/backend`

### Step 3: GitHub Actions Workflows

The workflows are already created in `.github/workflows/`. They will:

- **develop branch** → Deploy to testing environment
- **main branch** → Deploy to production environment

### Step 4: Test Deployment

1. Push to `develop` branch:
```bash
git checkout develop
git add .
git commit -m "Test deployment"
git push origin develop
```

2. Check GitHub Actions:
   - Go to **Actions** tab in your repository
   - Watch the deployment workflow run

---

## Cron Jobs Setup

### Step 1: Access Cron Jobs

1. In hPanel, go to **Advanced** → **Cron Jobs**
2. Click **Create Cron Job**

### Step 2: Add Laravel Scheduler

**Cron Job 1: Laravel Scheduler**
- **Minute:** `*`
- **Hour:** `*`
- **Day:** `*`
- **Month:** `*`
- **Weekday:** `*`
- **Command:**
```bash
cd /home/username/laravel-app/backend && php artisan schedule:run >> /dev/null 2>&1
```

**Cron Job 2: Queue Worker (if using queues)**
- **Minute:** `*`
- **Hour:** `*`
- **Day:** `*`
- **Month:** `*`
- **Weekday:** `*`
- **Command:**
```bash
cd /home/username/laravel-app/backend && php artisan queue:work --tries=3 --timeout=90 >> /dev/null 2>&1
```

---

## Troubleshooting

### Issue 1: 500 Internal Server Error

**Solutions:**
1. Check file permissions:
```bash
chmod -R 775 storage bootstrap/cache
```

2. Check `.env` file exists and is configured
3. Check `APP_KEY` is set:
```bash
php artisan key:generate
```

4. Check error logs:
```bash
tail -f storage/logs/laravel.log
```

5. Check PHP version in hPanel → PHP Configuration

### Issue 2: Database Connection Error

**Solutions:**
1. Verify database credentials in `.env`
2. Use `localhost` as DB_HOST (not 127.0.0.1)
3. Check database user has proper permissions
4. Verify database exists in phpMyAdmin

### Issue 3: Route Not Found (404)

**Solutions:**
1. Clear route cache:
```bash
php artisan route:clear
php artisan route:cache
```

2. Check `.htaccess` file exists in `public/` folder
3. Verify mod_rewrite is enabled (contact Hostinger support)
4. Check `APP_URL` in `.env` matches your domain

### Issue 4: Permission Denied

**Solutions:**
1. Set correct permissions:
```bash
chmod -R 775 storage bootstrap/cache
chown -R username:username storage bootstrap/cache
```

2. Check folder ownership via File Manager

### Issue 5: Composer Autoload Issues

**Solutions:**
```bash
composer dump-autoload -o
```

### Issue 6: Storage Link Not Working

**Solutions:**
```bash
php artisan storage:link
```

If symlinks don't work on Hostinger, you may need to:
1. Copy `storage/app/public` to `public/storage`
2. Or use a different storage configuration

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] Strong database passwords
- [ ] JWT secret key set
- [ ] `.env` file not accessible via web (check with browser)
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] Storage and cache folders have 775 permissions
- [ ] HTTPS enabled (SSL certificate installed)
- [ ] CORS configured properly in `config/cors.php`
- [ ] Rate limiting enabled
- [ ] `.env` file is in `.gitignore`

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

### With Secret (bypass for testing)

```bash
php artisan down --secret="bypass-token-123"
```

Then access: `https://api.yourdomain.com/bypass-token-123`

---

## Backup Strategy

### 1. Database Backup via phpMyAdmin

1. Go to **Databases** → **phpMyAdmin**
2. Select your database
3. Click **Export**
4. Choose **Quick** or **Custom** method
5. Click **Go** to download SQL file

### 2. Automated Database Backup

Create cron job in hPanel:

```bash
# Daily backup at 2 AM
0 2 * * * mysqldump -u your_db_user -p'your_db_password' your_db_name > /home/username/backups/db_$(date +\%Y\%m\%d).sql
```

### 3. File Backup

Create cron job:

```bash
# Weekly backup on Sunday at 3 AM
0 3 * * 0 tar -czf /home/username/backups/files_$(date +\%Y\%m\%d).tar.gz /home/username/laravel-app
```

---

## Quick Reference Commands

```bash
# Connect via SSH
ssh username@yourdomain.com

# Navigate to Laravel
cd /home/username/laravel-app/backend

# Clear all caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear

# Optimize
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Run migrations
php artisan migrate --force

# Check logs
tail -f storage/logs/laravel.log

# Maintenance mode
php artisan down
php artisan up
```

---

## Next Steps

1. ✅ Complete initial setup
2. ✅ Configure database
3. ✅ Set up environment variables
4. ✅ Configure domain/subdomain
5. ✅ Test API endpoints
6. ✅ Set up GitHub Actions
7. ✅ Configure cron jobs
8. ✅ Set up backups
9. ✅ Enable SSL certificate
10. ✅ Test CI/CD deployment

---

## Hostinger Support

If you encounter issues:
- **Live Chat:** Available in hPanel
- **Knowledge Base:** https://support.hostinger.com
- **Email Support:** support@hostinger.com

---

**Last Updated:** 2025-01-20

