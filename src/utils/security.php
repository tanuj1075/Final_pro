<?php

function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    // Use the array form for session_set_cookie_params (PHP 7.3+).
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

/**
 * Backward-compatible helper for templates that still call generate_oauth_state().
 * OAuth flow state is generated/validated in oauth_start.php and oauth_callback.php.
 */
function generate_oauth_state() {
    return bin2hex(random_bytes(32));
}

function destroy_session_and_cookie() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
function resolve_asset_url(?string $path, string $type = 'images'): string
{
    if (empty($path)) {
        return '../assets/images/icon.png';
    }
    
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
        return $path;
    }
    
    return "../assets/{$type}/{$path}";
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

    throw new RuntimeException('Missing OAuth state secret configuration.');
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
/**
 * Check if the currently logged-in user is still active and approved.
 * If not, destroy the session and redirect to login.
 */
function check_user_active() {
    if (session_status() === PHP_SESSION_NONE) {
        secure_session_start();
    }

    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        return;
    }

    if (!isset($_SESSION['user_id'])) {
        return;
    }

    try {
        if (!class_exists(\App\Database\Connection::class, false)) {
            require_once __DIR__ . '/bootstrap.php';
        }

        $db = \App\Database\Connection::getInstance();
        $stmt = $db->prepare("SELECT is_active, is_approved FROM admin_panel_siteuser WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || (int)$user['is_active'] !== 1 || (int)$user['is_approved'] !== 1) {
            destroy_session_and_cookie();
            $errorMsg = !$user ? 'Account not found.' : ((int)$user['is_active'] !== 1 ? 'Your account has been disabled by admin.' : 'Your account is pending approval.');
            header('Location: login.php?error=' . urlencode($errorMsg));
            exit;
        }
    } catch (Exception $e) {
        // Silently fail if database is down, or log it
        error_log('Session validation failed: ' . $e->getMessage());
    }
}
