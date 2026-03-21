import crypto from 'crypto';
import { buildGoogleRedirectUrl } from '../services/googleOAuthService.js';

export function googleLogin(req, res) {
  const state = crypto.randomBytes(16).toString('hex');
  res.cookie('oauth_state', state, { httpOnly: true, sameSite: 'lax' });
  res.redirect(buildGoogleRedirectUrl(state));
}

export function oauthCallback(req, res) {
  const { code, state } = req.query;
  const savedState = req.cookies?.oauth_state;

  if (!code || !state || !savedState || state !== savedState) {
    return res.status(400).json({ error: 'Invalid OAuth callback payload' });
  }

  res.clearCookie('oauth_state');
  return res.status(200).json({ message: 'OAuth callback received', code: String(code) });
}
