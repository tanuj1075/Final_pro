<?php
/**
 * Admin entry point — delegates everything to AdminController.
 * Loaded by api/index.php for routes: admin/, admin/login, admin/dashboard etc.
 */
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';

secure_session_start();

use App\Controllers\AdminController;
use App\Database\Connection;
use App\Repositories\AnimeRepository;
use App\Repositories\UserRepository;

global $ADMIN_USERNAME, $ADMIN_PASSWORD, $PASSWORD_HASH, $adminCredentialsConfigured, $isUsingDefaultAdminCredentials;

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

$PASSWORD_HASH = getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$YourHashHere';

$profileFile = __DIR__ . '/../../data/admin_override.json';
if (file_exists($profileFile)) {
    $override = json_decode(file_get_contents($profileFile), true);
    if (!empty($override['username'])) $ADMIN_USERNAME = $override['username'];
    if (!empty($override['password'])) {
        if (strpos($override['password'], '$2y$') === 0 || strpos($override['password'], '$2b$') === 0) {
            $PASSWORD_HASH = $override['password'];
            $ADMIN_PASSWORD = null;
        } else {
            $ADMIN_PASSWORD = $override['password'];
        }
    }
}

try {
    $db = Connection::getInstance();
    $userRepo = new UserRepository($db);
    $animeRepo = new AnimeRepository($db);
    $controller = new AdminController($animeRepo, $userRepo);
    $controller->handleRequest();
} catch (Exception $e) {
    error_log("Admin Critical Error: " . $e->getMessage());
    echo "A critical error occurred. Please check the logs.";
}
