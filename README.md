# Crunchrolly Anime Web App

A PHP + HTML/CSS/JS anime streaming-style project with:

- User signup/login flow (`signup.php`, `login.php`)
- Dedicated user panel (`user_panel.php`) after successful user login
- Admin login/dashboard entry (`index.php`)
- Protected main app page (`ash.php`)
- Auto-initialized SQLite backend (`data/app.sqlite`) via `db_helper.php`
- Admin approval workflow for new user accounts (approve from `index.php`)
- Static watch/info pages (`w.html`, `video.html`, `manga.html`)

## Project structure

```text
.
├── index.php         # Admin login + admin panel entry
├── login.php         # User login
├── signup.php        # User registration
├── ash.php           # Protected main homepage/dashboard
├── db_helper.php     # SQLite DB helper + auto schema init
├── data/app.sqlite   # Auto-created runtime DB file
├── control.js        # Homepage UI interactions
├── AT.css            # Main dashboard styles
├── watchstyle.css    # Watch-page styles
├── w.html            # Detailed "Your Name" watch page
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
- Admin can approve pending users from `index.php` admin panel.
- Only approved users can log in from `login.php`.
