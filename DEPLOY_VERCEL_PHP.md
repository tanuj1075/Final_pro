# Vercel setup check (PHP + static links)

This repository keeps PHP pages in project root (for example `index.php`, `login.php`, `signup.php`) and static pages/assets beside them.

## 1) What is wired now

- `vercel.json` uses `handle: filesystem` so existing static files are served directly by Vercel CDN before PHP routing.
- `vercel.json` sends static assets (`.html`, `.css`, `.js`, images, fonts, video) directly via Vercel CDN.
- PHP routes are rewritten to `/api/index.php`.
- `/api/index.php` whitelists and loads root PHP files safely.

This makes existing links like `login.php`, `signup.php`, `ash.php`, `user_panel.php`, and OAuth endpoints continue to work.

## 2) Current route mapping

- `/` -> `login.php` (user login page)
- `/admin` or `/admin.php` -> `index.php` (admin login/panel)
- `/login` or `/login.php` -> `login.php`
- `/signup` or `/signup.php` -> `signup.php`
- `/ash` or `/ash.php` -> `ash.php`
- `/user_panel` or `/user_panel.php` -> `user_panel.php`
- `/oauth_start` or `/oauth_start.php` -> `oauth_start.php`
- `/oauth_callback` or `/oauth_callback.php` -> `oauth_callback.php`
- `/about` -> `about.php` (only if file exists)
- `/contact` -> `contact.php` (only if file exists)

## 3) Commands to install and deploy

```bash
npm install --save-dev vercel vercel-php
npx vercel login
npx vercel
npx vercel --prod
```

## 4) Link-check suggestion (before deploy)

Run these quick checks locally:

```bash
php -l api/index.php
php -r 'json_decode(file_get_contents("vercel.json")); echo json_last_error_msg(), PHP_EOL;'
```

And verify links/files exist:

```bash
for f in index.php login.php signup.php ash.php user_panel.php oauth_start.php oauth_callback.php AT.css style.css control.js manga.html video.html w.html; do [ -f "$f" ] && echo "OK  $f" || echo "MISS $f"; done
```

## 5) Database + env notes for Vercel serverless

- Avoid relying on local writable SQLite files for production persistence.
- Use managed DB services and keep credentials in Vercel env vars.

```bash
npx vercel env add APP_ENV production
npx vercel env add DB_DSN production
npx vercel env add DB_USER production
npx vercel env add DB_PASS production
npx vercel env add DB_PATH production
```

In PHP read with `getenv('DB_DSN')` (or `$_ENV`).


## 6) Deployment-failure fix included

- Removed legacy `builds` configuration and switched to `functions.runtime` (`vercel-php@0.7.3`) for better compatibility with current Vercel deployments.
- Fixed `/admin` rewrite bug so it maps directly to `index.php` (not `admin.php`).
Access in PHP via `getenv('NAME')` or `$_ENV['NAME']`.

For SQLite fallback in serverless, set `DB_PATH=/tmp/final_pro_data/app.sqlite`.
(Important: `/tmp` is ephemeral and resets between deployments/cold starts.)


## 6) Deployment-failure fix included

- Switched back to the broadly compatible community-runtime builder style (`"builds": [{"src": "api/index.php", "use": "vercel-php"}]`).
- Added `@vercel/static` build (non-PHP extensions only) plus `handle: filesystem` so CSS/JS/images (including `AT.css`) are served directly, while `.php` files are never exposed as static source.
- Fixed `/admin` rewrite so it maps directly to `index.php` (not `admin.php`).
