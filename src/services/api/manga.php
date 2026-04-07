<?php
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();

use App\Database\Connection;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'list') {
    $db = Connection::getInstance();
    try {
        $stmt = $db->query("SELECT id, title, description, cover_url, status FROM admin_panel_manga ORDER BY id DESC");
        $mangas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mangas as &$m) {
            $chStmt = $db->prepare("SELECT chapter_number, title, file_url FROM admin_panel_manga_chapter WHERE manga_id = :id ORDER BY chapter_number ASC");
            $chStmt->execute(['id' => $m['id']]);
            $m['chapters'] = $chStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['success' => true, 'mangas' => $mangas]);
    } catch (\Exception $e) {
        echo json_encode(['success' => true, 'mangas' => []]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action required.']);
