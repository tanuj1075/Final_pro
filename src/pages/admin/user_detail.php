<?php
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../../utils/bootstrap.php';

use App\Database\Connection;

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin?error=Admin login required');
    exit;
}

$uid = (int)($_GET['id'] ?? 0);
if (!$uid) { header('Location: /admin/dashboard'); exit; }

$user = null;
$history = [];
$favorites = [];
$totalWatched = 0;
$totalFavs = 0;

try {
    $db = Connection::getInstance();

    $stmt = $db->prepare("SELECT * FROM admin_panel_siteuser WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();

    if (!$user) { header('Location: /admin/dashboard'); exit; }

    // Ensure tables exist
    $db->exec("CREATE TABLE IF NOT EXISTS user_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL, anime_id INTEGER NOT NULL,
        watched_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS user_favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL, anime_id INTEGER NOT NULL,
        added_at TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE (user_id, anime_id)
    )");

    // History (last 12, deduplicated)
    $stmt = $db->prepare(
        "SELECT anime_id, MAX(watched_at) AS watched_at FROM user_history
         WHERE user_id = ? GROUP BY anime_id ORDER BY watched_at DESC LIMIT 12"
    );
    $stmt->execute([$uid]);
    $history = $stmt->fetchAll();

    // Favorites (last 12)
    $stmt = $db->prepare("SELECT anime_id, added_at FROM user_favorites WHERE user_id = ? ORDER BY added_at DESC LIMIT 12");
    $stmt->execute([$uid]);
    $favorites = $stmt->fetchAll();

    $totalWatched = (int)$db->query("SELECT COUNT(DISTINCT anime_id) FROM user_history WHERE user_id = $uid")->fetchColumn();
    $totalFavs    = (int)$db->query("SELECT COUNT(*) FROM user_favorites WHERE user_id = $uid")->fetchColumn();

} catch (\Exception $e) {
    $errorMsg = $e->getMessage();
}

// Helper: resolve anime info (DB first, then JSON cache)
$isVercel = (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false);
$cacheDir = $isVercel ? '/tmp/ackerstream_cache' : __DIR__ . '/../../cache';
function resolveAnime(int $aid, $db, string $cacheDir): array {
    try {
        $s = $db->prepare("SELECT title, cover_image FROM admin_panel_anime WHERE id = ?");
        $s->execute([$aid]);
        $row = $s->fetch();
        if ($row) return ['title' => $row['title'], 'image' => $row['cover_image'] ? '/src/assets/anime/'.$row['cover_image'] : '', 'link' => '/admin/upload_video'];
    } catch (\Exception $e) {}
    $cf = $cacheDir . '/anime_' . $aid . '_cache.json';
    if (file_exists($cf)) {
        $d = json_decode(file_get_contents($cf), true);
        return [
            'title' => $d['title_english'] ?? $d['title'] ?? 'Anime #'.$aid,
            'image' => $d['images']['jpg']['image_url'] ?? '',
            'link'  => '/watch.php?id='.$aid,
        ];
    }
    return ['title' => 'Anime #'.$aid, 'image' => '', 'link' => '#'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Detail - <?= htmlspecialchars($user['username'] ?? '') ?> | Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#ff4b2b; --bg:#0f172a; --card:#1e293b; --text:#f8fafc; --muted:#94a3b8; --accent:#38bdf8; --border:rgba(255,255,255,.08); --green:#10b981; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Outfit',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; padding:40px 5%; }
        .back { display:inline-flex; align-items:center; gap:8px; color:var(--muted); text-decoration:none; font-size:14px; margin-bottom:28px; transition:color .2s; }
        .back:hover { color:var(--text); }
        .profile-hero { background:var(--card); border-radius:20px; padding:32px; border:1px solid var(--border); display:flex; align-items:center; gap:28px; margin-bottom:28px; }
        .avatar { width:72px; height:72px; background:linear-gradient(135deg,var(--primary),#ff416c); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:28px; font-weight:700; flex-shrink:0; }
        .profile-info h2 { font-size:22px; margin-bottom:4px; }
        .profile-info p { color:var(--muted); font-size:13px; }
        .stats-row { display:flex; gap:20px; flex-wrap:wrap; margin-top:12px; }
        .stat-chip { background:rgba(255,255,255,.06); border-radius:10px; padding:8px 16px; font-size:13px; }
        .stat-chip span { color:var(--accent); font-weight:700; font-size:18px; display:block; }
        .badge { display:inline-flex; align-items:center; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-green { background:rgba(16,185,129,.12); color:#34d399; }
        .badge-red { background:rgba(239,68,68,.12); color:#f87171; }
        .section-title { font-size:18px; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:10px; }
        .section-title i { color:var(--accent); }
        .anime-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:12px; margin-bottom:40px; }
        .anime-card { background:var(--card); border-radius:10px; overflow:hidden; border:1px solid var(--border); text-decoration:none; color:var(--text); transition:transform .2s; display:block; }
        .anime-card:hover { transform:translateY(-4px); }
        .anime-card img { width:100%; aspect-ratio:2/3; object-fit:cover; }
        .anime-card-body { padding:8px 10px; }
        .anime-card-title { font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:3px; }
        .anime-card-date { font-size:11px; color:var(--muted); }
        .no-img { aspect-ratio:2/3; background:#1e293b; display:flex; align-items:center; justify-content:center; color:#475569; font-size:28px; }
        .detail-card { background:var(--card); border-radius:16px; padding:24px; border:1px solid var(--border); margin-bottom:28px; }
        .info-row { display:flex; align-items:center; gap:14px; padding:11px 0; border-bottom:1px solid var(--border); font-size:14px; }
        .info-row:last-child { border-bottom:none; }
        .info-row i { color:var(--accent); width:18px; text-align:center; }
        .info-label { color:var(--muted); flex:1; font-size:13px; }
    </style>
</head>
<body>
    <a href="/admin/dashboard" class="back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <!-- Profile Hero -->
    <div class="profile-hero">
        <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
        <div class="profile-info">
            <h2><?= htmlspecialchars($user['username']) ?>
                <?php if ((int)$user['is_active'] === 0): ?>
                    <span class="badge badge-red" style="margin-left:8px;">Banned</span>
                <?php elseif ((int)$user['is_approved'] === 1): ?>
                    <span class="badge badge-green" style="margin-left:8px;">Active</span>
                <?php endif; ?>
            </h2>
            <p><?= htmlspecialchars($user['email']) ?></p>
            <div class="stats-row">
                <div class="stat-chip"><span><?= $totalWatched ?></span>Anime Watched</div>
                <div class="stat-chip"><span><?= $totalFavs ?></span>Favorites</div>
                <div class="stat-chip"><span style="font-size:13px;"><?= htmlspecialchars(substr($user['created_at'] ?? '', 0, 10)) ?></span>Joined</div>
            </div>
        </div>
    </div>

    <!-- Account Details -->
    <div class="section-title"><i class="fas fa-id-badge"></i> Account Details</div>
    <div class="detail-card">
        <div class="info-row"><i class="fas fa-hashtag"></i><div class="info-label">User ID</div><div>#<?= (int)$user['id'] ?></div></div>
        <div class="info-row"><i class="fas fa-envelope"></i><div class="info-label">Email</div><div><?= htmlspecialchars($user['email']) ?></div></div>
        <div class="info-row"><i class="fas fa-sign-in-alt"></i><div class="info-label">Last Login</div><div><?= htmlspecialchars($user['last_login'] ?? 'Never') ?></div></div>
        <div class="info-row"><i class="fas fa-sign-out-alt"></i><div class="info-label">Last Logout</div><div><?= htmlspecialchars($user['last_logout'] ?? '—') ?></div></div>
        <div class="info-row"><i class="fas fa-network-wired"></i><div class="info-label">Registered IP</div><div><?= htmlspecialchars($user['registration_ip'] ?? '—') ?></div></div>
        <div class="info-row"><i class="fas fa-laptop"></i><div class="info-label">Last Seen IP</div><div><?= htmlspecialchars($user['last_seen_ip'] ?? '—') ?></div></div>
    </div>

    <!-- Watch History -->
    <div class="section-title"><i class="fas fa-history"></i> Watch History (<?= $totalWatched ?> unique)</div>
    <?php if (!empty($history)): ?>
    <div class="anime-grid">
        <?php foreach ($history as $row):
            $info = resolveAnime((int)$row['anime_id'], $db, $cacheDir);
        ?>
        <a class="anime-card" href="<?= htmlspecialchars($info['link']) ?>">
            <?php if ($info['image']): ?>
                <img src="<?= htmlspecialchars($info['image']) ?>" alt="<?= htmlspecialchars($info['title']) ?>" loading="lazy">
            <?php else: ?>
                <div class="no-img"><i class="fas fa-image"></i></div>
            <?php endif; ?>
            <div class="anime-card-body">
                <div class="anime-card-title"><?= htmlspecialchars($info['title']) ?></div>
                <div class="anime-card-date"><?= htmlspecialchars(substr($row['watched_at'] ?? '', 0, 10)) ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="color:var(--muted); margin-bottom:32px;">No watch history recorded for this user.</p>
    <?php endif; ?>

    <!-- Favorites -->
    <div class="section-title"><i class="fas fa-heart"></i> Favorites (<?= $totalFavs ?>)</div>
    <?php if (!empty($favorites)): ?>
    <div class="anime-grid">
        <?php foreach ($favorites as $row):
            $info = resolveAnime((int)$row['anime_id'], $db, $cacheDir);
        ?>
        <a class="anime-card" href="<?= htmlspecialchars($info['link']) ?>">
            <?php if ($info['image']): ?>
                <img src="<?= htmlspecialchars($info['image']) ?>" alt="<?= htmlspecialchars($info['title']) ?>" loading="lazy">
            <?php else: ?>
                <div class="no-img"><i class="fas fa-image"></i></div>
            <?php endif; ?>
            <div class="anime-card-body">
                <div class="anime-card-title"><?= htmlspecialchars($info['title']) ?></div>
                <div class="anime-card-date">Added <?= htmlspecialchars(substr($row['added_at'] ?? '', 0, 10)) ?></div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="color:var(--muted);">No favorites saved by this user.</p>
    <?php endif; ?>

</body>
</html>
