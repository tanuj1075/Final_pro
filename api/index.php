<?php
/**
 * Vercel serverless router — routes ALL PHP requests to their correct files.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$route = trim((string)($_GET['route'] ?? 'login.php'), '/');

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

    // ── Admin panel pages (inside src/pages/admin/) ────────────────────────
    'admin/dashboard.php',
    'admin/login.php',
    'admin/upload_video.php',
    'admin/manage_manga.php',

    // ── Internal API endpoints (in src/services/api/) ─────────────────────
    'api/users.php',
    'api/manga.php',
    'api/upload_video.php',
    'api/upload_manga.php',
];

/** Pretty-URL aliases → real filenames */
$aliases = [
    ''               => 'index.php',
    'login'          => 'login.php',
    'signup'         => 'signup.php',
    'index'          => 'index.php',
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
    // Admin routes → root index.php handles admin login
    'admin'          => 'index.php',
    'admin/'         => 'index.php',
    'admin/index.php'=> 'index.php',
    'admin/manage_anime.php' => 'manage_anime.php',
    'admin/ash.php'  => 'ash.php',
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
