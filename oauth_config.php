<?php
/**
 * OAuth provider configuration.
 *
 * Set these values in your environment (recommended) or edit for local testing.
 */
return [
    'google' => [
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: '',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: '',
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'scope' => 'openid email profile',
    ],
    'facebook' => [
        'client_id' => getenv('FACEBOOK_CLIENT_ID') ?: '',
        'client_secret' => getenv('FACEBOOK_CLIENT_SECRET') ?: '',
        'authorize_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
        'userinfo_url' => 'https://graph.facebook.com/me?fields=id,name,email',
        'scope' => 'email,public_profile',
    ],
    'apple' => [
        'client_id' => getenv('APPLE_CLIENT_ID') ?: '',
        // Apple requires a JWT client secret generated from Apple Developer keys.
        'client_secret' => getenv('APPLE_CLIENT_SECRET') ?: '',
        'authorize_url' => 'https://appleid.apple.com/auth/authorize',
        'token_url' => 'https://appleid.apple.com/auth/token',
        'scope' => 'name email',
    ],
];
