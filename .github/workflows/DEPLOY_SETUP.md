# Deployment Workflow Setup Guide

## Overview

The `backend.yml` workflow automatically deploys your Laravel 11 API to shared hosting via FTPS when code is pushed to the `main` branch.

## How It Works

1. **Laravel core files** → Deployed to `/backend/` directory
2. **Public files** → Deployed to `/public_html/` directory

This separation is common in shared hosting where the web root (`public_html`) is separate from application files.

## Required GitHub Secrets

Configure these secrets in your GitHub repository:

**Settings → Secrets and variables → Actions → New repository secret**

### Required Secrets

| Secret Name | Description | Example |
|------------|-------------|---------|
| `FTP_SERVER` | Your FTP/FTPS server hostname | `ftp.yourdomain.com` or `yourdomain.com` |
| `FTP_USERNAME` | FTP username | `your_ftp_username` |
| `FTP_PASSWORD` | FTP password | `your_secure_password` |
| `FTP_PORT` | FTP port (optional, defaults to 21) | `21` for FTP/FTPS, `990` for explicit FTPS |

## Server Directory Structure

After deployment, your server should have:

```
/
├── backend/              (Laravel core files)
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env              (Must be created manually on server)
│   └── artisan
└── public_html/          (Web-accessible files)
    ├── index.php
    ├── .htaccess
    └── assets/
```

## Important: Update public/index.php

Since Laravel core is in `/backend/` and public files are in `/public_html/`, you need to update the paths in `/public_html/index.php`:

**Original paths:**
```php
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../bootstrap/app.php';
```

**Updated paths for shared hosting:**
```php
require __DIR__.'/../backend/vendor/autoload.php';
require __DIR__.'/../backend/bootstrap/app.php';
```

And update the maintenance file path:
```php
if (file_exists($maintenance = __DIR__.'/../backend/storage/framework/maintenance.php')) {
    require $maintenance;
}
```

## Post-Deployment Checklist

After the first deployment:

1. ✅ Create `.env` file in `/backend/` on the server
2. ✅ Update `/public_html/index.php` paths (see above)
3. ✅ Set file permissions:
   ```bash
   chmod -R 775 /backend/storage
   chmod -R 775 /backend/bootstrap/cache
   ```
4. ✅ Configure database credentials in `.env`
5. ✅ Run migrations (if you have SSH access):
   ```bash
   cd /backend
   php artisan migrate --force
   ```
6. ✅ Clear and cache config (if you have SSH access):
   ```bash
   php artisan config:clear
   php artisan config:cache
   php artisan route:cache
   ```

## Testing the Deployment

1. Push to `main` branch
2. Check GitHub Actions tab for deployment status
3. Test your API endpoint:
   ```bash
   curl https://yourdomain.com/api/health
   ```

## Manual Trigger

You can manually trigger the deployment:
- Go to **Actions** tab in GitHub
- Select **Deploy Laravel API to Shared Hosting**
- Click **Run workflow** → Select `main` branch → **Run workflow**

## Troubleshooting

### Connection Failed
- Verify FTP/FTPS credentials are correct
- Check if FTPS is enabled on your hosting (try `ftp` protocol if FTPS doesn't work)
- Verify the port:
  - Port `21` for FTP/FTPS (implicit)
  - Port `990` for explicit FTPS
  - If FTPS doesn't work, try changing protocol to `ftp` in the workflow

### Files Not Deploying
- Check server directory paths exist
- Verify FTP user has write permissions
- Check GitHub Actions logs for specific errors
- If FTPS fails, try changing `protocol: ftps` to `protocol: ftp` in the workflow

### 500 Error After Deployment
- Verify `.env` file exists and is configured
- Check file permissions on `storage/` and `bootstrap/cache/`
- Verify `index.php` paths are updated correctly
- Check server error logs

## Excluded Files

The workflow automatically excludes:
- `.env` files (must be created manually on server)
- `node_modules/`
- `tests/`
- `.github/`
- Git files
- Development files

## Notes

- **No SSH required**: This workflow uses FTPS (FTP over SSL/TLS) only, perfect for shared hosting
- **Separate deployments**: Core files and public files are deployed separately
- **Production ready**: Uses `--no-dev` for Composer, excludes test files
- **Secure**: Uses FTPS protocol for encrypted file transfer (fallback to `ftp` if FTPS not supported)

