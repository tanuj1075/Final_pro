<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../utils/bootstrap.php';
use App\Database\Connection;
use App\Repositories\UserRepository;
use App\Repositories\OAuthRepository;

$config = require __DIR__ . '/oauth_config.php';
$provider = $_GET['provider'] ?? ($_POST['provider'] ?? '');

if (!isset($config[$provider])) {
    header('Location: login.php?error=Unsupported callback provider.');
    exit;
}

$providerError = trim((string)($_GET['error'] ?? ($_POST['error'] ?? '')));
if ($providerError !== '') {
    $providerDescription = trim((string)($_GET['error_description'] ?? ($_POST['error_description'] ?? '')));
    $errorMessage = 'OAuth provider returned an error: ' . $providerError;
    if ($providerDescription !== '') {
        $errorMessage .= ' (' . $providerDescription . ')';
    }
    header('Location: login.php?error=' . urlencode($errorMessage));
    exit;
}

$state = trim((string)($_GET['state'] ?? ($_POST['state'] ?? '')));
$stateCookieName = 'oauth_state_' . $provider;
$stateCookieValue = $_COOKIE[$stateCookieName] ?? '';
try {
    $statePayload = parse_oauth_state_cookie_value($stateCookieValue);
    // Always clear one-time state cookie once callback is reached.
    setcookie($stateCookieName, '', oauth_cookie_options(time() - 3600));
} catch (Throwable $e) {
    error_log('OAuth callback state processing failed: ' . $e->getMessage());
    header('Location: login.php?error=' . urlencode('OAuth state validation unavailable due to server configuration.'));
    exit;
}

if (!is_array($statePayload)) {
    header('Location: login.php?error=Missing or invalid OAuth state cookie. Please try again.');
    exit;
}

$expectedState = (string)($statePayload['state'] ?? '');
$stateProvider = (string)($statePayload['provider'] ?? '');
$stateExp = (int)($statePayload['exp'] ?? 0);
$redirectUri = (string)($statePayload['redirect_uri'] ?? '');

if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    header('Location: login.php?error=Invalid OAuth state. Please try again.');
    exit;
}

if ($stateProvider !== $provider || $stateExp < time()) {
    header('Location: login.php?error=OAuth state expired or provider mismatch. Please try again.');
    exit;
}

if ($redirectUri === '') {
    $redirectUri = build_app_url('/oauth_callback.php?provider=' . urlencode($provider));
}

$code = trim((string)($_GET['code'] ?? ($_POST['code'] ?? '')));
if ($code === '') {
    header('Location: login.php?error=OAuth code missing.');
    exit;
}

$providerConfig = $config[$provider];
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
if (!is_array($tokenData)) {
    header('Location: login.php?error=' . urlencode('OAuth token response is not valid JSON.'));
    exit;
}

$accessToken = trim((string)($tokenData['access_token'] ?? ''));
$idToken = trim((string)($tokenData['id_token'] ?? ''));

if ($provider === 'google' && ($accessToken === '' || $idToken === '')) {
    header('Location: login.php?error=Google token response missing access_token or id_token.');
    exit;
}

if ($provider !== 'apple' && $accessToken === '') {
    header('Location: login.php?error=OAuth token missing.');
    exit;
}

$profile = null;
if ($provider === 'google') {
    $validatedIdToken = validateGoogleIdToken(
        $idToken,
        (string)$providerConfig['client_id'],
        (string)($providerConfig['issuer'] ?? 'https://accounts.google.com')
    );

    if (!$validatedIdToken['ok']) {
        header('Location: login.php?error=' . urlencode('Google ID token validation failed: ' . $validatedIdToken['error']));
        exit;
    }

    $claims = $validatedIdToken['claims'];
    $profile = [
        'sub' => (string)($claims['sub'] ?? ''),
        'id' => (string)($claims['sub'] ?? ''),
        'email' => strtolower(trim((string)($claims['email'] ?? ''))),
        'email_verified' => (bool)($claims['email_verified'] ?? false),
        'name' => trim((string)($claims['name'] ?? '')),
    ];
} elseif ($provider === 'facebook') {
    $profile = fetchJsonWithBearer($providerConfig['userinfo_url'], $accessToken);
} elseif ($provider === 'apple') {
    $profile = decodeAppleIdToken($idToken);
}

if (!$profile) {
    header('Location: login.php?error=Unable to fetch social profile.');
    exit;
}

$email = strtolower(trim((string)($profile['email'] ?? '')));
$name = trim((string)($profile['name'] ?? ''));
$providerId = trim((string)($profile['id'] ?? ($profile['sub'] ?? '')));

