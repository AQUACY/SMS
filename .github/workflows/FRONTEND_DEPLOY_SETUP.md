# Frontend Deployment Workflow Setup Guide

## Overview

The `frontend.yml` workflow automatically deploys your Quasar/Vue frontend to shared hosting via FTPS when code is pushed to the `main` branch (or when frontend files change).

## How It Works

1. **Builds the frontend** with production API URL configured
2. **Deploys built files** from `frontend/dist/spa/` to `/public_html/sms/` directory

## Required GitHub Secrets

Configure these secrets in your GitHub repository:

**Settings → Secrets and variables → Actions → New repository secret**

### Required Secrets

| Secret Name | Description | Example | Required |
|------------|-------------|---------|----------|
| `FTP_SERVER` | Your FTP/FTPS server hostname | `ftp.yourdomain.com` or `yourdomain.com` | ✅ Yes |
| `FTP_USERNAME` | FTP username | `your_ftp_username` | ✅ Yes |
| `FTP_PASSWORD` | FTP password | `your_secure_password` | ✅ Yes |
| `FRONTEND_API_URL` | Production API URL for frontend | `https://api.yourschool.com/api` | ⚠️ Recommended |
| `FTP_PORT` | FTP port (optional, defaults to 21) | `21` for FTP/FTPS, `990` for explicit FTPS | ❌ Optional |

**Note:** If `FRONTEND_API_URL` is not set, it defaults to `https://api.yourschool.com/api`. Make sure to set this to match your actual backend API URL.

## Server Directory Structure

After deployment, your server should have:

```
/public_html/sms/
├── index.html
├── .htaccess          (for Vue Router history mode)
├── assets/
│   ├── index-*.js
│   ├── index-*.css
│   └── ...
└── ...
```

## API URL Configuration

The workflow automatically configures the API URL during build by updating `.quasar.env.json`. The API URL is set from the `FRONTEND_API_URL` secret.

**In your code**, the API URL is accessed via:
```javascript
process.env.API_URL || 'http://localhost:8000/api'
```

**During build**, Quasar replaces `process.env.API_URL` with the value from `.quasar.env.json` for the production environment.

## Important: .htaccess for Vue Router

Since Vue Router uses history mode, you need a `.htaccess` file in `/public_html/sms/` to handle client-side routing:

**Create `.htaccess` file:**

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /sms/
  
  # Handle Angular and Vue Router
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /sms/index.html [L]
</IfModule>
```

**Or if your domain points directly to `/sms/`:**

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  
  # Handle Angular and Vue Router
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

## Post-Deployment Checklist

After the first deployment:

1. ✅ Verify API URL is correct:
   - Check browser console for API calls
   - Ensure API calls go to your production backend
   
2. ✅ Create `.htaccess` file (see above) if not deployed automatically

3. ✅ Test the application:
   - Visit your domain
   - Test login functionality
   - Verify API connectivity
   - Test navigation (ensure no 404 errors on refresh)

4. ✅ Check browser console for errors

5. ✅ Verify CORS is configured on backend for your frontend domain

## Testing the Deployment

1. Push to `main` branch (or push changes to `frontend/` directory)
2. Check GitHub Actions tab for deployment status
3. Visit your frontend URL in a browser
4. Open browser DevTools → Network tab
5. Verify API calls are going to the correct backend URL

## Manual Trigger

You can manually trigger the deployment:
- Go to **Actions** tab in GitHub
- Select **Deploy Frontend to Shared Hosting**
- Click **Run workflow** → Select `main` branch → **Run workflow**

## Troubleshooting

### Build Fails

**Error: API_URL not found**
- Ensure `FRONTEND_API_URL` secret is set in GitHub
- Check the workflow logs for the actual API URL being used

**Error: npm install fails**
- Check Node.js version (requires >= 18.0.0)
- Verify `package-lock.json` is committed

### Deployment Fails

**Connection Failed**
- Verify FTP/FTPS credentials are correct
- Check if FTPS is enabled on your hosting (try `ftp` protocol if FTPS doesn't work)
- Verify the port:
  - Port `21` for FTP/FTPS (implicit)
  - Port `990` for explicit FTPS
  - If FTPS doesn't work, try changing protocol to `ftp` in the workflow

**Files Not Deploying**
- Check server directory path exists (`/public_html/sms/`)
- Verify FTP user has write permissions
- Check GitHub Actions logs for specific errors

### Frontend Issues After Deployment

**404 Errors on Page Refresh**
- Ensure `.htaccess` file exists and is configured correctly
- Verify mod_rewrite is enabled on your server
- Check that the RewriteBase matches your subdirectory

**API Calls Failing**
- Verify `FRONTEND_API_URL` secret matches your backend URL
- Check browser console for CORS errors
- Ensure backend CORS is configured for your frontend domain
- Verify backend is accessible from the frontend domain

**Blank Page**
- Check browser console for JavaScript errors
- Verify all assets are loading (check Network tab)
- Ensure API URL is correct and backend is accessible

## Workflow Triggers

The workflow runs automatically when:
- Code is pushed to `main` branch AND changes are in `frontend/` directory
- The workflow file itself is changed
- Manually triggered via GitHub Actions UI

## Environment Variables

The workflow sets these environment variables during build:

- `NODE_ENV=production` - Production mode
- `QUASAR_MODE=spa` - Single Page Application mode
- `API_URL` - Set from `FRONTEND_API_URL` secret (via `.quasar.env.json`)

## Notes

- **No SSH required**: This workflow uses FTPS only, perfect for shared hosting
- **Automatic API URL**: API URL is configured automatically from GitHub secrets
- **Production build**: Uses optimized production build with minification
- **Secure**: Uses FTPS protocol for encrypted file transfer (fallback to `ftp` if FTPS not supported)
- **Smart triggers**: Only runs when frontend files change

## Related Workflows

- **Backend deployment**: See `.github/workflows/backend.yml` and `DEPLOY_SETUP.md`
- Both workflows can run independently or together

---

**Last Updated:** 2025-01-20

