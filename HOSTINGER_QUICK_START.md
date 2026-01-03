# Hostinger Deployment Quick Start Checklist

Use this checklist for quick deployment reference.

## Pre-Deployment Checklist

- [ ] Hostinger account active
- [ ] Domain/subdomain ready
- [ ] PHP 8.2+ enabled in hPanel
- [ ] Database created in hPanel
- [ ] FTP credentials noted
- [ ] SSH access enabled (if available)

## Step-by-Step Quick Guide

### 1. Database Setup (5 minutes)

```
hPanel → Databases → MySQL Databases
→ Create Database: sms_db
→ Create User: sms_user
→ Add User to Database (ALL PRIVILEGES)
→ Note: DB name, username, password, host (localhost)
```

### 2. Upload Laravel Files (10-15 minutes)

**Option A: File Manager**
```
1. Zip backend folder locally (exclude: .git, node_modules, tests, .env)
2. hPanel → Files → File Manager
3. Upload zip to /home/username/
4. Extract → Rename to laravel-app
```

**Option B: FTP**
```
1. Connect via FileZilla/WinSCP
2. Upload backend/ to /home/username/laravel-app/backend/
```

**Option C: Git + SSH**
```bash
ssh username@yourdomain.com
cd /home/username/
git clone https://github.com/your-repo.git laravel-app
cd laravel-app/backend
composer install --no-dev --optimize-autoloader
```

### 3. Configure Environment (5 minutes)

```bash
# Via SSH or File Manager
cd /home/username/laravel-app/backend
cp .env.example .env
nano .env  # Edit with your settings
```

**Update .env:**
```env
APP_URL=https://api.yourdomain.com
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 4. Run Setup Script (2 minutes)

```bash
cd /home/username/laravel-app/backend
chmod +x scripts/setup-hostinger.sh
./scripts/setup-hostinger.sh
```

Or manually:
```bash
php artisan key:generate
php artisan jwt:secret
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
chmod -R 775 storage bootstrap/cache
```

### 5. Configure Domain/Subdomain (5 minutes)

**Option A: Subdomain (Recommended)**
```
hPanel → Domains → Subdomains
→ Create: api.yourdomain.com
→ Document Root: /home/username/laravel-app/backend/public
```

**Option B: Subdirectory**
```bash
ln -s /home/username/laravel-app/backend/public /home/username/public_html/api
```

### 6. Test API (2 minutes)

```bash
curl https://api.yourdomain.com/api/health
# Or visit in browser
```

### 7. Set Up Cron Jobs (3 minutes)

```
hPanel → Advanced → Cron Jobs
```

**Cron 1: Laravel Scheduler**
```
* * * * * cd /home/username/laravel-app/backend && php artisan schedule:run >> /dev/null 2>&1
```

**Cron 2: Queue Worker (if needed)**
```
* * * * * cd /home/username/laravel-app/backend && php artisan queue:work --tries=3 >> /dev/null 2>&1
```

### 8. Configure GitHub Actions (10 minutes)

```
GitHub Repo → Settings → Secrets and variables → Actions
```

**Add Secrets:**
- `TESTING_FTP_SERVER` = ftp.yourdomain.com
- `TESTING_FTP_USERNAME` = your_ftp_user
- `TESTING_FTP_PASSWORD` = your_ftp_pass
- `TESTING_SERVER_DIR` = /laravel-app/backend/
- `TESTING_SSH_HOST` = yourdomain.com (if SSH available)
- `TESTING_SSH_USERNAME` = your_ssh_user
- `TESTING_SSH_PASSWORD` = your_ssh_pass
- `TESTING_APP_DIR` = /home/username/laravel-app/backend

**Repeat for PRODUCTION_* secrets**

## Common Commands

```bash
# Connect via SSH
ssh username@yourdomain.com

# Navigate to app
cd /home/username/laravel-app/backend

# Clear caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear

# Optimize
php artisan config:cache && php artisan route:cache

# Maintenance mode
php artisan down
php artisan up

# Check logs
tail -f storage/logs/laravel.log

# Run migrations
php artisan migrate --force
```

## Troubleshooting Quick Fixes

**500 Error:**
```bash
chmod -R 775 storage bootstrap/cache
php artisan key:generate
php artisan config:clear
```

**404 Error:**
```bash
php artisan route:clear
php artisan route:cache
# Check .htaccess exists in public/
```

**Database Error:**
```bash
# Check .env DB credentials
# Use localhost (not 127.0.0.1)
```

## File Structure Reference

```
/home/username/
├── public_html/
│   └── api/ → symlink to ../laravel-app/backend/public
└── laravel-app/
    └── backend/
        ├── app/
        ├── config/
        ├── database/
        ├── public/
        ├── routes/
        ├── storage/
        ├── vendor/
        └── .env
```

## Total Time Estimate

- **First-time setup:** 30-45 minutes
- **Subsequent deployments:** Automatic via GitHub Actions

---

**Need help?** Check `HOSTINGER_DEPLOYMENT_GUIDE.md` for detailed instructions.

