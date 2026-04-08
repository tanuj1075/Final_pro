# AckerStream - Anime & Manga Streaming Platform

A high-performance, cinematic PHP-based platform for streaming anime and reading manga.

## Project Structure
- `src/pages/` - All user-facing PHP pages (Manga, Anime, User Panel, etc.)
- `src/services/` - Backend services and API endpoints.
- `src/components/` - Reusable UI components (Navbar, Header, etc.).
- `src/utils/` - Shared utility functions and database bootstrap logic.
- `api/index.php` - The Vercel-compatible serverless router.
- `data/` - Contains the local SQLite database (`app.sqlite`).

## Getting Started

### Prerequisites
- PHP 8.0 or higher
- SQLite3

### Running Locally
You can run the project using the built-in PHP development server:

```bash
php -S localhost:8000 api/index.php
```

Then visit `http://localhost:8000` in your browser.

## Deployment
This project is optimized for deployment on **Vercel** using the `vercel.json` configuration provided. It uses the `api/index.php` as a router to handle pretty URLs.

---
© 2026 AckerStream Elite

## Access URLs
- **User login (default homepage):** `/` or `/index.php`
- **Admin panel login:** `/admin`

### Admin login notes
- Admin auth is handled by `src/pages/index.php` (admin controller entrypoint) routed via `/admin` in `vercel.json`.
- If admin login says it is disabled, set environment variables on Vercel: `ADMIN_USERNAME` and `ADMIN_PASSWORD` (or `ADMIN_PASSWORD_HASH`).

# Ackerstream
