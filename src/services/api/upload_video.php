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

$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$genre       = trim($_POST['genre'] ?? '');
$releaseYear = !empty($_POST['release_year']) ? (int)$_POST['release_year'] : null;
$uploadType  = $_POST['upload_type'] ?? 'movie';

if (!$title && $uploadType !== 'episode') {
    echo json_encode(['success' => false, 'message' => 'Title is required.']); exit;
}

// ── VALIDATE files exist ──────────────────────────────────────────────────────
$hasFile = isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK;
$hasUrl  = trim($_POST['video_url'] ?? '') !== '';
if (!$hasFile && !$hasUrl) {
    echo json_encode(['success' => false, 'message' => 'Provide a video file or an external URL.']); exit;
}

// ── THUMBNAIL ─────────────────────────────────────────────────────────────────
$thumbUrl = '';
if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
    $allowedImg = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($_FILES['thumbnail']['type'], $allowedImg)) {
        echo json_encode(['success' => false, 'message' => 'Invalid thumbnail format (JPG/PNG/WEBP only).']); exit;
    }
    if ($_FILES['thumbnail']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Thumbnail exceeds 5 MB.']); exit;
    }

    if (cloudinary_credentials_set()) {
        $result = cloudinary_upload_file($_FILES['thumbnail'], 'anime/thumbnails', 'image');
        if (!$result['success']) { echo json_encode($result); exit; }
        $thumbUrl = $result['url'];
    } else {
        // Local fallback (only works locally, not on Vercel)
        $thumbDir = __DIR__ . '/../../../uploads/thumbnails/';
        if (!is_dir($thumbDir)) @mkdir($thumbDir, 0755, true);
        $fn = uniqid('thumb_') . '.' . pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbDir . $fn)) {
            $thumbUrl = '/uploads/thumbnails/' . $fn;
        }
    }
}

// ── VIDEO ─────────────────────────────────────────────────────────────────────
$videoUrl = '';
if ($hasFile) {
    $allowedVid = ['video/mp4', 'video/webm', 'video/x-matroska', 'video/quicktime'];
    if (!in_array($_FILES['video_file']['type'], $allowedVid)) {
        echo json_encode(['success' => false, 'message' => 'Invalid video format (MP4/MKV/WEBM only).']); exit;
    }

    if (cloudinary_credentials_set()) {
        $result = cloudinary_upload_file($_FILES['video_file'], 'anime/videos', 'video');
        if (!$result['success']) { echo json_encode($result); exit; }
        $videoUrl = $result['url'];
    } else {
        $videoDir = __DIR__ . '/../../../uploads/videos/';
        if (!is_dir($videoDir)) @mkdir($videoDir, 0755, true);
        $fn = uniqid('vid_') . '.' . pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['video_file']['tmp_name'], $videoDir . $fn)) {
            $videoUrl = '/uploads/videos/' . $fn;
        }
    }
} else {
    $videoUrl = trim($_POST['video_url']);
}

// ── DATABASE ──────────────────────────────────────────────────────────────────
try {
    $db = Connection::getInstance();

    // ── EPISODE upload ────────────────────────────────────────────────────────
    if ($uploadType === 'episode') {
        $animeId    = (int)($_POST['anime_id'] ?? 0);
        $episodeNum = (float)($_POST['episode_number'] ?? 0);
        if (!$animeId || !$episodeNum) {
            echo json_encode(['success' => false, 'message' => 'Series and Episode Number are required.']); exit;
        }
        $stmt = $db->prepare("INSERT INTO admin_panel_episode (anime_id, episode_number, title, thumbnail_url, video_url)
                              VALUES (:aid, :enum, :title, :thumb, :vid)");
        $stmt->execute(['aid' => $animeId, 'enum' => $episodeNum, 'title' => $title ?: null, 'thumb' => $thumbUrl ?: null, 'vid' => $videoUrl]);

        // Bump episode count
        $db->prepare("UPDATE admin_panel_anime_detail SET total_episodes = total_episodes + 1 WHERE anime_id = :aid")
           ->execute(['aid' => $animeId]);

        echo json_encode(['success' => true, 'message' => "Episode $episodeNum uploaded and saved!"]); exit;
    }

    // ── MOVIE upload ──────────────────────────────────────────────────────────
    $db->prepare("INSERT INTO admin_panel_anime (title, status, rating, created_at, type, release_year)
                  VALUES (:title, 'published', 0, datetime('now'), 'Movie', :ryear)")
       ->execute(['title' => $title, 'ryear' => $releaseYear]);
    $animeId = (int)$db->lastInsertId();

    $db->prepare("INSERT INTO admin_panel_anime_detail (anime_id, synopsis, poster_url, stream_url, total_episodes)
                  VALUES (:aid, :syn, :poster, :stream, 1)")
       ->execute(['aid' => $animeId, 'syn' => $description, 'poster' => $thumbUrl, 'stream' => $videoUrl]);

    // Genres
    if ($genre) {
        foreach (array_map('trim', explode(',', $genre)) as $gName) {
            if (!$gName) continue;
            $db->prepare("INSERT OR IGNORE INTO admin_panel_genre (name) VALUES (:name)")->execute(['name' => $gName]);
            $gStmt = $db->prepare("SELECT id FROM admin_panel_genre WHERE name = :name");
            $gStmt->execute(['name' => $gName]);
            $gId = $gStmt->fetchColumn();
            if ($gId) {
                $db->prepare("INSERT OR IGNORE INTO admin_panel_anime_genres (anime_id, genre_id) VALUES (:aid, :gid)")
                   ->execute(['aid' => $animeId, 'gid' => $gId]);
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Movie "' . htmlspecialchars($title) . '" uploaded successfully!']);
} catch (Exception $e) {
    error_log('upload_video error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}
