<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../utils/bootstrap.php';
use App\Database\Connection;

if (empty($_SESSION['user_logged_in']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = Connection::getInstance();

// Fetch all manga series
$mangaList = [];
$chapters  = [];
$selected  = null;

$mangaId = (int)($_GET['manga_id'] ?? 0);

try {
    $stmt = $db->query("SELECT id, title, description, cover_url, status FROM admin_panel_manga ORDER BY id DESC");
    $mangaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) { /* table may not exist yet */ }

if ($mangaId && $mangaList) {
    foreach ($mangaList as $m) {
        if ((int)$m['id'] === $mangaId) { $selected = $m; break; }
    }
    if ($selected) {
        try {
            $chStmt = $db->prepare("SELECT id, chapter_number, title, file_url FROM admin_panel_manga_chapter WHERE manga_id = :id ORDER BY chapter_number ASC");
            $chStmt->execute(['id' => $mangaId]);
            $chapters = $chStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) { }
    }
} elseif (!$mangaId && $mangaList) {
    // Auto-select first manga
    $selected  = $mangaList[0];
    $mangaId   = (int)$selected['id'];
    try {
        $chStmt = $db->prepare("SELECT id, chapter_number, title, file_url FROM admin_panel_manga_chapter WHERE manga_id = :id ORDER BY chapter_number ASC");
        $chStmt->execute(['id' => $mangaId]);
        $chapters = $chStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) { }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $selected ? htmlspecialchars($selected['title']) . ' · '; ?>Manga Reader</title>
  <meta name="description" content="Read manga online. Browse series and chapters.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #09090f; --surface: #111118; --card: #16161e;
      --border: rgba(255,255,255,0.06); --primary: #ff4b2b; --secondary: #ff416c;
      --accent: #a78bfa; --text: #e2e8f0; --muted: #64748b;
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }

    /* NAV */
    nav {
      background: rgba(9,9,15,0.9); backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
      padding: 14px 24px; display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
    }
    .nav-brand { font-size: 18px; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
    .nav-back  { color: var(--muted); text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 10px; border: 1px solid var(--border); transition: all 0.2s; }
    .nav-back:hover { color: var(--text); border-color: rgba(255,255,255,0.15); }

    /* LAYOUT */
    .layout { display: flex; flex: 1; height: calc(100vh - 57px); overflow: hidden; }

    /* SIDEBAR */
    .sidebar {
      width: 280px; min-width: 280px;
      background: var(--surface); border-right: 1px solid var(--border);
      display: flex; flex-direction: column; overflow: hidden;
    }
    .sidebar-header { padding: 20px; border-bottom: 1px solid var(--border); }
    .sidebar-header h3 { font-size: 13px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; }
    .series-list { overflow-y: auto; flex: 1; }
    .series-item {
      display: flex; gap: 12px; align-items: center;
      padding: 14px 20px; text-decoration: none; color: var(--text);
      border-bottom: 1px solid var(--border); transition: background 0.15s;
    }
    .series-item:hover { background: rgba(255,255,255,0.03); }
    .series-item.active { background: rgba(255,75,43,0.08); border-left: 3px solid var(--primary); }
    .series-cover { width: 44px; height: 60px; object-fit: cover; border-radius: 6px; flex-shrink: 0; background: #111; }
    .series-cover-placeholder { width: 44px; height: 60px; background: var(--card); border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .series-info .s-title { font-size: 13px; font-weight: 600; margin-bottom: 3px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .series-info .s-status { font-size: 11px; color: var(--muted); }

    /* MAIN READER */
    .reader-main { flex: 1; overflow-y: auto; display: flex; flex-direction: column; }

    /* MANGA HEADER */
    .manga-header {
      padding: 28px 32px 20px;
      background: linear-gradient(180deg, rgba(167,139,250,0.05) 0%, transparent 100%);
      border-bottom: 1px solid var(--border);
    }
    .manga-header-inner { display: flex; gap: 24px; align-items: flex-start; }
    .manga-cover { width: 100px; height: 140px; object-fit: cover; border-radius: 12px; flex-shrink: 0; }
    .manga-cover-ph { width: 100px; height: 140px; background: var(--card); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .manga-title { font-size: 26px; font-weight: 800; margin-bottom: 6px; }
    .manga-status-badge { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; background: rgba(167,139,250,0.15); color: var(--accent); margin-bottom: 10px; }
    .manga-desc { color: var(--muted); font-size: 14px; line-height: 1.6; max-width: 640px; }

    /* CHAPTER LIST */
    .chapters-section { padding: 24px 32px; }
    .chapters-title { font-size: 16px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .chapters-title i { color: var(--accent); }
    .chapters-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
    .chapter-card {
      background: var(--card); border: 1px solid var(--border); border-radius: 12px;
      padding: 16px; display: flex; align-items: center; gap: 14px;
      text-decoration: none; color: var(--text); transition: all 0.2s;
    }
    .chapter-card:hover { background: rgba(255,255,255,0.04); border-color: rgba(167,139,250,0.3); transform: translateY(-2px); color: var(--text); text-decoration: none; }
    .ch-num-badge {
      width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
      background: linear-gradient(135deg, rgba(167,139,250,0.2), rgba(255,75,43,0.1));
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 800; color: var(--accent);
    }
    .ch-info .ch-title { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
    .ch-info .ch-sub   { font-size: 12px; color: var(--muted); }
    .ch-arrow { margin-left: auto; color: var(--muted); font-size: 12px; }

    /* EMPTY STATE */
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-state i { font-size: 64px; color: #1e293b; margin-bottom: 20px; display: block; }
    .empty-state h3 { color: var(--muted); font-size: 20px; margin-bottom: 8px; }
    .empty-state p  { color: #334155; font-size: 14px; }
    .empty-state a  { display: inline-block; margin-top: 20px; padding: 10px 24px; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 10px; text-decoration: none; font-weight: 600; }

    /* NO MANGA */
    .no-manga { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; }

    @media (max-width: 768px) {
      .layout { flex-direction: column; height: auto; }
      .sidebar { width: 100%; min-width: auto; height: auto; border-right: none; border-bottom: 1px solid var(--border); }
      .series-list { max-height: 200px; }
      .manga-header-inner { flex-direction: column; }
      .chapters-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<nav>
  <a href="manga_reader.php" class="nav-brand"><i class="fas fa-book-open" style="color:var(--accent);"></i> Manga Reader</a>
  <a href="manga.php" class="nav-back"><i class="fas fa-arrow-left"></i> Back to Manga</a>
</nav>

<div class="layout">

  <!-- Sidebar: Series List -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h3><i class="fas fa-list"></i> Series</h3>
    </div>
    <div class="series-list">
      <?php if (empty($mangaList)): ?>
        <div style="padding: 24px; color: var(--muted); font-size: 13px; text-align: center;">
          No manga series uploaded yet.
        </div>
      <?php endif; ?>
      <?php foreach ($mangaList as $m): ?>
        <a href="manga_reader.php?manga_id=<?php echo (int)$m['id']; ?>" class="series-item <?php echo ((int)$m['id'] === $mangaId) ? 'active' : ''; ?>">
          <?php if (!empty($m['cover_url'])): ?>
            <img src="<?php echo htmlspecialchars($m['cover_url']); ?>" class="series-cover" alt="">
          <?php else: ?>
            <div class="series-cover-placeholder"><i class="fas fa-book" style="color:#334155;"></i></div>
          <?php endif; ?>
          <div class="series-info">
            <div class="s-title"><?php echo htmlspecialchars($m['title']); ?></div>
            <div class="s-status"><?php echo htmlspecialchars($m['status']); ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>

  <!-- Main Reader Area -->
  <main class="reader-main">
    <?php if (empty($mangaList)): ?>
      <div class="no-manga">
        <div class="empty-state">
          <i class="fas fa-book-open"></i>
          <h3>No Manga Available</h3>
          <p>The admin hasn't uploaded any manga series yet.</p>
          <a href="manga.php">Back to Manga</a>
        </div>
      </div>

    <?php elseif ($selected): ?>
      <!-- Manga Header -->
      <div class="manga-header">
        <div class="manga-header-inner">
          <?php if (!empty($selected['cover_url'])): ?>
            <img src="<?php echo htmlspecialchars($selected['cover_url']); ?>" class="manga-cover" alt="">
          <?php else: ?>
            <div class="manga-cover-ph"><i class="fas fa-book" style="font-size:36px; color:#334155;"></i></div>
          <?php endif; ?>
          <div>
            <div class="manga-title"><?php echo htmlspecialchars($selected['title']); ?></div>
            <span class="manga-status-badge"><?php echo htmlspecialchars($selected['status']); ?></span>
            <p class="manga-desc"><?php echo htmlspecialchars($selected['description'] ?: 'No description provided.'); ?></p>
          </div>
        </div>
      </div>

      <!-- Chapters -->
      <div class="chapters-section">
        <div class="chapters-title"><i class="fas fa-book-reader"></i> <?php echo count($chapters); ?> Chapter<?php echo count($chapters) !== 1 ? 's' : ''; ?></div>

        <?php if (empty($chapters)): ?>
          <div class="empty-state">
            <i class="fas fa-file-alt"></i>
            <h3>No Chapters Yet</h3>
            <p>The admin hasn't uploaded chapters for this series.</p>
          </div>
        <?php else: ?>
          <div class="chapters-grid">
            <?php foreach ($chapters as $ch): ?>
              <a href="<?php echo htmlspecialchars($ch['file_url']); ?>" target="_blank" class="chapter-card">
                <div class="ch-num-badge"><?php echo htmlspecialchars((string)$ch['chapter_number']); ?></div>
                <div class="ch-info">
                  <div class="ch-title"><?php echo htmlspecialchars($ch['title'] ?: 'Chapter ' . $ch['chapter_number']); ?></div>
                  <div class="ch-sub">Tap to read</div>
                </div>
                <i class="fas fa-external-link-alt ch-arrow"></i>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>

</div>

</body>
</html>
