# Project Setup

## Structure
- `frontend` = UI (Next.js)
- `backend` = API + OAuth
- `database` = DB models/config/schema

## Run Frontend
```bash
cd frontend
npm install
npm run dev
```

## Run Backend
```bash
cd backend
npm install
node server.js
```

## Run Database Layer
```bash
cd database
npm install
# use database/schema/users.sql with your Postgres instance
```

## Notes
- Frontend never talks directly to database.
- Backend is the only layer that talks to database.
- Use separate env files for each layer.
