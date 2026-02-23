<?php
/**
 * Vercel serverless router for this repo.
 *
 * Why this file exists:
 * - Vercel executes PHP inside /api as serverless functions.
 * - Existing project PHP files still live in repo root.
 * - This router safely maps incoming URLs to known PHP files.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$route = trim((string)($_GET['route'] ?? 'login.php'), '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

/**
 * Small whitelist so arbitrary files cannot be included.
 * Add new PHP pages here when you create them.
 */
$allowedPhpFiles = [
    'index.php',
    'login.php',
    'signup.php',
    'ash.php',
    'user_panel.php',
    'oauth_start.php',
    'oauth_callback.php',
    'about.php',
    'contact.php',
];

/**
 * Pretty-URL aliases.
 * /about -> about.php
 * /contact -> contact.php
 */
$aliases = [
    '' => 'login.php',
    'admin' => 'index.php',
    'about' => 'about.php',
    'contact' => 'contact.php',
    'login' => 'login.php',
    'signup' => 'signup.php',
];

if (isset($aliases[$route])) {
    $route = $aliases[$route];
}

// If caller passes a path with query fragments, keep only file name.
$route = basename($route);

if (!in_array($route, $allowedPhpFiles, true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Route not found: ' . $route;
    return;
}

$target = $projectRoot . '/' . $route;
if (!is_file($target)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Mapped file not found: ' . $route;
    return;
}

// Optional method guard for /contact if you use a contact form endpoint.
if ($route === 'contact.php' && !in_array($method, ['GET', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, POST');
    echo 'Method Not Allowed';
    return;
}

require $target;
