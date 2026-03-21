import express from 'express';
import { issueState, consumeState } from '../utils/stateStore.js';
import { OAuthError } from '../utils/errors.js';
import { assertProvider, authenticateWithProvider } from '../services/oauthService.js';
import { env } from '../config/env.js';

const router = express.Router();

router.get('/auth/:provider', (req, res, next) => {
  try {
    const providerName = req.params.provider;
    const provider = assertProvider(providerName);

    const state = issueState(providerName);
    res.cookie(env.stateCookieName, state, {
      httpOnly: true,
      sameSite: 'lax',
      secure: env.cookieSecure,
      signed: true,
      maxAge: env.stateTtlMs
    });

    return res.redirect(provider.buildAuthUrl(state));
  } catch (error) {
    return next(error);
  }
});

async function callbackHandler(req, res, next) {
  try {
    const providerName = req.params.provider;
    assertProvider(providerName);

    const source = req.method === 'POST' ? req.body : req.query;
    const { code, state, error, error_description: errorDescription } = source;
    const cookieState = req.signedCookies?.[env.stateCookieName];

    if (error) {
      throw new OAuthError(`OAuth provider error: ${error}`, 400, errorDescription || null);
    }

    if (!code) throw new OAuthError('Missing code in callback', 400);
    if (!state || !cookieState || state !== cookieState || !consumeState(state, providerName)) {
      throw new OAuthError('Invalid OAuth state', 403);
    }

    const result = await authenticateWithProvider(providerName, String(code));

    res.clearCookie(env.stateCookieName);
    return res.status(200).json({
      message: result.created ? 'User registered and logged in' : 'User logged in',
      token: result.token,
      user: result.profile
    });
  } catch (error) {
    return next(error);
  } finally {
    res.clearCookie(env.stateCookieName);
  }
}

router.get('/auth/:provider/callback', callbackHandler);
router.post('/auth/:provider/callback', callbackHandler);

export default router;
