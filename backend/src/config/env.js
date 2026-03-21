import dotenv from 'dotenv';

dotenv.config({ path: new URL('../../.env', import.meta.url).pathname });
dotenv.config();

function required(name) {
  const value = process.env[name];
  if (!value) throw new Error(`Missing required env var: ${name}`);
  return value;
}

export const env = {
  port: Number(process.env.PORT || 4000),
  apiBaseUrl: process.env.API_BASE_URL || 'http://localhost:4000',
  frontendBaseUrl: process.env.FRONTEND_BASE_URL || 'http://localhost:3000',
  jwtSecret: required('JWT_SECRET'),
  googleClientId: required('GOOGLE_CLIENT_ID'),
  googleClientSecret: required('GOOGLE_CLIENT_SECRET'),
  googleRedirectUri: process.env.GOOGLE_REDIRECT_URI || 'http://localhost:4000/auth/callback',
  cookieSecure: String(process.env.COOKIE_SECURE || 'false') === 'true',
  cookieSameSite: process.env.COOKIE_SAME_SITE || 'lax',
};
