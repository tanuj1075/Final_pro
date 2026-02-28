<?php

function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

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
