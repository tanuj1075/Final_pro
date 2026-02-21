<?php
/**
 * OAuth provider configuration.
 *
 * Credential lookup order:
 * 1) process environment variables
 * 2) values loaded from local .env file in project root
 */

$dotenv = [];
$dotenvPath = __DIR__ . '/.env';
if (is_readable($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                $dotenv[$key] = $value;
            }
        }
    }
}

$getSecret = static function (string $key) use ($dotenv): string {
    $envValue = getenv($key);
    if ($envValue !== false && trim((string)$envValue) !== '') {
        return trim((string)$envValue);
    }

    return trim((string)($dotenv[$key] ?? ''));
};

return [
    'google' => [
        'client_id' => $getSecret('GOOGLE_CLIENT_ID'),
        'client_secret' => $getSecret('GOOGLE_CLIENT_SECRET'),
        'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'scope' => 'openid email profile',
    ],
    'facebook' => [
        'client_id' => $getSecret('FACEBOOK_CLIENT_ID'),
        'client_secret' => $getSecret('FACEBOOK_CLIENT_SECRET'),
        'authorize_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
        'userinfo_url' => 'https://graph.facebook.com/me?fields=id,name,email',
        'scope' => 'email,public_profile',
    ],
    'apple' => [
        'client_id' => $getSecret('APPLE_CLIENT_ID'),
        // Apple requires a JWT client secret generated from Apple Developer keys.
        'client_secret' => $getSecret('APPLE_CLIENT_SECRET'),
        'authorize_url' => 'https://appleid.apple.com/auth/authorize',
        'token_url' => 'https://appleid.apple.com/auth/token',
        'scope' => 'name email',
    ],
];
