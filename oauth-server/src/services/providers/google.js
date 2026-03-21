import axios from 'axios';
import { env } from '../../config/env.js';
import { OAuthError } from '../../utils/errors.js';
import { validateGoogleIdToken } from '../tokenValidators.js';

const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
const TOKEN_URL = 'https://oauth2.googleapis.com/token';

function redirectUri() {
  return `${env.baseUrl}/auth/google/callback`;
}

export function validateGoogleConfig() {
  if (!env.providers.google.clientId || !env.providers.google.clientSecret) {
    throw new OAuthError('Google OAuth credentials are not configured', 500);
  }
}

export function buildGoogleAuthUrl(state) {
  const params = new URLSearchParams({
    client_id: env.providers.google.clientId,
    redirect_uri: redirectUri(),
    response_type: 'code',
    scope: 'openid email profile',
    state,
    access_type: 'offline',
    prompt: 'consent'
  });

  return `${AUTH_URL}?${params.toString()}`;
}

export async function googleExchangeCode(code) {
  try {
    const payload = new URLSearchParams({
      code,
      client_id: env.providers.google.clientId,
      client_secret: env.providers.google.clientSecret,
      redirect_uri: redirectUri(),
      grant_type: 'authorization_code'
    });

    const { data } = await axios.post(TOKEN_URL, payload.toString(), {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      timeout: 10_000
    });

    return data;
  } catch (error) {
    throw new OAuthError('Google token exchange failed', 502, error.response?.data);
  }
}

export async function googleProfileFromTokens(tokens) {
  if (!tokens.id_token) throw new OAuthError('Google ID token missing', 401);

  const payload = await validateGoogleIdToken(tokens.id_token, env.providers.google.clientId);
  if (!payload.email_verified) throw new OAuthError('Google email is not verified', 422);
  if (!payload.email) throw new OAuthError('Email not provided by Google', 422);

  return {
    provider: 'google',
    providerId: payload.sub,
    email: payload.email,
    name: payload.name || '',
    avatar: payload.picture || null
  };
}
