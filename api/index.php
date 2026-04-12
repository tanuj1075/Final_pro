<?php
/**
 * Vercel serverless router — routes ALL PHP requests to their correct files.
 */

declare(strict_types=1);

$isProduction = (getenv('VERCEL_ENV') === 'production' || getenv('APP_ENV') === 'production');

if ($isProduction) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        error_log("Error: [$errno] $errstr in $errfile on line $errline");
    });
    
    set_exception_handler(function($e) {
        error_log("Exception: " . $e->getMessage());
        http_response_code(500);
        echo "Internal Server Error. Please try again later.";
        exit;
    });

    if (isset($_GET['access_key'])) {
        setcookie('prod_access_key', $_GET['access_key'], time() + 86400 * 30, "/");
        $_COOKIE['prod_access_key'] = $_GET['access_key'];
    }
    
    $expectedKey = getenv('PRODUCTION_ACCESS_KEY');
    if ($expectedKey && ($_COOKIE['prod_access_key'] ?? '') !== $expectedKey) {
        http_response_code(403);
        echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Access Restricted</title>
  <style>
    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f4f4f4; }
    h1 { color: #333; }
    p { color: #666; }
  </style>
</head>
<body>
  <h1>Access Restricted</h1>
  <p>This section is not available without proper access.</p>
</body>
</html>';
        exit;
    }
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$projectRoot = dirname(__DIR__);

$route = $_GET['route'] ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($route === '/' || $route === '/api/index.php' || $route === '/index.php') {
    $route = 'login.php';
}
$route = trim((string)$route, '/');

// Support malformed links like "index.php/admin/dashboard" by canonicalizing
// them to "admin/dashboard" before alias/whitelist checks.
if (str_starts_with($route, 'index.php/')) {
    $route = substr($route, strlen('index.php/'));
}

/**
 * Whitelist of all allowed page/API files (relative to src/pages/ OR src/services/).
 */
$allowedRoutes = [
    // ── User-facing pages ──────────────────────────────────────────────────
    'index.php',
    'login.php',
    'signup.php',
    'ash.php',
    'user_panel.php',
    'oauth_start.php',
    'oauth_callback.php',
    'anime.php',
    'anime_hub.php',
    'anime_detail.php',
    'manage_anime.php',
    'manga_reader.php',
    'manga.php',
    'subscription.php',
    'video.html',
    'watch.php',
    'watch_yourname.php',
    'watch_aot.php',
    'watch_demonslayer.php',

    // ── Admin panel pages (inside src/pages/admin/) ────────────────────────
    'admin/index.php',
    'admin/dashboard.php',
    'admin/login.php',
    'admin/upload_video.php',
    'admin/manage_manga.php',
    'admin/admin_profile.php',
    'admin/edit_anime.php',
    'admin/user_detail.php',

    // ── Internal API endpoints (in src/services/api/) ─────────────────────
    'api/users.php',
    'api/manga.php',
    'api/upload_video.php',
    'api/upload_manga.php',
];

/** Pretty-URL aliases → real filenames */
$aliases = [
    ''               => 'ash.php',
    '/'              => 'ash.php',
    'login'          => 'login.php',
    'signup'         => 'signup.php',
    'ash'            => 'ash.php',
    'profile'        => 'user_panel.php',
    'anime'          => 'anime.php',
    'hub'            => 'anime_hub.php',
    'manga'          => 'manga.php',
    'watch'                 => 'watch.php',
    'admin'                 => 'admin/index.php',
    'admin/'                => 'admin/index.php',
    'subscription'          => 'subscription.php',
    'video'                 => 'video.html',
    'watch_yourname'        => 'watch_yourname.php',
    'watch_aot'             => 'watch_aot.php',
    'watch_demonslayer'     => 'watch_demonslayer.php',
    'admin/login'           => 'admin/index.php',
    'admin/login.php'       => 'admin/index.php',
    'admin/dashboard'       => 'admin/dashboard.php',
    'admin/upload_video'    => 'admin/upload_video.php',
    'admin/manage_manga'    => 'admin/manage_manga.php',
    'admin/admin_profile'   => 'admin/admin_profile.php',
    'admin/user_detail'     => 'admin/user_detail.php',
    'admin/edit_anime'      => 'admin/edit_anime.php',
    'admin/manage_anime'    => 'manage_anime.php',
    'admin/manage_anime.php'=> 'manage_anime.php',
    'admin/ash.php'         => 'ash.php',
];

// 1. Check for explicit aliases
if (isset($aliases[$route])) {
    $route = $aliases[$route];
}

// 2. Handle extension-less clean URLs for allowed pages
if (!str_contains($route, '.') && !in_array($route, $allowedRoutes, true)) {
    if (in_array($route . '.php', $allowedRoutes, true)) {
        $route .= '.php';
    } elseif (in_array($route . '.html', $allowedRoutes, true)) {
        $route .= '.html';
    }
}

// 3. Final Whitelist Check
if (!in_array($route, $allowedRoutes, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Route not found: $route";
    return;
}

// ── Resolve target file path ──────────────────────────────────────────────────
if (str_starts_with($route, 'api/')) {
    $target = $projectRoot . '/src/services/' . $route;
} else {
    $target = $projectRoot . '/src/pages/' . $route;
}

if (!is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "File not found: $route (Target: $target)";
    return;
}

require $target;
