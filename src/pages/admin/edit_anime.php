<?php
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: ../../admin?error=Admin login required');
    exit;
}

use App\Database\Connection;
$db = Connection::getInstance();

$animeId = (int)($_GET['id'] ?? 0);
if (!$animeId) {
    header('Location: dashboard.php?error=Missing anime ID');
    exit;
}

// Fetch basic info and detail
$stmt = $db->prepare("SELECT a.*, d.synopsis, d.poster_url, d.stream_url 
                      FROM admin_panel_anime a 
                      LEFT JOIN admin_panel_anime_detail d ON a.id = d.anime_id 
                      WHERE a.id = :id");
$stmt->execute(['id' => $animeId]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    header('Location: dashboard.php?error=Anime not found');
    exit;
}

// Fetch genres
$gStmt = $db->prepare("SELECT g.name FROM admin_panel_genre g 
                       JOIN admin_panel_anime_genres ag ON g.id = ag.genre_id 
                       WHERE ag.anime_id = :id");
$gStmt->execute(['id' => $animeId]);
$genresList = $gStmt->fetchAll(PDO::FETCH_COLUMN);
$genresStr = implode(', ', $genresList);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Content - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4b2b;
            --secondary: #ff416c;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --accent: #38bdf8;
            --muted: #94a3b8;
            --border: rgba(255,255,255,0.08);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        
        .sidebar { width: 260px; background: var(--card-bg); border-right: 1px solid var(--border); padding: 28px 20px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { display: flex; align-items: center; gap: 10px; margin-bottom: 40px; }
        .sidebar-header i { font-size: 28px; color: var(--primary); }
        .sidebar-header span { font-size: 18px; font-weight: 700; }
        
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 10px; color: var(--muted); text-decoration: none; margin-bottom: 6px; transition: all 0.2s; font-size: 14px; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 75, 43, 0.12); color: var(--primary); }
        
        .main { flex: 1; padding: 40px; overflow-y: auto; max-width: 900px; }
        .header { margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--muted); text-decoration: none; font-size: 14px; transition: color 0.2s; }
        .btn-back:hover { color: #fff; }
        
        .card { background: var(--card-bg); border-radius: 18px; padding: 28px; border: 1px solid var(--border); }
        .section-title { font-size: 18px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--primary); }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--muted); font-size: 13px; font-weight: 600; }
        .form-control { width: 100%; padding: 11px 16px; border-radius: 10px; border: 1px solid var(--border); background: rgba(0,0,0,0.2); color: #fff; font-size: 14px; font-family: inherit; transition: border 0.2s; }
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { resize: vertical; min-height: 120px; }
        
        .btn-save { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: #fff; border: none; padding: 14px 28px; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: opacity 0.2s; display: flex; align-items: center; gap: 8px; justify-content: center; width: 100%; margin-top: 20px; }
        .btn-save:hover { opacity: 0.9; }
        .btn-save:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; display: none; }
        .alert.success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; }
        .alert.error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #f87171; }
        
        .preview-box { background: rgba(0,0,0,0.3); border-radius: 10px; padding: 16px; border: 1px dashed var(--border); margin-top: 10px; display: flex; gap: 15px; align-items: center; }
        .preview-img { width: 60px; height: 80px; border-radius: 6px; object-fit: cover; background: #2d3748; }
        .preview-info { flex: 1; }
        .preview-info small { color: var(--muted); display: block; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-bolt"></i>
            <span>Admin Central</span>
        </div>
        <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
        <a href="upload_video.php" class="nav-item"><i class="fas fa-upload"></i> Upload Video</a>
        <a href="manage_manga.php" class="nav-item"><i class="fas fa-book-open"></i> Manage Manga</a>
        <a href="admin_profile.php" class="nav-item"><i class="fas fa-user-cog"></i> My Profile</a>
        <a href="../../pages/ash.php" class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> Visit Site</a>
        <a href="../index.php?action=logout" class="nav-item" style="margin-top: auto; color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main">
        <div class="header">
            <div>
                <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <h1 style="font-size: 24px; margin-top: 12px;">Edit Anime Library Item</h1>
            </div>
            <div class="badge-type" style="background: rgba(56,189,248,.1); color: var(--accent); padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                <?= htmlspecialchars($anime['type']) ?>
            </div>
        </div>

        <div id="statusAlert" class="alert"></div>

        <div class="card">
            <h3 class="section-title"><i class="fas fa-edit"></i> Item Details</h3>
            <form id="editForm">
                <input type="hidden" name="anime_id" value="<?= $animeId ?>">
                <input type="hidden" name="type" value="<?= htmlspecialchars($anime['type']) ?>">

                <div class="form-group">
                    <label>Content Title</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($anime['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Synopsis / Description</label>
                    <textarea name="description" class="form-control" placeholder="Brief summary..."><?= htmlspecialchars($anime['synopsis'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Genres (Comma separated)</label>
                        <input type="text" name="genres" class="form-control" value="<?= htmlspecialchars($genresStr) ?>" placeholder="Action, Adventure, Fantasy">
                    </div>
                    <div class="form-group">
                        <label>Release Year</label>
                        <input type="number" name="release_year" class="form-control" value="<?= htmlspecialchars($anime['release_year'] ?? '') ?>" placeholder="2024">
                    </div>
                </div>

                <div class="form-group">
                    <label>Poster / Image URL</label>
                    <input type="url" name="poster_url" class="form-control" value="<?= htmlspecialchars($anime['poster_url'] ?? '') ?>" placeholder="https://example.com/poster.jpg">
                    <?php if (!empty($anime['poster_url'])): ?>
                    <div class="preview-box">
                        <img src="<?= htmlspecialchars($anime['poster_url']) ?>" class="preview-img" onerror="this.src='https://via.placeholder.com/60x80?text=No+Image'">
                        <div class="preview-info">
                            <small>Current Image Preview</small>
                            <span style="font-size: 12px; color: var(--muted); word-break: break-all;"><?= basename($anime['poster_url']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Video Stream URL</label>
                    <input type="url" name="stream_url" class="form-control" value="<?= htmlspecialchars($anime['stream_url'] ?? '') ?>" placeholder="https://example.com/movie.mp4">
                    <small style="color: var(--muted); font-size: 11px; margin-top: 4px; display: block;">Link to MP4, MKV, or an embeddable player URL.</small>
                </div>

                <button type="submit" class="btn-save" id="saveBtn"><i class="fas fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        const form = document.getElementById('editForm');
        const btn = document.getElementById('saveBtn');
        const alert = document.getElementById('statusAlert');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            alert.style.display = 'none';

            const formData = new FormData(form);
            try {
                const response = await fetch('../../services/api/edit_anime.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                alert.style.display = 'block';
                if (result.success) {
                    alert.className = 'alert success';
                    alert.innerHTML = '<i class="fas fa-check-circle"></i> ' + result.message;
                    setTimeout(() => window.location.href = 'dashboard.php?tab=content', 1500);
                } else {
                    alert.className = 'alert error';
                    alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + result.message;
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
                }
            } catch (err) {
                alert.style.display = 'block';
                alert.className = 'alert error';
                alert.innerHTML = '<i class="fas fa-exclamation-circle"></i> Connection error. Please try again.';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
            }
        });
    </script>
</body>
</html>
