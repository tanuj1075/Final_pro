<?php
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';
require_once __DIR__ . '/../../utils/cloudinary.php';
secure_session_start();

use App\Database\Connection;

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']); exit;
}

$db = Connection::getInstance();

// Ensure tables exist
try {
    $db->exec("CREATE TABLE IF NOT EXISTS admin_panel_manga (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT NULL,
        cover_url TEXT NULL,
        status TEXT NOT NULL DEFAULT 'Ongoing',
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS admin_panel_manga_chapter (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        manga_id INTEGER NOT NULL,
        chapter_number REAL NOT NULL,
        title TEXT NULL,
        file_url TEXT NOT NULL,
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        FOREIGN KEY (manga_id) REFERENCES admin_panel_manga(id) ON DELETE CASCADE
    )");
} catch (\Exception $e) { }

$action = $_POST['action'] ?? '';

// ── Helper: upload a generic file to Cloudinary or local ──────────────────────
function upload_file_smart(array $file, string $cloudFolder, string $localDir, string $localPrefix, string $resourceType = 'auto'): array
{
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Invalid file upload.'];
    }

    if (cloudinary_credentials_set()) {
        $result = cloudinary_upload_file($file, $cloudFolder, $resourceType);
        return $result; // ['success'=>bool, 'url'=>...|'message'=>...]
    }

    // Local fallback
    $absDir = __DIR__ . '/../../../uploads/' . $localDir . '/';
    if (!is_dir($absDir)) @mkdir($absDir, 0755, true);
    $fn = uniqid($localPrefix) . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (move_uploaded_file($file['tmp_name'], $absDir . $fn)) {
        return ['success' => true, 'url' => '/uploads/' . $localDir . '/' . $fn];
    }
    return ['success' => false, 'message' => 'File move failed.'];
}

// ── CREATE MANGA SERIES ───────────────────────────────────────────────────────
if ($action === 'create_manga') {
    $title = trim($_POST['title'] ?? '');
    if (!$title) { echo json_encode(['success' => false, 'message' => 'Title is required']); exit; }

    $coverUrl = '';
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
        $allowedImg = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['cover']['type'], $allowedImg)) {
            echo json_encode(['success' => false, 'message' => 'Invalid cover image (JPG/PNG/WEBP only).']); exit;
        }
        $result = upload_file_smart($_FILES['cover'], 'manga/covers', 'manga', 'cover_', 'image');
        if (!$result['success']) { echo json_encode($result); exit; }
        $coverUrl = $result['url'];
    }

    try {
        $stmt = $db->prepare("INSERT INTO admin_panel_manga (title, description, cover_url) VALUES (:title, :desc, :cover)");
        $stmt->execute(['title' => $title, 'desc' => trim($_POST['description'] ?? ''), 'cover' => $coverUrl]);
        echo json_encode(['success' => true, 'message' => 'Manga series "' . htmlspecialchars($title) . '" created!']);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

// ── UPLOAD CHAPTER ────────────────────────────────────────────────────────────
} elseif ($action === 'upload_chapter') {
    $mangaId    = (int)($_POST['manga_id'] ?? 0);
    $chapterNum = (float)($_POST['chapter_number'] ?? 0);
    if (!$mangaId || !$chapterNum) {
        echo json_encode(['success' => false, 'message' => 'Manga ID and Chapter Number required']); exit;
    }

    if (!isset($_FILES['chapter_file']) || $_FILES['chapter_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No valid chapter file provided']); exit;
    }

    $allowedTypes = ['application/zip', 'application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($_FILES['chapter_file']['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Supported: ZIP, PDF, JPG, PNG, WEBP.']); exit;
    }

    $result = upload_file_smart($_FILES['chapter_file'], 'manga/chapters', 'manga', "ch{$chapterNum}_", 'auto');
    if (!$result['success']) { echo json_encode($result); exit; }

    try {
        $stmt = $db->prepare("INSERT INTO admin_panel_manga_chapter (manga_id, chapter_number, title, file_url)
                              VALUES (:mid, :cnum, :title, :url)");
        $stmt->execute([
            'mid'   => $mangaId,
            'cnum'  => $chapterNum,
            'title' => trim($_POST['chapter_title'] ?? '') ?: "Chapter $chapterNum",
            'url'   => $result['url']
        ]);
        echo json_encode(['success' => true, 'message' => "Chapter $chapterNum uploaded successfully!"]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error saving chapter.']);
    }

// ── LIST MANGA (for dropdowns) ────────────────────────────────────────────────
} elseif ($action === 'list_manga') {
    try {
        $stmt = $db->query("SELECT id, title FROM admin_panel_manga ORDER BY title ASC");
        echo json_encode(['success' => true, 'mangaList' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'mangaList' => []]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
