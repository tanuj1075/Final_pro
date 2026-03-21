import axios from 'axios';
import { SignJWT, importPKCS8 } from 'jose';
import { env } from '../../config/env.js';
import { OAuthError } from '../../utils/errors.js';
import { validateAppleIdToken } from '../tokenValidators.js';

const AUTH_URL = 'https://appleid.apple.com/auth/authorize';
const TOKEN_URL = 'https://appleid.apple.com/auth/token';

function redirectUri() {
  return `${env.baseUrl}/auth/apple/callback`;
}

export function validateAppleConfig() {
  const c = env.providers.apple;
  if (!c.clientId || !c.teamId || !c.keyId || !c.privateKey) {
    throw new OAuthError('Apple OAuth credentials are not configured', 500);
  }
}

function toPkcs8(privateKey) {
  return privateKey.includes('BEGIN PRIVATE KEY')
    ? privateKey
    : `-----BEGIN PRIVATE KEY-----\n${privateKey}\n-----END PRIVATE KEY-----`;
}

async function createAppleClientSecret() {
  const keyPem = toPkcs8(env.providers.apple.privateKey || '');
  const key = await importPKCS8(keyPem, 'ES256');

  const now = Math.floor(Date.now() / 1000);
  return new SignJWT({})
    .setProtectedHeader({ alg: 'ES256', kid: env.providers.apple.keyId })
    .setIssuer(env.providers.apple.teamId)
    .setAudience('https://appleid.apple.com')
    .setSubject(env.providers.apple.clientId)
    .setIssuedAt(now)
    .setExpirationTime(now + 60 * 60 * 24 * 180)
    .sign(key);
}

export function buildAppleAuthUrl(state) {
  const params = new URLSearchParams({
    client_id: env.providers.apple.clientId,
    redirect_uri: redirectUri(),
    response_type: 'code',
    response_mode: 'query',
    scope: 'name email',
    state
  });

  return `${AUTH_URL}?${params.toString()}`;
}

export async function appleExchangeCode(code) {
  try {
    const clientSecret = await createAppleClientSecret();
    const payload = new URLSearchParams({
      grant_type: 'authorization_code',
      code,
      redirect_uri: redirectUri(),
      client_id: env.providers.apple.clientId,
      client_secret: clientSecret
    });

    const { data } = await axios.post(TOKEN_URL, payload.toString(), {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      timeout: 10_000
    });

    return data;
  } catch (error) {
    throw new OAuthError('Apple token exchange failed', 502, error.response?.data || error.message);
  }
}

export async function appleProfileFromTokens(tokens) {
  if (!tokens.id_token) throw new OAuthError('Apple ID token missing', 401);

  const payload = await validateAppleIdToken(tokens.id_token, env.providers.apple.clientId);
  if (!payload.email) throw new OAuthError('Email not provided by Apple', 422);

  return {
    provider: 'apple',
    providerId: payload.sub,
    email: payload.email,
    name: '',
    avatar: null
  };
}
