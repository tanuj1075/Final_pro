import app from './app.js';
import { env } from './config/env.js';
import { registerProvider } from './services/oauthService.js';

import {
  buildGoogleAuthUrl,
  googleExchangeCode,
  googleProfileFromTokens
} from './services/providers/google.js';
import {
  buildFacebookAuthUrl,
  facebookExchangeCode,
  facebookProfileFromTokens
} from './services/providers/facebook.js';
import {
  buildAppleAuthUrl,
  appleExchangeCode,
  appleProfileFromTokens
} from './services/providers/apple.js';

registerProvider('google', {
  buildAuthUrl: buildGoogleAuthUrl,
  exchangeCode: googleExchangeCode,
  normalizeProfile: googleProfileFromTokens
});

registerProvider('facebook', {
  buildAuthUrl: buildFacebookAuthUrl,
  exchangeCode: facebookExchangeCode,
  normalizeProfile: facebookProfileFromTokens
});

registerProvider('apple', {
  buildAuthUrl: buildAppleAuthUrl,
  exchangeCode: appleExchangeCode,
  normalizeProfile: appleProfileFromTokens
});

app.listen(env.port, () => {
  console.log(`OAuth server listening on port ${env.port}`);
});
