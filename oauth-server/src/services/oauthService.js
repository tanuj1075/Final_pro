import { OAuthError } from '../utils/errors.js';
import { upsertUser } from '../db/userStore.js';
import { signAuthToken } from '../utils/jwt.js';

const providers = new Map();

export function registerProvider(name, providerConfig) {
  providers.set(name, providerConfig);
}

export function assertProvider(name) {
  if (!providers.has(name)) {
    throw new OAuthError('Unsupported OAuth provider', 404);
  }
  const provider = providers.get(name);
  if (typeof provider.validateConfig === 'function') {
    provider.validateConfig();
  }
  return provider;
}

export async function authenticateWithProvider(providerName, code) {
  if (!code) throw new OAuthError('Missing authorization code', 400);

  const provider = assertProvider(providerName);
  const tokens = await provider.exchangeCode(code);
  const normalizedProfile = await provider.normalizeProfile(tokens);

  if (!normalizedProfile.email) {
    throw new OAuthError('Email not provided by provider', 422);
  }

  const { user, created } = await upsertUser(normalizedProfile);
  const jwt = signAuthToken(user);

  return {
    user,
    created,
    token: jwt,
    profile: normalizedProfile
  };
}
