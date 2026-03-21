import dotenv from 'dotenv';

dotenv.config({ path: new URL('../../.env', import.meta.url).pathname });
dotenv.config();

function required(name) {
  const value = String(process.env[name] || '').trim();
  if (!value) throw new Error(`Missing required env var: ${name}`);
  return value;
}

function parsePort(rawPort) {
  const port = Number(rawPort);
  if (!Number.isInteger(port) || port <= 0 || port > 65535) {
    throw new Error(`Invalid PORT value: ${rawPort}`);
  }
  return port;
}

function parseHttpUrl(name, rawValue) {
  const value = String(rawValue || '').trim();
  if (!value) throw new Error(`Missing required env var: ${name}`);

  let parsed;
  try {
    parsed = new URL(value);
  } catch (error) {
    throw new Error(`Invalid URL for ${name}`);
  }

  if (!['http:', 'https:'].includes(parsed.protocol)) {
    throw new Error(`Invalid protocol for ${name}. Use http or https.`);
  }

  return parsed.toString().replace(/\/$/, '');
}

function parseSameSite(rawValue) {
  const value = String(rawValue || 'lax').toLowerCase();
  if (!['lax', 'strict', 'none'].includes(value)) {
    throw new Error('COOKIE_SAME_SITE must be one of: lax, strict, none');
  }
  return value;
}

function parseBoolean(rawValue, defaultValue = false) {
  if (rawValue === undefined || rawValue === null || rawValue === '') return defaultValue;
  return String(rawValue).toLowerCase() === 'true';
}

export const env = {
  port: parsePort(process.env.PORT || '4000'),
  apiBaseUrl: parseHttpUrl('API_BASE_URL', process.env.API_BASE_URL || 'http://localhost:4000'),
  frontendBaseUrl: parseHttpUrl('FRONTEND_BASE_URL', process.env.FRONTEND_BASE_URL || 'http://localhost:3000'),
  jwtSecret: required('JWT_SECRET'),
  googleClientId: required('GOOGLE_CLIENT_ID'),
  googleClientSecret: required('GOOGLE_CLIENT_SECRET'),
  googleRedirectUri: parseHttpUrl('GOOGLE_REDIRECT_URI', process.env.GOOGLE_REDIRECT_URI || 'http://localhost:4000/auth/callback'),
  cookieSecure: parseBoolean(process.env.COOKIE_SECURE, false),
  cookieSameSite: parseSameSite(process.env.COOKIE_SAME_SITE),
};
