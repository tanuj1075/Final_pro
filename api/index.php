<?php
/**
 * Vercel serverless router — routes ALL PHP requests to their correct files.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$route = trim((string)($_GET['route'] ?? 'login.php'), '/');

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
    'about.php',
    'contact.php',
    'anime_hub.php',
    'anime_detail.php',
    'manage_anime.php',
    'manga_reader.php',
    'manga.php',
    'subscription.html',
    'video.html',
    'watch_yourname.php',
    'watch_aot.php',
    'watch_demonslayer.php',

    // ── Admin panel pages (inside src/pages/admin/) ────────────────────────
    'admin/dashboard.php',
    'admin/login.php',
    'admin/upload_video.php',
    'admin/manage_manga.php',
    'admin/admin_profile.php',
    'admin/user_detail.php',
    'admin/edit_anime.php',

    // ── Internal API endpoints (in src/services/api/) ─────────────────────
    'api/users.php',
    'api/manga.php',
    'api/upload_video.php',
    'api/upload_manga.php',
];

/** Pretty-URL aliases → real filenames */
$aliases = [
    ''               => 'login.php',
    'login'          => 'login.php',
    'signup'         => 'signup.php',
    'index'          => 'login.php',
    'index.php'      => 'login.php',
    'ash'            => 'ash.php',
    'user_panel'     => 'user_panel.php',
    'oauth_start'    => 'oauth_start.php',
    'oauth_callback' => 'oauth_callback.php',
    'anime_hub'      => 'anime_hub.php',
    'anime_detail'   => 'anime_detail.php',
    'manage_anime'   => 'manage_anime.php',
    'manga_reader'   => 'manga_reader.php',
    'manga'          => 'manga.php',
    'subscription'   => 'subscription.html',
    'video'          => 'video.html',
    'watch_yourname' => 'watch_yourname.php',
    'watch_aot'      => 'watch_aot.php',
    'watch_demonslayer' => 'watch_demonslayer.php',
    'admin/login'      => 'index.php',
    'admin/login.php'  => 'index.php',
    'admin/dashboard'       => 'admin/dashboard.php',
    'admin/upload_video'    => 'admin/upload_video.php',
    'admin/manage_manga'    => 'admin/manage_manga.php',
    'admin/admin_profile'   => 'admin/admin_profile.php',
    'admin/user_detail'     => 'admin/user_detail.php',
    'admin/edit_anime'      => 'admin/edit_anime.php',
    'admin/manage_anime'    => 'manage_anime.php',
    'admin/manage_anime.php' => 'manage_anime.php',
    'admin/ash.php'         => 'ash.php',
];

if (isset($aliases[$route])) {
    $route = $aliases[$route];
}

// Strip .php extension aliases (e.g. "anime_hub.php" → "anime_hub.php")
if (!str_contains($route, '/')) {
    $route = basename($route);
}

if (!in_array($route, $allowedRoutes, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Route not found: $route";
    return;
}

// ── Resolve target file path ──────────────────────────────────────────────────
// API endpoints live in src/services/api/
if (str_starts_with($route, 'api/')) {
    $target = $projectRoot . '/src/services/' . $route;
} else {
    $target = $projectRoot . '/src/pages/' . $route;
}

if (!is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "File not found: $route";
    return;
}

require $target;
