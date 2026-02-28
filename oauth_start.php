<?php
require_once 'security.php';
secure_session_start();

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

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state_' . $provider] = $state;
$_SESSION['oauth_redirect_uri_' . $provider] = $redirectUri;

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
