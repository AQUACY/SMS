# API URL Troubleshooting Guide

## Current Issue
Frontend is still using `http://localhost:8000/api` in production instead of the production API URL.

## How It Should Work

1. **GitHub Secret**: `FRONTEND_API_URL` should be set to your production API URL (e.g., `https://sms.kdgsolution.com/api`)
2. **Build Process**: The environment variable is passed to the build
3. **Quasar Config**: `rawDefine` and `extendViteConf` replace `process.env.API_URL` in the code
4. **Result**: Built files should have the production API URL hardcoded

## Verification Steps

### 1. Check GitHub Secret
- Go to: Repository → Settings → Secrets and variables → Actions
- Verify `FRONTEND_API_URL` exists and has the correct value
- Value should be: `https://sms.kdgsolution.com/api` (or your actual API URL)

### 2. Check Build Logs
After pushing, check GitHub Actions logs:
- Look for "Building with API_URL: ..." - should show your production URL
- Look for "🔧 Building with API_URL: ..." in Quasar build output
- Check if "Verify API URL in build" step passes

### 3. Check Built Files
After deployment, check the deployed JavaScript files:
- Open browser DevTools → Sources → Find a JS file (e.g., `index-*.js`)
- Search for "localhost:8000" - should NOT be found
- Search for your API URL (e.g., "sms.kdgsolution.com") - should be found

### 4. Check Network Tab
- Open browser DevTools → Network tab
- Try to login
- Check the request URL - should be your production API URL

## Common Issues

### Issue 1: Secret Not Set
**Symptom**: Build logs show default URL or localhost
**Fix**: Set `FRONTEND_API_URL` secret in GitHub

### Issue 2: Secret Value Wrong
**Symptom**: API calls go to wrong URL
**Fix**: Update `FRONTEND_API_URL` secret with correct URL

### Issue 3: Build Not Using Secret
**Symptom**: Build logs show localhost even though secret is set
**Fix**: 
- Check secret name is exactly `FRONTEND_API_URL`
- Verify workflow file uses `${{ secrets.FRONTEND_API_URL }}`
- Re-run the workflow

### Issue 4: Code Still Has Fallback
**Symptom**: Even after fix, still using localhost
**Fix**: 
- Clear browser cache
- Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
- Check if old build files are still deployed

## Manual Test

To test locally with production API URL:

```bash
cd frontend
API_URL=https://sms.kdgsolution.com/api npm run build
```

Then check `dist/spa/assets/index-*.js`:
```bash
grep -r "localhost:8000" dist/spa/
# Should return nothing

grep -r "sms.kdgsolution.com" dist/spa/
# Should find the API URL
```

## Current Configuration

- **Quasar Config**: Uses `rawDefine` and `extendViteConf` to inject API_URL
- **Workflow**: Sets `API_URL` and `VITE_API_URL` environment variables
- **Code**: Uses `process.env.API_URL` with fallback to localhost

## Next Steps

1. ✅ Verify `FRONTEND_API_URL` secret is set correctly
2. ✅ Push changes to trigger new build
3. ✅ Check build logs for API URL
4. ✅ Verify built files don't contain localhost
5. ✅ Test in browser after deployment
6. ✅ Clear browser cache if needed

## If Still Not Working

1. Check the actual built file on the server
2. Search for "localhost" in the deployed JS files
3. If found, the replacement isn't working - check Quasar/Vite version compatibility
4. Consider using a different approach (e.g., runtime configuration file)

