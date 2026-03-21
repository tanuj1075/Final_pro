import axios from 'axios';
import { env } from '../../config/env.js';
import { OAuthError } from '../../utils/errors.js';

const AUTH_URL = 'https://www.facebook.com/v21.0/dialog/oauth';
const TOKEN_URL = 'https://graph.facebook.com/v21.0/oauth/access_token';
const PROFILE_URL = 'https://graph.facebook.com/me';

function redirectUri() {
  return `${env.baseUrl}/auth/facebook/callback`;
}

export function validateFacebookConfig() {
  if (!env.providers.facebook.clientId || !env.providers.facebook.clientSecret) {
    throw new OAuthError('Facebook OAuth credentials are not configured', 500);
  }
}

export function buildFacebookAuthUrl(state) {
  const params = new URLSearchParams({
    client_id: env.providers.facebook.clientId,
    redirect_uri: redirectUri(),
    response_type: 'code',
    scope: 'email,public_profile',
    state
  });

  return `${AUTH_URL}?${params.toString()}`;
}

export async function facebookExchangeCode(code) {
  try {
    const { data } = await axios.get(TOKEN_URL, {
      params: {
        client_id: env.providers.facebook.clientId,
        client_secret: env.providers.facebook.clientSecret,
        redirect_uri: redirectUri(),
        code
      },
      timeout: 10_000
    });

    return data;
  } catch (error) {
    throw new OAuthError('Facebook token exchange failed', 502, error.response?.data);
  }
}

export async function facebookProfileFromTokens(tokens) {
  try {
    const { data } = await axios.get(PROFILE_URL, {
      params: {
        fields: 'id,name,email,picture.type(large)',
        access_token: tokens.access_token
      },
      timeout: 10_000
    });

    if (!data.email) throw new OAuthError('Email not provided by Facebook', 422);

    return {
      provider: 'facebook',
      providerId: data.id,
      email: data.email,
      name: data.name || '',
      avatar: data.picture?.data?.url || null
    };
  } catch (error) {
    if (error instanceof OAuthError) throw error;
    throw new OAuthError('Failed to fetch Facebook profile', 502, error.response?.data);
  }
}
