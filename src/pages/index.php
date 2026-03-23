<?php
require_once __DIR__ . '/../utils/bootstrap.php';
require_once __DIR__ . '/../utils/security.php';
secure_session_start();

use App\Controllers\AdminController;
use App\Database\Connection;
use App\Repositories\AnimeRepository;
use App\Repositories\UserRepository;

// ===================== CONFIGURATION =====================
$envAdminUsername = trim((string)(getenv('ADMIN_USERNAME') ?: ''));
$envAdminPassword = (string)(getenv('ADMIN_PASSWORD') ?: '');

$allowDefaultCredentials = (getenv('ALLOW_DEFAULT_ADMIN_CREDENTIALS') === '1');
$localHosts = ['127.0.0.1', '::1', 'localhost'];
$serverAddr = trim((string)($_SERVER['SERVER_ADDR'] ?? ''));
$remoteAddr = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
if (in_array($serverAddr, $localHosts, true) || in_array($remoteAddr, $localHosts, true)) {
    $allowDefaultCredentials = true;
}

$isUsingDefaultAdminCredentials = ($envAdminUsername === '' || $envAdminPassword === '');
$ADMIN_USERNAME = $envAdminUsername !== '' ? $envAdminUsername : 'admin';
$ADMIN_PASSWORD = $envAdminPassword !== '' ? $envAdminPassword : 'rkmb123#';
$adminCredentialsConfigured = !$isUsingDefaultAdminCredentials || $allowDefaultCredentials;

// Optional hash override for production (set ADMIN_PASSWORD_HASH in env)
$PASSWORD_HASH = getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$YourHashHere';
// =========================================================

try {
    $db = Connection::getInstance();
    $userRepo = new UserRepository($db);
    $animeRepo = new AnimeRepository($db);
    
    $controller = new AdminController($animeRepo, $userRepo);
    $controller->handleRequest();
} catch (Exception $e) {
    error_log("Critical Error: " . $e->getMessage());
    echo "A critical application error occurred. Please check the error logs.";
}
