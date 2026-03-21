import app from './app.js';
import { env } from './config/env.js';
import { registerProvider } from './services/oauthService.js';

import {
  buildGoogleAuthUrl,
  googleExchangeCode,
  googleProfileFromTokens,
  validateGoogleConfig
} from './services/providers/google.js';
import {
  buildFacebookAuthUrl,
  facebookExchangeCode,
  facebookProfileFromTokens,
  validateFacebookConfig
} from './services/providers/facebook.js';
import {
  buildAppleAuthUrl,
  appleExchangeCode,
  appleProfileFromTokens,
  validateAppleConfig
} from './services/providers/apple.js';

registerProvider('google', {
  validateConfig: validateGoogleConfig,
  buildAuthUrl: buildGoogleAuthUrl,
  exchangeCode: googleExchangeCode,
  normalizeProfile: googleProfileFromTokens
});

registerProvider('facebook', {
  validateConfig: validateFacebookConfig,
  buildAuthUrl: buildFacebookAuthUrl,
  exchangeCode: facebookExchangeCode,
  normalizeProfile: facebookProfileFromTokens
});

registerProvider('apple', {
  validateConfig: validateAppleConfig,
  buildAuthUrl: buildAppleAuthUrl,
  exchangeCode: appleExchangeCode,
  normalizeProfile: appleProfileFromTokens
});

app.listen(env.port, () => {
  console.log(`OAuth server listening on port ${env.port}`);
});
