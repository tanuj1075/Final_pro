# Vercel Deployment Guide — AckerStream

## Step 1: Push to GitHub
Make sure your repo is on GitHub with no secrets in any files.

## Step 2: Import to Vercel
1. Go to https://vercel.com/new
2. Import your GitHub repo
3. Framework Preset: **Other**
4. Root Directory: leave as `/`
5. Click Deploy

## Step 3: Set Environment Variables
Go to Vercel → Project → Settings → Environment Variables and add:

| Variable | Required | Description |
|---|---|---|
| ADMIN_USERNAME | ✅ | Admin login username |
| ADMIN_PASSWORD | ✅ | Admin login password |
| GOOGLE_CLIENT_ID | ⚠️ OAuth | From Google Cloud Console |
| GOOGLE_CLIENT_SECRET | ⚠️ OAuth | From Google Cloud Console |
| CLOUDINARY_CLOUD_NAME | ⚠️ Uploads | From Cloudinary Dashboard |
| CLOUDINARY_API_KEY | ⚠️ Uploads | From Cloudinary Dashboard |
| CLOUDINARY_API_SECRET | ⚠️ Uploads | From Cloudinary Dashboard |
| APP_BASE_URL | ✅ | Your Vercel URL e.g. https://ackerstream.vercel.app |
| OAUTH_STATE_SECRET | ✅ OAuth | Any random 32+ char string |
| APP_ENV | ✅ | Set to: production |

## Step 4: Google OAuth Redirect URI
In Google Cloud Console → OAuth Client → Authorized redirect URIs, add:
```
https://your-app.vercel.app/oauth_callback?provider=google
```

## Step 5: Test These URLs After Deploy
- [ ] `https://your-app.vercel.app/` — redirects to login
- [ ] `https://your-app.vercel.app/login` — login page loads
- [ ] `https://your-app.vercel.app/signup` — signup page loads  
- [ ] `https://your-app.vercel.app/admin` — admin login loads
- [ ] `https://your-app.vercel.app/src/assets/images/bird.svg` — static asset loads
- [ ] Login → home page (ash.php) loads with anime
- [ ] Admin login → dashboard loads

## Common Issues

**PHP file downloads instead of running:**
The `vercel.json` routes are wrong. Make sure `handle: filesystem` comes AFTER static asset routes.

**Admin panel 404:**
`src/pages/admin/index.php` is missing. Create it (see TASK 3 above).

**SQLite errors on Vercel:**
Vercel filesystem is read-only. Set `APP_DATA_DIR=/tmp/ackerstream_data` in env vars.

**Cache write errors:**
Vercel filesystem is read-only. Cache must write to `/tmp/`. See Task 6c fix.

**OAuth redirect_uri mismatch:**
Make sure `APP_BASE_URL` env var matches exactly what's in Google Cloud Console.
