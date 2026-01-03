# Deployment Documentation Overview

This repository contains comprehensive guides for deploying the Laravel backend API to shared hosting, with specific instructions for Hostinger.

## Documentation Files

### 🚀 Quick Start
- **[HOSTINGER_QUICK_START.md](./HOSTINGER_QUICK_START.md)** - Quick checklist and reference for Hostinger deployment (start here!)

### 📚 Detailed Guides
- **[HOSTINGER_DEPLOYMENT_GUIDE.md](./HOSTINGER_DEPLOYMENT_GUIDE.md)** - Complete step-by-step guide for Hostinger shared hosting
- **[SHARED_HOSTING_DEPLOYMENT.md](./SHARED_HOSTING_DEPLOYMENT.md)** - General shared hosting deployment guide (works for any provider)

### 🔧 CI/CD Configuration
- **[.github/workflows/deploy-testing.yml](./.github/workflows/deploy-testing.yml)** - GitHub Actions workflow for `develop` branch → testing environment
- **[.github/workflows/deploy-production.yml](./.github/workflows/deploy-production.yml)** - GitHub Actions workflow for `main` branch → production environment

### 🛠️ Deployment Scripts
- **[backend/scripts/setup-hostinger.sh](./backend/scripts/setup-hostinger.sh)** - Initial setup script (run once)
- **[backend/scripts/deploy-hostinger.sh](./backend/scripts/deploy-hostinger.sh)** - Deployment script (run after each deployment)
- **[backend/scripts/README.md](./backend/scripts/README.md)** - Scripts documentation

## Quick Navigation

### For Hostinger Users
1. **First Time Setup:** Follow [HOSTINGER_QUICK_START.md](./HOSTINGER_QUICK_START.md)
2. **Detailed Instructions:** See [HOSTINGER_DEPLOYMENT_GUIDE.md](./HOSTINGER_DEPLOYMENT_GUIDE.md)
3. **CI/CD Setup:** Configure GitHub Secrets (see guide) and workflows will auto-deploy

### For Other Shared Hosting Providers
1. **General Guide:** Follow [SHARED_HOSTING_DEPLOYMENT.md](./SHARED_HOSTING_DEPLOYMENT.md)
2. **Adapt scripts:** Modify paths in deployment scripts to match your hosting structure

## Deployment Workflow

### Manual Deployment
```bash
# 1. Upload files to server
# 2. Run setup script (first time only)
cd /home/username/laravel-app/backend
./scripts/setup-hostinger.sh

# 3. Run deployment script (after each update)
./scripts/deploy-hostinger.sh
```

### Automated Deployment (CI/CD)
1. **Push to `develop` branch** → Auto-deploys to testing environment
2. **Push to `main` branch** → Auto-deploys to production environment

## GitHub Secrets Required

### Testing Environment (develop branch)
- `TESTING_FTP_SERVER`
- `TESTING_FTP_USERNAME`
- `TESTING_FTP_PASSWORD`
- `TESTING_SERVER_DIR` (default: `/laravel-app/backend/`)
- `TESTING_SSH_HOST` (optional)
- `TESTING_SSH_USERNAME` (optional)
- `TESTING_SSH_PASSWORD` (optional)
- `TESTING_APP_DIR` (default: `/home/username/laravel-app/backend`)

### Production Environment (main branch)
- `PRODUCTION_FTP_SERVER`
- `PRODUCTION_FTP_USERNAME`
- `PRODUCTION_FTP_PASSWORD`
- `PRODUCTION_SERVER_DIR` (default: `/laravel-app/backend/`)
- `PRODUCTION_SSH_HOST` (optional)
- `PRODUCTION_SSH_USERNAME` (optional)
- `PRODUCTION_SSH_PASSWORD` (optional)
- `PRODUCTION_APP_DIR` (default: `/home/username/laravel-app/backend`)

## Directory Structure on Server

```
/home/username/
├── public_html/
│   └── api/              (Symlink to ../laravel-app/backend/public)
└── laravel-app/
    └── backend/          (Laravel root)
        ├── app/
        ├── config/
        ├── database/
        ├── public/
        ├── routes/
        ├── storage/
        ├── vendor/
        ├── .env
        └── artisan
```

## Common Tasks

### Clear Caches
```bash
php artisan config:clear && php artisan cache:clear && php artisan route:clear
```

### Run Migrations
```bash
php artisan migrate --force
```

### Maintenance Mode
```bash
php artisan down    # Enable
php artisan up      # Disable
```

### Check Logs
```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### 500 Internal Server Error
- Check file permissions: `chmod -R 775 storage bootstrap/cache`
- Verify `.env` file exists and is configured
- Check `APP_KEY` is set: `php artisan key:generate`
- Review logs: `tail -f storage/logs/laravel.log`

### 404 Not Found
- Clear route cache: `php artisan route:clear && php artisan route:cache`
- Verify `.htaccess` exists in `public/` folder
- Check `APP_URL` in `.env` matches your domain

### Database Connection Error
- Verify database credentials in `.env`
- Use `localhost` as `DB_HOST` (not `127.0.0.1`)
- Check database user has proper permissions

## Support

- **Hostinger Support:** Live chat in hPanel or support@hostinger.com
- **Laravel Documentation:** https://laravel.com/docs
- **GitHub Actions Docs:** https://docs.github.com/en/actions

## Next Steps

1. ✅ Choose your hosting provider
2. ✅ Follow the appropriate deployment guide
3. ✅ Set up GitHub Actions secrets
4. ✅ Test manual deployment
5. ✅ Test automated deployment via CI/CD
6. ✅ Configure cron jobs
7. ✅ Set up backups
8. ✅ Enable SSL certificate

---

**Last Updated:** 2025-01-20

