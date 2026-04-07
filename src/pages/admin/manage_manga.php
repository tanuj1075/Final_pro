<?php
require_once __DIR__ . '/../../utils/bootstrap.php';
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin?error=Admin login required');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Manga - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #ff4b2b; --secondary: #ff416c; --bg: #0f172a; --card-bg: #1e293b; --text: #f8fafc; --accent: #38bdf8; }
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
        
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: flex-start;}
        
        .form-card { background: var(--card-bg); padding: 32px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #94a3b8; font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; font-size: 16px; font-family: inherit;}
        .form-control:focus { outline: none; border-color: var(--primary); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .btn-submit { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 14px 24px; border-radius: 12px; border: none; font-size: 16px; font-weight: 700; cursor: pointer; width: 100%; transition: opacity 0.2s;}
        .btn-submit:hover { opacity: 0.9; }
        .btn-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 24px; display: none; }
        .alert.success { background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; }
        .alert.error { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-bolt"></i>
            <span>Admin Central</span>
        </div>
        <div class="nav-items">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="upload_video.php" class="nav-item"><i class="fas fa-upload"></i> Upload Video</a>
            <a href="manage_manga.php" class="nav-item active"><i class="fas fa-book-open"></i> Manage Manga</a>
            <a href="admin_profile.php" class="nav-item"><i class="fas fa-user-cog"></i> My Profile</a>
            <a href="../../pages/ash.php" class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> Visit Site</a>
        </div>
        <a href="../index.php?action=logout" class="nav-item" style="margin-top: auto; color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <div class="grid-container">
            
            <!-- Series Upload Form -->
            <div>
                <h2 class="section-title"><i class="fas fa-folder-plus"></i> Create Manga Series</h2>
                <div id="series-alert" class="alert"></div>

                <div class="form-card">
                    <form id="seriesForm">
                        <input type="hidden" name="action" value="create_manga">
                        <div class="form-group">
                            <label>Manga Title *</label>
                            <input type="text" name="title" class="form-control" required placeholder="Jujutsu Kaisen">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" placeholder="Manga synopsis..."></textarea>
                        </div>
                        <div class="form-group">
                            <label>Cover Image (JPG/PNG/WEBP)</label>
                            <input type="file" name="cover" accept="image/*" class="form-control">
                        </div>
                        <button type="submit" id="seriesBtn" class="btn-submit">Create Series</button>
                    </form>
                </div>
            </div>

            <!-- Chapter Upload Form -->
            <div>
                <h2 class="section-title"><i class="fas fa-file-upload"></i> Upload Chapter</h2>
                <div id="chapter-alert" class="alert"></div>

                <div class="form-card">
                    <form id="chapterForm">
                        <input type="hidden" name="action" value="upload_chapter">
                        <div class="form-group">
                            <label>Select Manga Series *</label>
                            <select name="manga_id" id="manga_select" class="form-control" required>
                                <option value="">Loading...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Chapter Number *</label>
                            <input type="number" step="0.1" name="chapter_number" class="form-control" required placeholder="e.g. 1.5">
                        </div>
                        <div class="form-group">
                            <label>Chapter Name (Optional)</label>
                            <input type="text" name="chapter_title" class="form-control" placeholder="Ryomen Sukuna">
                        </div>
                        <div class="form-group">
                            <label>Chapter File (ZIP or Image)</label>
                            <input type="file" name="chapter_file" accept=".zip,image/*" class="form-control" required>
                        </div>
                        <button type="submit" id="chapterBtn" class="btn-submit">Upload Chapter</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        const seriesForm = document.getElementById('seriesForm');
        const chapterForm = document.getElementById('chapterForm');
        const mangaSelect = document.getElementById('manga_select');

        function fetchMangaList() {
            const formData = new FormData();
            formData.append('action', 'list_manga');
            
            fetch('/api/upload_manga.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        mangaSelect.innerHTML = '<option value="">-- Choose Series --</option>';
                        data.mangaList.forEach(m => {
                            mangaSelect.innerHTML += `<option value="${m.id}">${m.title}</option>`;
                        });
                    }
                }).catch(e => console.error(e));
        }

        function handleAjaxForm(formElement, btnElement, alertElement, successCallback) {
            formElement.addEventListener('submit', (e) => {
                e.preventDefault();
                btnElement.disabled = true;
                const formData = new FormData(formElement);
                
                fetch('/api/upload_manga.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    btnElement.disabled = false;
                    alertElement.style.display = 'block';
                    alertElement.className = 'alert ' + (data.success ? 'success' : 'error');
                    alertElement.innerText = data.message;
                    if (data.success) {
                        formElement.reset();
                        if(successCallback) successCallback();
                    }
                })
                .catch(err => {
                    btnElement.disabled = false;
                    alertElement.style.display = 'block';
                    alertElement.className = 'alert error';
                    alertElement.innerText = 'Network error occurred.';
                });
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchMangaList();
            handleAjaxForm(seriesForm, document.getElementById('seriesBtn'), document.getElementById('series-alert'), fetchMangaList);
            handleAjaxForm(chapterForm, document.getElementById('chapterBtn'), document.getElementById('chapter-alert'));
        });
    </script>
</body>
</html>
