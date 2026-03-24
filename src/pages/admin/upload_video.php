<?php
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php?error=Admin login required');
    exit;
}

use App\Database\Connection;
$db = Connection::getInstance();
$seriesList = [];
try {
    $stmt = $db->query("SELECT id, title FROM admin_panel_anime WHERE type = 'Series' ORDER BY title ASC");
    $seriesList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4b2b;
            --secondary: #ff416c;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --accent: #38bdf8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background-color: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: var(--card-bg); border-right: 1px solid rgba(255, 255, 255, 0.1); padding: 32px; display: flex; flex-direction: column; }
        .sidebar-header { display: flex; align-items: center; gap: 12px; margin-bottom: 48px; }
        .sidebar-header i { font-size: 32px; color: var(--primary); }
        .sidebar-header span { font-size: 20px; font-weight: 700; }
        .nav-items { flex: 1; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; color: #94a3b8; text-decoration: none; margin-bottom: 8px; transition: all 0.2s; }
        .nav-item:hover, .nav-item.active { background: rgba(255, 75, 43, 0.1); color: var(--primary); }
        .main-content { flex: 1; padding: 48px; overflow-y: auto; }
        
        .section-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .section-title i { color: var(--accent); }
        
        .form-card { background: var(--card-bg); padding: 32px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); max-width: 800px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; font-size: 16px; font-family: inherit;}
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .btn-submit { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 14px 24px; border-radius: 12px; border: none; font-size: 16px; font-weight: 700; cursor: pointer; width: 100%; transition: opacity 0.2s; margin-top: 10px;}
        .btn-submit:hover { opacity: 0.9; }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        
        #progress-container { display: none; margin-top: 20px; }
        .progress-bar { width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; margin-bottom: 8px; }
        .progress-fill { height: 100%; background: var(--primary); width: 0%; transition: width 0.2s; }
        .progress-text { font-size: 14px; color: #94a3b8; text-align: center; }
        
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; display: none; }
        .alert.success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; }
        .alert.error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; }
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: flex-start;}
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-bolt"></i>
            <span>Admin Central</span>
        </div>
        <div class="nav-items">
            <a href="index.php" class="nav-item"><i class="fas fa-home"></i> Metrics</a>
            <a href="manage_anime.php" class="nav-item"><i class="fas fa-film"></i> Anime Catalog</a>
            <a href="upload_video.php" class="nav-item active"><i class="fas fa-upload"></i> Upload Video</a>
            <a href="manage_manga.php" class="nav-item"><i class="fas fa-book-open"></i> Manage Manga</a>
            <a href="ash.php" class="nav-item"><i class="fas fa-external-link-alt"></i> Visit Site</a>
        </div>
        <a href="index.php?action=logout" class="nav-item" style="margin-top: auto; color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="grid-container">
            
            <!-- Movie Upload Form -->
            <div>
                <h2 class="section-title"><i class="fas fa-cloud-upload-alt"></i> Upload New Movie</h2>
                <div id="movie-alert" class="alert"></div>

                <div class="form-card">
                    <form id="movieForm">
                        <input type="hidden" name="upload_type" value="movie">
                        <div class="form-group">
                            <label>Movie Title *</label>
                            <input type="text" name="title" class="form-control" required placeholder="Demon Slayer Movie">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" placeholder="Synopsis"></textarea>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <div class="form-group" style="flex:1;">
                                <label>Genre tags</label>
                                <input type="text" name="genre" class="form-control" placeholder="Action, Drama">
                            </div>
                            <div class="form-group" style="flex:1;">
                                <label>Release Year</label>
                                <input type="number" name="release_year" class="form-control" placeholder="2024">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Movie Thumbnail (JPG/PNG/WEBP)</label>
                            <input type="file" name="thumbnail" accept="image/*" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Video File (MP4/MKV) OR URL</label>
                            <input type="file" name="video_file" accept="video/mp4,video/webm,video/x-matroska" class="form-control" style="margin-bottom: 5px;">
                            <input type="url" name="video_url" class="form-control" placeholder="Video External Link">
                        </div>
                        <button type="submit" class="btn-submit">Upload Movie</button>
                    </form>
                    <div class="progress-container" id="movie-progress" style="display:none;margin-top:15px;">
                        <div class="progress-bar"><div class="progress-fill" id="movie-fill"></div></div>
                        <div class="progress-text" id="movie-text">0%</div>
                    </div>
                </div>
            </div>

            <!-- Episode Upload Form -->
            <div>
                <h2 class="section-title"><i class="fas fa-list-ol"></i> Add Series Episode</h2>
                <div id="ep-alert" class="alert"></div>

                <div class="form-card">
                    <form id="episodeForm">
                        <input type="hidden" name="upload_type" value="episode">
                        <div class="form-group">
                            <label>Select Anime Series *</label>
                            <select name="anime_id" id="anime_select" class="form-control" required>
                                <option value="">-- Choose Series --</option>
                                <?php foreach ($seriesList as $series): ?>
                                    <option value="<?= $series['id'] ?>"><?= htmlspecialchars($series['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Episode Number *</label>
                            <input type="number" step="0.5" name="episode_number" class="form-control" required placeholder="e.g. 1">
                        </div>
                        <div class="form-group">
                            <label>Episode Title (Optional)</label>
                            <input type="text" name="title" class="form-control" placeholder="The Boy Who Lived">
                        </div>
                        <div class="form-group">
                            <label>Episode Thumbnail (Optional)</label>
                            <input type="file" name="thumbnail" accept="image/*" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Video File * OR URL</label>
                            <input type="file" name="video_file" accept="video/mp4,video/webm,video/x-matroska" class="form-control" style="margin-bottom: 5px;">
                            <input type="url" name="video_url" class="form-control" placeholder="Video External Link">
                        </div>
                        <button type="submit" class="btn-submit">Upload Episode</button>
                    </form>
                    <div class="progress-container" id="ep-progress" style="display:none;margin-top:15px;">
                        <div class="progress-bar"><div class="progress-fill" id="ep-fill"></div></div>
                        <div class="progress-text" id="ep-text">0%</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function showAlert(alertEl, msg, isError = false) {
            alertEl.style.display = 'block';
            alertEl.className = 'alert ' + (isError ? 'error' : 'success');
            alertEl.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i> ${msg}`;
        }

        function handleAjaxForm(formId, alertId, progressId, fillId, textId) {
            const form = document.getElementById(formId);
            const alertEl = document.getElementById(alertId);
            const progressContainer = document.getElementById(progressId);
            const progressFill = document.getElementById(fillId);
            const progressText = document.getElementById(textId);
            const submitBtn = form.querySelector('button[type="submit"]');

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const fileInput = form.querySelector('input[type="file"][name="video_file"]');
                const urlInput = form.querySelector('input[type="url"][name="video_url"]');
                if(!fileInput.files.length && !urlInput.value.trim()){
                    showAlert(alertEl, 'Please provide either a video file or a video URL.', true);
                    return;
                }

                const formData = new FormData(form);
                submitBtn.disabled = true;
                progressContainer.style.display = 'block';
                alertEl.style.display = 'none';
                progressFill.style.width = '0%';
                progressText.innerText = 'Initializing upload...';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/src/services/api/upload_video.php', true);
                
                xhr.upload.onprogress = (event) => {
                    if (event.lengthComputable) {
                        const percentComplete = Math.floor((event.loaded / event.total) * 100);
                        progressFill.style.width = percentComplete + '%';
                        progressText.innerText = `Uploading... ${percentComplete}%`;
                    }
                };

                xhr.onload = () => {
                    submitBtn.disabled = false;
                    progressContainer.style.display = 'none';
                    if (xhr.status === 200) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.success) {
                                showAlert(alertEl, res.message);
                                form.reset();
                            } else {
                                showAlert(alertEl, res.message, true);
                            }
                        } catch (e) {
                            showAlert(alertEl, 'Server returned an invalid response.', true);
                        }
                    } else {
                        showAlert(alertEl, 'Network error occurred during upload.', true);
                    }
                };

                xhr.onerror = () => {
                    submitBtn.disabled = false;
                    progressContainer.style.display = 'none';
                    showAlert(alertEl, 'An unexpected error occurred.', true);
                };

                xhr.send(formData);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            handleAjaxForm('movieForm', 'movie-alert', 'movie-progress', 'movie-fill', 'movie-text');
            handleAjaxForm('episodeForm', 'ep-alert', 'ep-progress', 'ep-fill', 'ep-text');
        });
    </script>
</body>
</html>
