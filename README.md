# Crunchrolly Anime Web App

A PHP + HTML/CSS/JS anime streaming-style project with:

- User signup/login flow (`signup.php`, `login.php`)
- Dedicated user panel (`user_panel.php`) after successful user login
- Admin login/dashboard entry (`index.php`)
- Protected main app page (`ash.php`)
- Auto-initialized SQLite backend (`data/app.sqlite`) via `db_helper.php`
- Admin approval workflow for new user accounts (approve from `index.php`)
- Static watch/info pages (`w.html`, `video.html`, `manga.html`)
- Dynamic anime catalog with centralized details/search (`anime_hub.php`, `anime_detail.php`)
- Admin content management for synopsis/trailer/poster/manga/stream/schedule (`manage_anime.php`)

## Project structure

```text
.
├── index.php         # Admin login + admin panel entry
├── login.php         # User login
├── signup.php        # User registration
├── ash.php           # Protected main homepage/dashboard
├── anime_hub.php     # Dynamic searchable anime catalog
├── anime_detail.php  # Full centralized anime detail page
├── manage_anime.php  # Admin anime content management
├── db_helper.php     # SQLite DB helper + auto schema init
├── data/app.sqlite   # Auto-created runtime DB file
├── control.js        # Homepage UI interactions
├── AT.css            # Main dashboard styles
├── watchstyle.css    # Watch-page styles
├── watch1.html       # Detailed "Your Name" watch page
├── manga.html        # Manga page
├── video.html        # Video/news style page
└── media assets      # images/videos/icons in repo root
```

## Local run

From repo root:

```bash
php -S 0.0.0.0:8000
```

Then open:

- `http://127.0.0.1:8000/login.php` (user login)
- `http://127.0.0.1:8000/user_panel.php` (user panel)
- `http://127.0.0.1:8000/index.php` (admin login)
- `http://127.0.0.1:8000/ash.php` (protected homepage)

## Basic quality checks

```bash
for f in *.php; do php -l "$f"; done
for f in *.js; do node --check "$f"; done
```

## User flow

- User login redirects to `user_panel.php` first.
- From user panel, users can open the anime home (`ash.php?from_panel=1`).
- Admin approval is still required before user login is allowed.

## Database behavior

- On first run, `db_helper.php` automatically creates required tables in `data/app.sqlite`.
- New signups are created as **pending**.
- Admin can approve or reject pending users from `index.php` admin panel.
- Only approved users can log in from `login.php`.


## Social OAuth setup

To enable Google / Meta(Facebook) / Apple sign-in:

1. Register apps in provider consoles and create OAuth credentials.
2. Configure callback URL to:
   - `http://127.0.0.1:8000/oauth_callback.php?provider=google`
   - `http://127.0.0.1:8000/oauth_callback.php?provider=facebook`
   - `http://127.0.0.1:8000/oauth_callback.php?provider=apple`
3. Set environment variables before starting PHP server:

```bash
export GOOGLE_CLIENT_ID=...
export GOOGLE_CLIENT_SECRET=...
export FACEBOOK_CLIENT_ID=...
export FACEBOOK_CLIENT_SECRET=...
export APPLE_CLIENT_ID=...
export APPLE_CLIENT_SECRET=...
```

Then use social buttons on login/admin pages. Newly created social accounts are kept pending until admin approval.

## Auto deploy from GitHub to live site (Vercel)

This repo now includes a GitHub Actions workflow at:

- `.github/workflows/vercel-auto-deploy.yml`

Behavior:

- Every push to `main`/`master` triggers a **production deploy**.
- Every pull request to `main`/`master` triggers a **preview deploy**.

### One-time setup in GitHub

In your GitHub repository, add these **Actions secrets**:

- `VERCEL_TOKEN`
- `VERCEL_ORG_ID`
- `VERCEL_PROJECT_ID`

After this setup, when you change code in GitHub and push, the website will update automatically after the workflow succeeds.


## Make it open on any phone/device with a global link

This project is already set up for global deployment using **Vercel** (`vercel.json` + GitHub Action workflow).

### Fastest method (GitHub + Vercel)

1. Push this repo to GitHub.
2. In Vercel, import the GitHub repository.
3. In Vercel project settings, add environment variables from `.env.example` and `DEPLOY_VERCEL_PHP.md`.
4. Deploy. Vercel gives you a public HTTPS URL (for example: `https://your-project.vercel.app`).
5. Open that URL on any phone/browser.

### Optional: custom domain

In Vercel -> Project -> Domains, add your own domain (example: `animehub.com`) and update DNS records.

After DNS propagation, your website will be globally reachable on desktop and mobile devices.

