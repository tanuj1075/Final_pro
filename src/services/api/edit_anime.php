<?php
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();

use App\Database\Connection;

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']); exit;
}

$animeId     = (int)($_POST['anime_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$genresStr   = trim($_POST['genres'] ?? '');
$releaseYear = !empty($_POST['release_year']) ? (int)$_POST['release_year'] : null;
$posterUrl   = trim($_POST['poster_url'] ?? '');
$streamUrl   = trim($_POST['stream_url'] ?? '');

if (!$animeId || !$title) {
    echo json_encode(['success' => false, 'message' => 'Anime ID and Title are required.']); exit;
}

try {
    $db = Connection::getInstance();
    $db->beginTransaction();

    // Update basic info
    $stmt = $db->prepare("UPDATE admin_panel_anime SET title = :title, release_year = :ryear WHERE id = :id");
    $stmt->execute(['title' => $title, 'ryear' => $releaseYear, 'id' => $animeId]);

    // Update detail info
    $dStmt = $db->prepare("UPDATE admin_panel_anime_detail SET synopsis = :syn, poster_url = :poster, stream_url = :stream WHERE anime_id = :id");
    $dStmt->execute(['syn' => $description, 'poster' => $posterUrl, 'stream' => $streamUrl, 'id' => $animeId]);

    // Update Genres (Simple approach: clear and re-insert)
    $db->prepare("DELETE FROM admin_panel_anime_genres WHERE anime_id = :id")->execute(['id' => $animeId]);
    
    if ($genresStr) {
        $genres = array_map('trim', explode(',', $genresStr));
        foreach ($genres as $gName) {
            if (!$gName) continue;
            
            // Ensure genre exists in master table
            $db->prepare("INSERT OR IGNORE INTO admin_panel_genre (name) VALUES (:name)")->execute(['name' => $gName]);
            
            // Get genre ID
            $gIdStmt = $db->prepare("SELECT id FROM admin_panel_genre WHERE name = :name");
            $gIdStmt->execute(['name' => $gName]);
            $gId = $gIdStmt->fetchColumn();
            
            if ($gId) {
                $db->prepare("INSERT OR IGNORE INTO admin_panel_anime_genres (anime_id, genre_id) VALUES (:aid, :gid)")
                   ->execute(['aid' => $animeId, 'gid' => $gId]);
            }
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Anime library item updated successfully!']);
} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    error_log('edit_anime error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database update failed.']);
}