if ($provider === 'google' && ($profile['email_verified'] ?? false) !== true) {
    header('Location: login.php?error=Google account email is not verified.');
    exit;
}

if ($email === '') {
    header('Location: login.php?error=No email returned by provider.');
    exit;
}

if ($providerId === '') {
    header('Location: login.php?error=No provider user ID returned by provider.');
    exit;
}

try {
    $db = Connection::getInstance();
    $userRepo = new UserRepository($db);
    $oauthRepo = new OAuthRepository($db);
    $userByIdentity = $oauthRepo->getUserByOAuthIdentity($provider, $providerId);
    $userByEmail = $userRepo->getUserByEmail($email);

    if ($userByIdentity && $userByEmail && (int)$userByIdentity['id'] !== (int)$userByEmail['id']) {
        header('Location: login.php?error=OAuth account conflict detected. Please contact support.');
        exit;
    }

    $user = $userByIdentity ?: $userByEmail;

    if (!$user) {
        $usernameBase = $name !== '' ? preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($name)) : explode('@', $email)[0];
        $usernameBase = trim((string)$usernameBase, '_');
        if ($usernameBase === '') {
            $usernameBase = $provider . '_user';
        }

        $username = $userRepo->generateUniqueUsername($usernameBase);
        $created = $oauthRepo->createOAuthUser($username, $email, $provider, $providerId);
        if (!$created) {
            header('Location: login.php?error=Unable to create OAuth account.');
            exit;
        }
        $user = $userRepo->getUserByEmail($email);
    } else {
        $oauthRepo->linkOAuthIdentity((int)$user['id'], $provider, $providerId);
    }

    if (!$user || (int)$user['is_active'] !== 1) {
        header('Location: login.php?error=Account disabled by admin.');
        exit;
    }

    // OAuth-created users are auto-approved, but keep this guard for legacy rows.
    if ((int)$user['is_approved'] !== 1) {
        header('Location: login.php?error=Account pending admin approval.');
        exit;
    }

    $userRepo->touchLastLogin((int)$user['id']);

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
    $body = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query($formData),
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
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
    }

    return ['ok' => $body !== false && $body !== null, 'body' => $body ?: ''];
}

function fetchJsonWithBearer($url, $token) {
    $body = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer {$token}"],
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$token}\r\n",
                'timeout' => 15,
            ],
        ];
        $context = stream_context_create($opts);
        $body = @file_get_contents($url, false, $context);
    }

    if ($body === false || $body === null) {
        return null;
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

function decodeAppleIdToken($idToken) {
    if (!$idToken || substr_count($idToken, '.') < 2) {
        return null;
    }

    $jwtParts = decodeJwtParts($idToken);
    if ($jwtParts === null || !isset($jwtParts['payload']) || !is_array($jwtParts['payload'])) {
        return null;
    }

    $decoded = $jwtParts['payload'];
    return [
        'id' => $decoded['sub'] ?? '',
        'sub' => $decoded['sub'] ?? '',
        'email' => $decoded['email'] ?? '',
        'name' => 'Apple User',
    ];
}

function validateGoogleIdToken($idToken, $expectedAudience, $expectedIssuer) {
    $jwtParts = decodeJwtParts($idToken);
    if ($jwtParts === null) {
        return ['ok' => false, 'error' => 'Malformed JWT token'];
    }

    $header = $jwtParts['header'];
    $payload = $jwtParts['payload'];

    $alg = (string)($header['alg'] ?? '');
    $kid = (string)($header['kid'] ?? '');
    if ($alg !== 'RS256' || $kid === '') {
        return ['ok' => false, 'error' => 'Unsupported Google JWT header'];
    }

    $jwks = fetchGoogleJwks();
    if ($jwks === null) {
        return ['ok' => false, 'error' => 'Unable to fetch Google JWKS'];
    }

    $jwk = null;
    foreach ($jwks['keys'] ?? [] as $candidate) {
        if (($candidate['kid'] ?? '') === $kid) {
            $jwk = $candidate;
            break;
        }
    }

    if (!is_array($jwk)) {
        return ['ok' => false, 'error' => 'No matching Google JWK found'];
    }

    if (!verifyJwtSignatureWithJwk($jwtParts['signed_part'], $jwtParts['signature'], $jwk)) {
        return ['ok' => false, 'error' => 'Google JWT signature verification failed'];
    }

    $issuer = (string)($payload['iss'] ?? '');
    if (!in_array($issuer, [$expectedIssuer, 'accounts.google.com', 'https://accounts.google.com'], true)) {
        return ['ok' => false, 'error' => 'Invalid issuer'];
    }

    $aud = $payload['aud'] ?? '';
    $audiences = is_array($aud) ? $aud : [$aud];
    if (!in_array($expectedAudience, $audiences, true)) {
        return ['ok' => false, 'error' => 'Invalid audience'];
    }

    $exp = (int)($payload['exp'] ?? 0);
    $iat = (int)($payload['iat'] ?? 0);
    $now = time();

    if ($exp <= $now - 60) {
        return ['ok' => false, 'error' => 'ID token expired'];
    }

    if ($iat > $now + 300) {
        return ['ok' => false, 'error' => 'Invalid iat claim'];
    }

    if (empty($payload['sub']) || empty($payload['email'])) {
        return ['ok' => false, 'error' => 'Missing required Google claims'];
    }

    if (($payload['email_verified'] ?? false) !== true) {
        return ['ok' => false, 'error' => 'Unverified Google email'];
    }

    return ['ok' => true, 'claims' => $payload];
}

function fetchGoogleJwks() {
    $url = 'https://www.googleapis.com/oauth2/v3/certs';
    $body = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
    } else {
        $opts = ['http' => ['method' => 'GET', 'timeout' => 10]];
        $context = stream_context_create($opts);
        $body = @file_get_contents($url, false, $context);
    }

    if (!is_string($body) || $body === '') {
        return null;
    }

    $jwks = json_decode($body, true);
    if (!is_array($jwks) || !isset($jwks['keys']) || !is_array($jwks['keys'])) {
        return null;
    }

    return $jwks;
}

