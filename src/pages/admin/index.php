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

try {
    $db = Connection::getInstance();
    $profile = $db->query("SELECT username, password_hash FROM admin_profile WHERE id = 1")->fetch();
    if ($profile) {
        if (!empty($profile['username'])) {
            $ADMIN_USERNAME = (string)$profile['username'];
        }
        if (!empty($profile['password_hash'])) {
            $PASSWORD_HASH = (string)$profile['password_hash'];
            $ADMIN_PASSWORD = null;
        }
    }

    $userRepo = new UserRepository($db);
    $animeRepo = new AnimeRepository($db);
    $controller = new AdminController($animeRepo, $userRepo);
    $controller->handleRequest();
} catch (Exception $e) {
    error_log("Admin Critical Error: " . $e->getMessage());
    http_response_code(500);
    echo "A critical error occurred. Please check the logs.";
}
