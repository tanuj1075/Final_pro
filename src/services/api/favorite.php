<?php
require_once __DIR__ . '/../../utils/security.php';
require_once __DIR__ . '/../../utils/bootstrap.php';
secure_session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId  = (int)($_SESSION['user_id'] ?? 0);
$animeId = (int)($_POST['anime_id'] ?? 0);

if (!$userId || !$animeId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $db = \App\Database\Connection::getInstance();

    // Ensure tables exist (idempotent)
    $db->exec("CREATE TABLE IF NOT EXISTS user_favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        anime_id INTEGER NOT NULL,
        added_at TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE (user_id, anime_id)
    )");

    // Check if already favorited
    $stmt = $db->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND anime_id = ?");
    $stmt->execute([$userId, $animeId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove from favorites (toggle off)
        $del = $db->prepare("DELETE FROM user_favorites WHERE user_id = ? AND anime_id = ?");
        $del->execute([$userId, $animeId]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        // Add to favorites (toggle on)
        $ins = $db->prepare("INSERT INTO user_favorites (user_id, anime_id) VALUES (?, ?)");
        $ins->execute([$userId, $animeId]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
