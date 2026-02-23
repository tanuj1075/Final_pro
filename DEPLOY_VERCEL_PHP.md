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
# Deploying this project to Vercel with `vercel-php`

This guide shows a simple pattern to deploy a mixed static + PHP project on Vercel.

## 1) Suggested structure

```text
.
├── api/
│   └── index.php          # serverless PHP router entrypoint
├── pages/
│   ├── about.php          # dynamic PHP page
│   └── contact.php        # GET/POST contact handler
├── index.html             # static homepage served at /
├── style.css
├── script.js
└── vercel.json
```

### Why this structure

- Files in `api/` are treated as serverless functions.
- Static assets (`.html`, `.css`, `.js`, images) are served directly by Vercel CDN.
- The router in `/api/index.php` keeps URL paths clean (`/about`, `/contact`) while still running PHP.

## 2) `vercel.json` explanation

The included `vercel.json` does the following:

- Uses community runtime `vercel-php` for all `api/**/*.php` files.
- Serves all non-PHP static assets via `@vercel/static`.
- Rewrites `/about` and `/contact` (and other routes) to `/api/index.php`.

## 3) Router behavior in `/api/index.php`

The included example router is implemented to:

- Serve `/` from `index.html`.
- Serve `/about` by requiring `pages/about.php` (or fallback `about.php`).
- Serve `/contact` for `GET` and `POST` by requiring `pages/contact.php` (or fallback `contact.php`).

## 4) Install and deploy

### Option A: global install

```bash
npm install -g vercel vercel-php
vercel login
vercel
```

### Option B: project-local install (recommended in teams)

```bash
npm install --save-dev vercel vercel-php
npx vercel login
npx vercel
```

Then for production:

```bash
npx vercel --prod
```

## 5) Database and environment variable notes

### Environment variables

Set secrets in Vercel instead of hardcoding:

```bash
npx vercel env add APP_ENV production
npx vercel env add DB_HOST production
npx vercel env add DB_USER production
npx vercel env add DB_PASS production
```

In PHP read with `getenv('DB_DSN')` (or `$_ENV`).


## 6) Deployment-failure fix included

- Switched back to the broadly compatible community-runtime builder style (`"builds": [{"src": "api/index.php", "use": "vercel-php"}]`).
- Kept `handle: filesystem` so static files are served directly before rewrites.
- Fixed `/admin` rewrite so it maps directly to `index.php` (not `admin.php`).
Access in PHP via `getenv('NAME')` or `$_ENV['NAME']`.

### Databases on serverless

- Do **not** rely on local files (like SQLite in writable local disk) for persistent production data on serverless.
- Prefer managed databases (PlanetScale/MySQL, Neon/Postgres, Supabase, etc.).
- Use connection pooling and short-lived connections due to serverless cold starts.
- Keep credentials in Vercel environment variables.
