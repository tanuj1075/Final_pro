import dotenv from 'dotenv';

dotenv.config();

const required = [
  'JWT_SECRET',
  'COOKIE_SECRET',
  'BASE_URL',
  'GOOGLE_CLIENT_ID',
  'GOOGLE_CLIENT_SECRET',
  'FACEBOOK_CLIENT_ID',
  'FACEBOOK_CLIENT_SECRET',
  'APPLE_CLIENT_ID',
  'APPLE_TEAM_ID',
  'APPLE_KEY_ID',
  'APPLE_PRIVATE_KEY'
];

for (const key of required) {
  if (!process.env[key]) {
    // Allow server boot for local development visibility, but warn loudly.
    console.warn(`[WARN] Missing environment variable: ${key}`);
  }
}

export const env = {
  port: Number(process.env.PORT || 3000),
  baseUrl: (process.env.BASE_URL || 'http://localhost:3000').replace(/\/+$/, ''),
  jwtSecret: process.env.JWT_SECRET || 'change-me-in-production',
  cookieSecret: process.env.COOKIE_SECRET || 'change-me-cookie-secret',
  jwtExpiresIn: process.env.JWT_EXPIRES_IN || '1h',
  mongoUri: process.env.MONGO_URI,
  stateCookieName: process.env.STATE_COOKIE_NAME || 'oauth_state',
  stateTtlMs: Number(process.env.STATE_TTL_MS || 10 * 60 * 1000),
  cookieSecure: process.env.COOKIE_SECURE ? process.env.COOKIE_SECURE === 'true' : true,
  cookieSameSite: process.env.COOKIE_SAME_SITE || 'none',
  providers: {
    google: {
      clientId: process.env.GOOGLE_CLIENT_ID,
      clientSecret: process.env.GOOGLE_CLIENT_SECRET
    },
    facebook: {
      clientId: process.env.FACEBOOK_CLIENT_ID,
      clientSecret: process.env.FACEBOOK_CLIENT_SECRET
    },
    apple: {
      clientId: process.env.APPLE_CLIENT_ID,
      teamId: process.env.APPLE_TEAM_ID,
      keyId: process.env.APPLE_KEY_ID,
      privateKey: process.env.APPLE_PRIVATE_KEY?.replace(/\\n/g, '\n')
    }
  }
};
