# Vercel deployment (PHP + static assets)

This project uses a serverless PHP router (`api/index.php`) and serves static assets from the repo root.

## 1) Required Vercel setup

- Runtime: `vercel-php@0.7.3` for `api/index.php`
- Routing:
  - static files first (`handle: filesystem`)
  - app routes forwarded to `/api/index.php?route=...`

This is already configured in `vercel.json`.

## 2) Route behavior

- `/` -> `login.php`
- `/admin` -> `index.php`
- `/login`, `/signup`, `/ash`, `/user_panel` -> mapped app pages
- `/oauth_start`, `/oauth_callback` -> OAuth endpoints
- `/*.css`, `/*.js`, images/videos/html -> served as static files

## 3) Important env vars (production)

Set these in Vercel Project Settings -> Environment Variables:

- `ADMIN_USERNAME`
- `ADMIN_PASSWORD`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` (if using Google OAuth)
- `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET` (if using Facebook OAuth)
- `APPLE_CLIENT_ID`, `APPLE_CLIENT_SECRET` (if using Apple OAuth)

Optional (local-style fallback in non-local environments):

- `ALLOW_DEFAULT_ADMIN_CREDENTIALS=1`

## 4) SQLite note on Vercel

SQLite file storage is ephemeral in serverless environments. For temporary fallback, use:

- `APP_DATA_DIR=/tmp/final_pro_data`

For reliable production data, use a managed database service.

## 5) Quick validation

```bash
php -l api/index.php
php -r 'json_decode(file_get_contents("vercel.json")); echo json_last_error_msg(), PHP_EOL;'
for f in $(rg --files -g '*.php'); do php -l "$f" >/dev/null || exit 1; done
for f in $(rg --files -g '*.js'); do node --check "$f" >/dev/null || exit 1; done
```
