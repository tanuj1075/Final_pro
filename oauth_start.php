<?php
require_once 'security.php';

$config = require __DIR__ . '/oauth_config.php';
$provider = $_GET['provider'] ?? '';

if (!isset($config[$provider])) {
    header('Location: login.php?error=Unsupported social provider.');
    exit;
}

$providerConfig = $config[$provider];
if (empty($providerConfig['client_id']) || empty($providerConfig['client_secret'])) {
    header('Location: login.php?error=' . urlencode(ucfirst($provider) . ' OAuth is not configured yet.'));
    exit;
}

$redirectUri = build_app_url('/oauth_callback.php?provider=' . urlencode($provider));
$state = bin2hex(random_bytes(32));
$expiresAt = time() + 600;

$statePayload = [
    'provider' => $provider,
    'state' => $state,
    'redirect_uri' => $redirectUri,
    'exp' => $expiresAt,
];

try {
    $stateCookie = build_oauth_state_cookie_value($statePayload);
    setcookie('oauth_state_' . $provider, $stateCookie, oauth_cookie_options($expiresAt));
} catch (Throwable $e) {
    error_log('OAuth start failed for provider ' . $provider . ': ' . $e->getMessage());
    header('Location: login.php?error=' . urlencode('OAuth is unavailable due to server configuration.'));
    exit;
}

$params = [
    'client_id' => $providerConfig['client_id'],
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => $providerConfig['scope'],
    'state' => $state,
];

if ($provider === 'apple') {
    $params['response_mode'] = 'form_post';
}

$authUrl = $providerConfig['authorize_url'] . '?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
