<?php
require_once 'security.php';
secure_session_start();
require_once 'db_helper.php';

$config = require __DIR__ . '/oauth_config.php';
$provider = $_GET['provider'] ?? ($_POST['provider'] ?? '');

if (!isset($config[$provider])) {
    header('Location: login.php?error=Unsupported callback provider.');
    exit;
}

$state = $_GET['state'] ?? ($_POST['state'] ?? '');
$expectedState = $_SESSION['oauth_state_' . $provider] ?? '';
if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
    header('Location: login.php?error=Invalid OAuth state. Please try again.');
    exit;
}
unset($_SESSION['oauth_state_' . $provider]);

$code = $_GET['code'] ?? ($_POST['code'] ?? '');
if (!$code) {
    header('Location: login.php?error=OAuth code missing.');
    exit;
}

$providerConfig = $config[$provider];
$redirectUri = $_SESSION['oauth_redirect_uri_' . $provider] ?? '';
unset($_SESSION['oauth_redirect_uri_' . $provider]);

if (!$redirectUri) {
    $forwardedProto = strtolower(trim($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $scheme = ($forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https' : 'http';
    $host = trim($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000'));
    $redirectUri = $scheme . '://' . $host . '/oauth_callback.php?provider=' . urlencode($provider);
}

$tokenResponse = httpPostForm($providerConfig['token_url'], [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri,
    'client_id' => $providerConfig['client_id'],
    'client_secret' => $providerConfig['client_secret'],
]);

if (!$tokenResponse['ok']) {
    header('Location: login.php?error=' . urlencode('OAuth token exchange failed.'));
    exit;
}

$tokenData = json_decode($tokenResponse['body'], true);
$accessToken = $tokenData['access_token'] ?? null;

if (!$accessToken && $provider !== 'apple') {
    header('Location: login.php?error=OAuth token missing.');
    exit;
}

$profile = null;
if ($provider === 'google') {
    $profile = fetchJsonWithBearer($providerConfig['userinfo_url'], $accessToken);
} elseif ($provider === 'facebook') {
    $profile = fetchJsonWithBearer($providerConfig['userinfo_url'], $accessToken);
} elseif ($provider === 'apple') {
    $idToken = $tokenData['id_token'] ?? '';
    $profile = decodeAppleIdToken($idToken);
}

if (!$profile) {
    header('Location: login.php?error=Unable to fetch social profile.');
    exit;
}

$email = strtolower(trim($profile['email'] ?? ''));
$name = trim($profile['name'] ?? '');
$providerId = trim((string)($profile['id'] ?? $profile['sub'] ?? ''));

if ($email === '') {
    header('Location: login.php?error=No email returned by provider.');
    exit;
}

try {
    $db = new DatabaseHelper();
    $user = $db->getUserByEmail($email);

    if (!$user) {
        $usernameBase = $name !== '' ? preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($name)) : explode('@', $email)[0];
        $usernameBase = trim($usernameBase, '_');
        if ($usernameBase === '') {
            $usernameBase = $provider . '_user';
        }

        $username = $db->generateUniqueUsername($usernameBase);
        $created = $db->createOAuthUser($username, $email, $provider, $providerId);
        if (!$created) {
            $db->close();
            header('Location: login.php?error=Unable to create OAuth account.');
            exit;
        }
        $user = $db->getUserByEmail($email);
    }

    if (!$user || (int)$user['is_active'] !== 1) {
        $db->close();
        header('Location: login.php?error=Account disabled by admin.');
        exit;
    }

    if ((int)$user['is_approved'] !== 1) {
        $db->close();
        header('Location: login.php?error=Account pending admin approval.');
        exit;
    }

    $db->touchLastLogin((int)$user['id']);
    $db->close();

    session_regenerate_id(true);
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];

    header('Location: user_panel.php');
    exit;
} catch (Exception $e) {
    error_log('OAuth login failed for provider ' . $provider . ': ' . $e->getMessage());
    header('Location: login.php?error=' . urlencode('OAuth login failed. Please try again.'));
    exit;
}

function httpPostForm($url, $formData) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($formData),
            'timeout' => 15,
        ],
    ];

    $context = stream_context_create($opts);
    $body = @file_get_contents($url, false, $context);
    return ['ok' => $body !== false, 'body' => $body ?: ''];
}

function fetchJsonWithBearer($url, $token) {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer {$token}\r\n",
            'timeout' => 15,
        ],
    ];
    $context = stream_context_create($opts);
    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        return null;
    }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

function decodeAppleIdToken($idToken) {
    if (!$idToken || substr_count($idToken, '.') < 2) {
        return null;
    }

    $parts = explode('.', $idToken);
    $payload = $parts[1] ?? '';
    $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
    $json = base64_decode(strtr($payload, '-_', '+/'));
    $decoded = json_decode($json, true);

    if (!is_array($decoded)) {
        return null;
    }

    return [
        'id' => $decoded['sub'] ?? '',
        'sub' => $decoded['sub'] ?? '',
        'email' => $decoded['email'] ?? '',
        'name' => 'Apple User',
    ];
}
