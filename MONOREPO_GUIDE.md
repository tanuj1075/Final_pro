# Monorepo Architecture (Frontend / Backend / Database)

This repository now contains a clean layered monorepo:

```text
.
├── frontend/            # Next.js App Router + Tailwind UI
├── backend/             # Express API + Google OAuth + JWT issuance
├── database/            # Postgres connection, models, schema
├── package.json         # npm workspaces + root scripts
└── ...legacy files
```

## Separation of concerns

- **Frontend** only renders UI and calls backend API via `NEXT_PUBLIC_API_BASE_URL`.
- **Backend** handles OAuth/token logic and all auth routes.
- **Database** owns DB connection/schema/model operations.
- Frontend has **no direct DB access** and no backend secrets.

## Run locally

1. Install workspace deps:

```bash
npm install
```

2. Configure env files:

- `frontend/.env` from `frontend/.env.example`
- `backend/.env` from `backend/.env.example`
- `database/.env` from `database/.env.example`

3. Start backend:

```bash
npm run dev:backend
```

4. Start frontend:

```bash
npm run dev:frontend
```

5. Hit OAuth flow:

- `http://localhost:3000` (frontend)
- Frontend redirects to `http://localhost:4000/auth/google`
- Google callback endpoint: `http://localhost:4000/auth/callback`

## Key endpoints

- `GET /health`
- `GET /auth/google`
- `GET /auth/callback`

## Production DB note

- Uses **PostgreSQL** config (`DATABASE_URL`) in `/database`.
- No SQLite dependency for serverless persistence.
