# API URL Build Fix

## Problem
The frontend was still using `http://localhost:8000/api` in production because `process.env.API_URL` wasn't being replaced during the build.

## Solution
Updated `quasar.config.js` to use `rawDefine` which injects the API URL at build time.

## How It Works

1. **During build**, the workflow sets `API_URL` environment variable
2. **Quasar config** uses `rawDefine` to replace `process.env.API_URL` with the actual value
3. **All instances** of `process.env.API_URL` in the code are replaced with the production URL

## Files Changed

1. **`frontend/quasar.config.js`** - Added `rawDefine` to inject API_URL
2. **`.github/workflows/frontend.yml`** - Simplified to just set environment variables

## Testing

After deployment, verify the API URL is correct:

1. Open browser DevTools → Network tab
2. Try to login
3. Check the request URL - it should be your production API URL (e.g., `https://sms.kdgsolution.com/api/auth/login`)
4. If it still shows `localhost`, the build didn't pick up the environment variable

## Manual Build Test

To test locally with production API URL:

```bash
cd frontend
API_URL=https://sms.kdgsolution.com/api npm run build
```

Then check the built files in `dist/spa/assets/` - search for the API URL to verify it's been replaced.

## Troubleshooting

If the API URL is still wrong after deployment:

1. Check GitHub Actions logs - verify `API_URL` is set correctly
2. Check the built files - search for "localhost" in the deployed files
3. Verify `FRONTEND_API_URL` secret is set in GitHub
4. Rebuild and redeploy

