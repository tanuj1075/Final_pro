<?php

function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    // PHP < 7.3 does not support array form for session_set_cookie_params.
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $isHttps, true);
    }

    session_start();
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function is_valid_csrf_token($token) {
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function destroy_session_and_cookie() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function oauth_cookie_options($expiresAt) {
    return [
        'expires' => (int)$expiresAt,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None',
    ];
}

function get_oauth_state_secret() {
    $configured = trim((string)getenv('OAUTH_STATE_SECRET'));
    if ($configured !== '') {
        return $configured;
    }

    $clientSecret = trim((string)getenv('GOOGLE_CLIENT_SECRET'));
    if ($clientSecret !== '') {
        return hash('sha256', $clientSecret . '|oauth-state-secret', false);
    }

    // Last-resort fallback. Works functionally but should not be used in production.
    return hash('sha256', __FILE__ . '|development-fallback', false);
}

function build_oauth_state_cookie_value(array $payload) {
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $encodedPayload = rtrim(strtr(base64_encode((string)$json), '+/', '-_'), '=');
    $signature = hash_hmac('sha256', $encodedPayload, get_oauth_state_secret());

    return $encodedPayload . '.' . $signature;
}

function parse_oauth_state_cookie_value($cookieValue) {
    if (!is_string($cookieValue) || strpos($cookieValue, '.') === false) {
        return null;
    }

    [$encodedPayload, $providedSig] = explode('.', $cookieValue, 2);
    if ($encodedPayload === '' || $providedSig === '') {
        return null;
    }

    $expectedSig = hash_hmac('sha256', $encodedPayload, get_oauth_state_secret());
    if (!hash_equals($expectedSig, $providedSig)) {
        return null;
    }

    $decoded = base64_decode(strtr($encodedPayload, '-_', '+/'), true);
    if (!is_string($decoded) || $decoded === '') {
        return null;
    }

    $payload = json_decode($decoded, true);
    return is_array($payload) ? $payload : null;
}

function get_app_base_url() {
    $configured = trim((string)(getenv('APP_BASE_URL') ?: ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))) === 'https')
        ? 'https'
        : 'http';

    $rawHost = trim((string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000')));
    $host = trim(explode(',', $rawHost)[0]);

    // Some proxies can send host with scheme; normalize to host[:port].
    if (strpos($host, '://') !== false) {
        $parsedHost = parse_url($host, PHP_URL_HOST);
        $parsedPort = parse_url($host, PHP_URL_PORT);
        if (is_string($parsedHost) && $parsedHost !== '') {
            $host = $parsedHost . ($parsedPort ? ':' . $parsedPort : '');
        }
    }

    // Basic host hardening to prevent malformed redirect URL generation.
    if ($host !== '' && !preg_match('/^[A-Za-z0-9\.-]+(?::\d{1,5})?$/', $host)) {
        $host = '';
    }

    if ($host !== '' && strpos($host, ':') === false) {
        $forwardedPort = trim((string)($_SERVER['HTTP_X_FORWARDED_PORT'] ?? ''));
        if (ctype_digit($forwardedPort)) {
            $forwardedPortInt = (int)$forwardedPort;
            $isDefaultPort = ($scheme === 'https' && $forwardedPortInt === 443) || ($scheme === 'http' && $forwardedPortInt === 80);
            if (!$isDefaultPort && $forwardedPortInt > 0 && $forwardedPortInt <= 65535) {
                $host .= ':' . $forwardedPortInt;
            }
        }
    }

    if ($host === '') {
        $host = '127.0.0.1:8000';
    }

    return $scheme . '://' . $host;
}

function build_app_url($pathWithQuery) {
    $path = '/' . ltrim((string)$pathWithQuery, '/');
    return get_app_base_url() . $path;
}
