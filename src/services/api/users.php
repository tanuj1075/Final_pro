<?php
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();

use App\Database\Connection;

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = Connection::getInstance();
    $stmt = $db->query(
        "SELECT
            id,
            username,
            email,
            is_active,
            status,
            last_login,
            last_logout,
            created_at,
            registration_ip,
            registration_user_agent,
            last_seen_ip,
            last_seen_user_agent
         FROM admin_panel_siteuser
         ORDER BY created_at DESC"
    );
    $users = $stmt->fetchAll();
    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
