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

Access in PHP via `getenv('NAME')` or `$_ENV['NAME']`.

### Databases on serverless

- Do **not** rely on local files (like SQLite in writable local disk) for persistent production data on serverless.
- Prefer managed databases (PlanetScale/MySQL, Neon/Postgres, Supabase, etc.).
- Use connection pooling and short-lived connections due to serverless cold starts.
- Keep credentials in Vercel environment variables.
