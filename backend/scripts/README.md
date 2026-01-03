# Deployment Scripts

This directory contains deployment and setup scripts for different hosting environments.

## Scripts

### `setup-hostinger.sh`

Initial setup script for Hostinger shared hosting. Run this once after first upload.

**Usage:**
```bash
chmod +x scripts/setup-hostinger.sh
./scripts/setup-hostinger.sh
```

**What it does:**
- Creates `.env` file from `.env.example`
- Generates application key
- Generates JWT secret
- Sets file permissions
- Clears caches
- Optionally runs migrations and seeds

### `deploy-hostinger.sh`

Deployment script for Hostinger. Run this after each deployment.

**Usage:**
```bash
chmod +x scripts/deploy-hostinger.sh
./scripts/deploy-hostinger.sh
```

**What it does:**
- Puts application in maintenance mode
- Clears all caches
- Runs migrations
- Optimizes application (caches config, routes, views)
- Sets permissions
- Creates storage link
- Brings application back online

**Note:** Update the `APP_DIR` variable in the script to match your server path.

## Customization

Before using these scripts, update the configuration variables at the top:

```bash
APP_DIR="/home/username/laravel-app/backend"  # Your Laravel app path
PHP_BIN="php"                                  # PHP binary path
```

## Permissions

Make scripts executable:
```bash
chmod +x scripts/*.sh
```

## Integration with GitHub Actions

These scripts can be called from GitHub Actions workflows via SSH:

```yaml
- name: Run deployment script
  uses: appleboy/ssh-action@v1.0.0
  with:
    host: ${{ secrets.SSH_HOST }}
    username: ${{ secrets.SSH_USERNAME }}
    password: ${{ secrets.SSH_PASSWORD }}
    script: |
      cd /home/username/laravel-app/backend
      ./scripts/deploy-hostinger.sh
```

