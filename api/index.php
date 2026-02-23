<?php
/**
 * Single serverless PHP entrypoint for Vercel.
 *
 * Suggested layout:
 * - /index.html                  (static homepage)
 * - /pages/about.php             (about page)
 * - /pages/contact.php           (contact endpoint/page)
 * - /api/index.php               (this router)
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$pagesDir = $projectRoot . '/pages';

$route = trim((string) ($_GET['route'] ?? ''), '/');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

/**
 * Sends a static file if it exists.
 */
function sendFile(string $absolutePath, string $contentType = 'text/html; charset=UTF-8'): void
{
    if (!is_file($absolutePath)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Not Found';
        return;
    }

    header('Content-Type: ' . $contentType);
    readfile($absolutePath);
}

// 1) Root / serves static HTML.
if ($route === '') {
    sendFile($projectRoot . '/index.html');
    return;
}

// 2) /about serves about.php.
if ($route === 'about') {
    $aboutPage = $pagesDir . '/about.php';

    if (is_file($aboutPage)) {
        require $aboutPage;
        return;
    }

    // Optional fallback if you keep legacy file locations temporarily.
    $legacyAbout = $projectRoot . '/about.php';
    if (is_file($legacyAbout)) {
        require $legacyAbout;
        return;
    }

    http_response_code(404);
    echo 'about.php not found.';
    return;
}

// 3) /contact supports POST (and optionally GET if your page renders a form).
if ($route === 'contact') {
    $contactPage = $pagesDir . '/contact.php';

    if (!in_array($method, ['GET', 'POST'], true)) {
        http_response_code(405);
        header('Allow: GET, POST');
        echo 'Method Not Allowed';
        return;
    }

    if (is_file($contactPage)) {
        require $contactPage;
        return;
    }

    // Optional fallback if file has not been moved yet.
    $legacyContact = $projectRoot . '/contact.php';
    if (is_file($legacyContact)) {
        require $legacyContact;
        return;
    }

    http_response_code(404);
    echo 'contact.php not found.';
    return;
}

// Any other route can be mapped here (or return 404).
http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Route not found: ' . $route;
