import crypto from 'crypto';
import { buildGoogleAuthUrl, exchangeGoogleCode, verifyGoogleIdToken } from '../services/googleOAuthService.js';
import { upsertOAuthUser } from '../db/users.js';
import { issueAuthToken } from '../services/jwtService.js';
import { env } from '../config/env.js';

const STATE_COOKIE = 'oauth_state_google';

export function googleStart(req, res) {
  const state = crypto.randomBytes(32).toString('hex');

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

    if (error) return res.status(400).json({ error: String(error), details: String(errorDescription || '') });
    if (!code) return res.status(400).json({ error: 'Missing code' });
    if (!state || !cookieState || state !== cookieState) return res.status(403).json({ error: 'Invalid state' });

    const tokens = await exchangeGoogleCode(String(code));
    const profile = await verifyGoogleIdToken(tokens.id_token);
    const { user, created } = await upsertOAuthUser(profile);

    const token = issueAuthToken(user);

    res.clearCookie(STATE_COOKIE, { path: '/' });

    return res.status(200).json({
      message: created ? 'User registered and logged in' : 'User logged in',
      token,
      user,
    });
  } catch (err) {
    return next(err);
  }
}
