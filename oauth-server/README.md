# OAuth Backend (Node.js + Express)

Secure OAuth 2.0 Authorization Code flow backend supporting:
- Google
- Facebook
- Apple

## Features
- Backend-only token exchange (client secrets never exposed to frontend)
- Standard routes:
  - `GET /auth/:provider`
  - `GET /auth/:provider/callback`
- CSRF protection with `state` parameter + HttpOnly state cookie validation
- ID token validation for Google and Apple (JWKS + JWT verification)
- User normalization format:
  ```json
  { "provider": "google", "providerId": "...", "email": "...", "name": "...", "avatar": "..." }
  ```
- User persistence with MongoDB (optional) or in-memory fallback
- Existing-user login and new-user registration in one flow
- JWT issuance after successful auth
- Explicit error handling for missing code, token failures, and missing email

## Setup
1. Copy env template:
   ```bash
   cp .env.example .env
   ```
2. Fill provider credentials.
3. Install and run:
   ```bash
   npm install
   npm run start
   ```

## Callback URLs
Use these in provider consoles:
- Google: `http://localhost:3000/auth/google/callback`
- Facebook: `http://localhost:3000/auth/facebook/callback`
- Apple: `http://localhost:3000/auth/apple/callback`

## Response from callback
```json
{
  "message": "User logged in",
  "token": "jwt",
  "user": {
    "provider": "google",
    "providerId": "123",
    "email": "a@b.com",
    "name": "User Name",
    "avatar": "https://..."
  }
}
```

## Security Notes
- Keep `JWT_SECRET` and OAuth client secrets on the server only.
- Set `COOKIE_SECURE=true` in production (HTTPS).
- Rotate secrets and monitor OAuth errors.