function decodeJwtParts($jwt) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }

    $header = json_decode(base64UrlDecode($parts[0]), true);
    $payload = json_decode(base64UrlDecode($parts[1]), true);
    $signature = base64UrlDecode($parts[2]);

    if (!is_array($header) || !is_array($payload) || !is_string($signature)) {
        return null;
    }

    return [
        'header' => $header,
        'payload' => $payload,
        'signature' => $signature,
        'signed_part' => $parts[0] . '.' . $parts[1],
    ];
}

function verifyJwtSignatureWithJwk($signedPart, $signature, $jwk) {
    $n = base64UrlDecode((string)($jwk['n'] ?? ''));
    $e = base64UrlDecode((string)($jwk['e'] ?? ''));

    if ($n === '' || $e === '') {
        return false;
    }

    $publicKeyPem = jwkToPem($n, $e);
    if ($publicKeyPem === null) {
        return false;
    }

    $verifyResult = openssl_verify($signedPart, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256);
    return $verifyResult === 1;
}

function jwkToPem($modulus, $exponent) {
    $modulusEnc = asn1EncodeInteger($modulus);
    $exponentEnc = asn1EncodeInteger($exponent);
    $rsaPublicKey = asn1EncodeSequence($modulusEnc . $exponentEnc);

    // rsaEncryption OID + NULL
    $algorithmIdentifier = hex2bin('300d06092a864886f70d0101010500');
    $subjectPublicKey = asn1EncodeBitString($rsaPublicKey);
    $subjectPublicKeyInfo = asn1EncodeSequence($algorithmIdentifier . $subjectPublicKey);

    $pem = "-----BEGIN PUBLIC KEY-----\n";
    $pem .= chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n");
    $pem .= "-----END PUBLIC KEY-----\n";

    return $pem;
}

function asn1EncodeInteger($value) {
    if ($value === '') {
        $value = "\x00";
    }

    if (ord($value[0]) > 0x7f) {
        $value = "\x00" . $value;
    }

    return "\x02" . asn1EncodeLength(strlen($value)) . $value;
}

function asn1EncodeBitString($value) {
    $value = "\x00" . $value;
    return "\x03" . asn1EncodeLength(strlen($value)) . $value;
}

function asn1EncodeSequence($value) {
    return "\x30" . asn1EncodeLength(strlen($value)) . $value;
}

function asn1EncodeLength($length) {
    if ($length <= 0x7f) {
        return chr($length);
    }

    $lenBytes = '';
    while ($length > 0) {
        $lenBytes = chr($length & 0xff) . $lenBytes;
        $length >>= 8;
    }

    return chr(0x80 | strlen($lenBytes)) . $lenBytes;
}

function base64UrlDecode($input) {
    $padding = 4 - (strlen($input) % 4);
    if ($padding < 4) {
        $input .= str_repeat('=', $padding);
    }

    return base64_decode(strtr($input, '-_', '+/')) ?: '';
}
