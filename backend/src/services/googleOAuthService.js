import axios from 'axios';
import { createRemoteJWKSet, jwtVerify } from 'jose';
import { env } from '../config/env.js';

const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
const GOOGLE_JWKS_URL = new URL('https://www.googleapis.com/oauth2/v3/certs');
const GOOGLE_ISSUERS = ['https://accounts.google.com', 'accounts.google.com'];

const JWKS = createRemoteJWKSet(GOOGLE_JWKS_URL);

export function buildGoogleAuthUrl(state) {
  const params = new URLSearchParams({
    client_id: env.googleClientId,
    redirect_uri: env.googleRedirectUri,
    response_type: 'code',
    scope: 'openid email profile',
    state,
    access_type: 'offline',
    prompt: 'consent',
  });

  return `${GOOGLE_AUTH_URL}?${params.toString()}`;
}

export async function exchangeGoogleCode(code) {
  if (typeof code !== 'string' || code.trim() === '') {
    throw new Error('Missing Google authorization code');
  }

  const payload = new URLSearchParams({
    code,
    client_id: env.googleClientId,
    client_secret: env.googleClientSecret,
    redirect_uri: env.googleRedirectUri,
    grant_type: 'authorization_code',
  });

  let data;
  try {
    const response = await axios.post(GOOGLE_TOKEN_URL, payload.toString(), {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      timeout: 10000,
    });
    data = response?.data;
  } catch (error) {
    const status = error?.response?.status;
    const providerError = error?.response?.data?.error_description || error?.response?.data?.error;
    const detail = providerError ? `: ${providerError}` : '';
    throw new Error(`Failed to exchange Google code${status ? ` (HTTP ${status})` : ''}${detail}`);
  }

  if (!data?.id_token || !data?.access_token) {
    throw new Error('Google token response missing id_token or access_token');
  }

  return data;
}

export async function verifyGoogleIdToken(idToken) {
  if (typeof idToken !== 'string' || idToken.trim() === '') {
    throw new Error('Missing Google id_token');
  }

  let payload;
  try {
    const verified = await jwtVerify(idToken, JWKS, {
      issuer: GOOGLE_ISSUERS,
      audience: env.googleClientId,
    });
    payload = verified.payload;
  } catch (error) {
    throw new Error('Invalid Google ID token');
  }

  if (payload.email_verified !== true) {
    throw new Error('Google email is not verified');
  }

  if (!payload.email || !payload.sub) {
    throw new Error('Google ID token missing required claims');
  }

  return {
    provider: 'google',
    providerId: String(payload.sub),
    email: String(payload.email).toLowerCase(),
    name: String(payload.name || ''),
    avatar: payload.picture ? String(payload.picture) : null,
  };
}
