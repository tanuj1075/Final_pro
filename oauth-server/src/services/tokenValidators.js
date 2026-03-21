import { createRemoteJWKSet, jwtVerify } from 'jose';
import { OAuthError } from '../utils/errors.js';

const googleJwks = createRemoteJWKSet(new URL('https://www.googleapis.com/oauth2/v3/certs'));
const appleJwks = createRemoteJWKSet(new URL('https://appleid.apple.com/auth/keys'));

export async function validateGoogleIdToken(idToken, audience) {
  try {
    const { payload } = await jwtVerify(idToken, googleJwks, {
      issuer: ['https://accounts.google.com', 'accounts.google.com'],
      audience
    });
    return payload;
  } catch {
    throw new OAuthError('Invalid Google ID token', 401);
  }
}

export async function validateAppleIdToken(idToken, audience) {
  try {
    const { payload } = await jwtVerify(idToken, appleJwks, {
      issuer: 'https://appleid.apple.com',
      audience
    });
    return payload;
  } catch {
    throw new OAuthError('Invalid Apple ID token', 401);
  }
}
