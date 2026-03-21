import crypto from 'crypto';
import { buildGoogleAuthUrl, exchangeGoogleCode, verifyGoogleIdToken } from '../services/googleOAuthService.js';
import { upsertOAuthUser } from '../db/users.js';
import { issueAuthToken } from '../services/jwtService.js';
import { env } from '../config/env.js';

const STATE_COOKIE = 'oauth_state_google';

export function googleStart(req, res) {
  const state = crypto.randomBytes(32).toString('hex');
  console.log('Starting Google OAuth flow');

  res.cookie(STATE_COOKIE, state, {
    httpOnly: true,
    secure: env.cookieSecure,
    sameSite: env.cookieSameSite,
    maxAge: 10 * 60 * 1000,
    path: '/',
  });

  return res.redirect(buildGoogleAuthUrl(state));
}

export async function googleCallback(req, res, next) {
  try {
    const { code, state, error, error_description: errorDescription } = req.query;
    const cookieState = req.cookies?.[STATE_COOKIE];
    console.log('Received Google OAuth callback');

    if (error) {
      return res.status(400).json({
        error: 'OAuth provider error',
        details: String(errorDescription || error),
      });
    }

    if (typeof code !== 'string' || code.trim() === '') {
      return res.status(400).json({ error: 'Missing or invalid code parameter' });
    }

    if (typeof state !== 'string' || state.trim() === '') {
      return res.status(400).json({ error: 'Missing or invalid state parameter' });
    }

    if (typeof cookieState !== 'string' || cookieState.trim() === '' || state !== cookieState) {
      return res.status(401).json({ error: 'Invalid OAuth state' });
    }

    const tokens = await exchangeGoogleCode(String(code));
    const profile = await verifyGoogleIdToken(tokens.id_token);
    const { user, created } = await upsertOAuthUser(profile);

    if (!user?.id || !user?.email) {
      return res.status(500).json({ error: 'Invalid user object returned from database' });
    }

    const token = issueAuthToken(user);

    res.clearCookie(STATE_COOKIE, { path: '/' });

    return res.status(200).json({
      message: created ? 'User registered and logged in' : 'User logged in',
      token,
      user,
    });
  } catch (err) {
    console.error('Google callback failed:', err?.message || err);
    return next(err);
  }
}
