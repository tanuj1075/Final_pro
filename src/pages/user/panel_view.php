<?php
// ── Pull history & favorites from DB for this user ──────────────────────────
$historyAnime  = [];
$favoritesAnime = [];
$totalWatched  = 0;
$totalFavorites = 0;

try {
    $db = \App\Database\Connection::getInstance();

    // Ensure tables exist (always safe)
    $db->exec("CREATE TABLE IF NOT EXISTS user_history (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        anime_id INTEGER NOT NULL,
        watched_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS user_favorites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        anime_id INTEGER NOT NULL,
        added_at TEXT NOT NULL DEFAULT (datetime('now')),
        UNIQUE (user_id, anime_id)
    )");

    $uid = (int)($user['id'] ?? 0);

    // Recent history (last 6, deduplicated)
    $histStmt = $db->prepare(
        "SELECT anime_id, MAX(watched_at) AS watched_at FROM user_history
         WHERE user_id = ? GROUP BY anime_id ORDER BY watched_at DESC LIMIT 6"
    );
    $histStmt->execute([$uid]);
    $historyRows = $histStmt->fetchAll();

    // Favorites (last 6)
    $favStmt = $db->prepare(
        "SELECT anime_id FROM user_favorites WHERE user_id = ? ORDER BY added_at DESC LIMIT 6"
    );
    $favStmt->execute([$uid]);
    $favoriteRows = $favStmt->fetchAll();

    // Stats
    $totalWatched = (int)$db->prepare(
        "SELECT COUNT(DISTINCT anime_id) FROM user_history WHERE user_id = ?"
    )->execute([$uid]) ? (int)$db->query("SELECT COUNT(DISTINCT anime_id) FROM user_history WHERE user_id = $uid")->fetchColumn() : 0;

    $totalFavorites = (int)$db->query(
        "SELECT COUNT(*) FROM user_favorites WHERE user_id = $uid"
    )->fetchColumn();

    // Fetch anime details from cache/Jikan for history
    $isVercel = (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false);
    $cacheDir = $isVercel ? '/tmp/ackerstream_cache' : __DIR__ . '/../../cache';
    if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

    foreach ($historyRows as $row) {
        $aid = (int)$row['anime_id'];
        $cf  = $cacheDir . '/anime_' . $aid . '_cache.json';
        $info = null;
        if (file_exists($cf) && (time() - filemtime($cf) < 3600)) {
            $info = json_decode(file_get_contents($cf), true);
        } else {
            $ctx = stream_context_create(['http' => ['header' => "User-Agent: AckerStream/1.0\r\n"]]);
            $res = @file_get_contents("https://api.jikan.moe/v4/anime/{$aid}", false, $ctx);
            if ($res) { $decoded = json_decode($res, true); $info = $decoded['data'] ?? null; if ($info) file_put_contents($cf, json_encode($info)); }
            usleep(300000);
        }
        if ($info) $historyAnime[] = ['anime' => $info, 'watched_at' => $row['watched_at']];
    }

    // Fetch anime details for favorites
    foreach ($favoriteRows as $row) {
        $aid = (int)$row['anime_id'];
        $cf  = $cacheDir . '/anime_' . $aid . '_cache.json';
        $info = null;
        if (file_exists($cf) && (time() - filemtime($cf) < 3600)) {
            $info = json_decode(file_get_contents($cf), true);
        } else {
            $ctx = stream_context_create(['http' => ['header' => "User-Agent: AckerStream/1.0\r\n"]]);
            $res = @file_get_contents("https://api.jikan.moe/v4/anime/{$aid}", false, $ctx);
            if ($res) { $decoded = json_decode($res, true); $info = $decoded['data'] ?? null; if ($info) file_put_contents($cf, json_encode($info)); }
            usleep(300000);
        }
        if ($info) $favoritesAnime[] = $info;
    }
} catch (\Exception $e) {
    // Silent fail — show panel with empty lists
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AckerStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --bg: #0a0e1a;
            --surface: #111827;
            --border: rgba(255,255,255,0.08);
            --text: #f1f5f9;
            --muted: #94a3b8;
            --gold: #fbbf24;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        /* ── HEADER ── */
        .profile-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #0f172a 60%);
            padding: 60px 5% 40px;
            border-bottom: 1px solid var(--border);
        }
        .header-inner { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .user-info { display: flex; align-items: center; gap: 24px; }
        .avatar {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            border-radius: 50%; display: flex; justify-content: center; align-items: center;
            font-size: 30px; font-weight: 700; box-shadow: 0 0 30px var(--primary-glow);
        }
        .user-details h1 { font-size: 26px; margin-bottom: 4px; }
        .user-details p { color: var(--muted); font-size: 14px; }
        .header-actions { display:flex; gap:12px; flex-wrap:wrap; }
        .btn { padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all .2s; border: none; }
        .btn-home { background: var(--primary); color: white; }
        .btn-home:hover { opacity: .88; }
        .btn-logout { background: rgba(239,68,68,.12); color: #f87171; border: 1px solid rgba(239,68,68,.2); }
        .btn-logout:hover { background: rgba(239,68,68,.22); }

        /* ── STATS BAR ── */
        .stats-bar { background: var(--surface); border-bottom: 1px solid var(--border); }
        .stats-inner { max-width: 1200px; margin: 0 auto; padding: 24px 5%; display: flex; gap: 32px; flex-wrap: wrap; }
        .stat { text-align:center; }
        .stat-number { font-size: 32px; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-top: 2px; }

        /* ── MAIN CONTENT ── */
        .main { max-width: 1200px; margin: 0 auto; padding: 40px 5%; }
        .section-title { font-size: 20px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .section-title i { color: var(--primary); }

        /* ── ANIME GRID ── */
        .anime-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 48px; }
        .anime-card { background: var(--surface); border-radius: 14px; overflow: hidden; text-decoration: none; color: var(--text); transition: transform .2s, box-shadow .2s; border: 1px solid var(--border); display: block; }
        .anime-card:hover { transform: translateY(-6px); box-shadow: 0 12px 32px rgba(0,0,0,.4); }
        .anime-card img { width: 100%; aspect-ratio: 2/3; object-fit: cover; display: block; }
        .anime-card-body { padding: 10px 12px; }
        .anime-card-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .anime-card-meta { font-size: 11px; color: var(--muted); }
        .anime-card-score { color: var(--gold); font-size: 12px; }

        /* ── ACCOUNT DETAILS ── */
        .card { background: var(--surface); border-radius: 20px; padding: 28px; border: 1px solid var(--border); margin-bottom: 32px; }
        .info-row { display: flex; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid var(--border); }
        .info-row:last-child { border-bottom: none; }
        .info-row i { color: var(--primary); font-size: 18px; width: 22px; text-align: center; }
        .info-label { color: var(--muted); font-size: 13px; flex: 1; }
        .info-value { font-weight: 500; }
        .badge-approved { background: rgba(16,185,129,.12); color: #34d399; border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }

        /* ── EMPTY STATE ── */
        .empty { text-align: center; padding: 40px; color: var(--muted); }
        .empty i { font-size: 48px; display: block; margin-bottom: 16px; opacity: .4; }
        .empty p { font-size: 14px; }
    </style>
</head>
<body>

<!-- Profile Header -->
<div class="profile-header">
    <div class="header-inner">
        <div class="user-info">
            <div class="avatar"><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?></div>
            <div class="user-details">
                <h1><?= htmlspecialchars($user['username'] ?? 'Anime Fan') ?></h1>
                <p><?= htmlspecialchars($user['email'] ?? '') ?> &nbsp;·&nbsp; Member since <?= htmlspecialchars(substr($user['created_at'] ?? '', 0, 10)) ?></p>
            </div>
        </div>
        <div class="header-actions">
            <a href="subscription.php" class="btn btn-home" style="background:#fbbf24;color:#000;"><i class="fas fa-crown"></i> Upgrade Plan</a>
            <a href="ash.php" class="btn btn-home"><i class="fas fa-home"></i> Home Feed</a>
            <a href="?logout=1" class="btn btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat"><div class="stat-number"><?= $totalWatched ?></div><div class="stat-label">Anime Watched</div></div>
        <div class="stat"><div class="stat-number"><?= $totalFavorites ?></div><div class="stat-label">Favorites</div></div>
        <div class="stat">
            <div class="stat-number" style="color:var(--gold);"><?= htmlspecialchars($user['subscription_tier'] ?? 'Free') ?></div>
            <div class="stat-label">Current Plan</div>
        </div>
        <div class="stat"><div class="stat-number"><span class="badge-approved">✓ Active</span></div><div class="stat-label">Account Status</div></div>
    </div>
</div>

<div class="main">

    <?php if ($dbError): ?>
        <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#fca5a5;padding:16px;border-radius:12px;margin-bottom:24px;">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($dbError) ?>
        </div>
    <?php endif; ?>

    <!-- Recently Watched -->
    <div class="section-title"><i class="fas fa-history"></i> Recently Watched</div>
    <?php if (!empty($historyAnime)): ?>
        <div class="anime-grid">
            <?php foreach ($historyAnime as $item):
                $a = $item['anime'];
                $img = $a['images']['webp']['image_url'] ?? $a['images']['jpg']['image_url'] ?? '';
                $t = htmlspecialchars($a['title_english'] ?? $a['title'] ?? 'Unknown');
                $s = $a['score'] ?? null;
            ?>
                <a class="anime-card" href="watch.php?id=<?= (int)$a['mal_id'] ?>">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= $t ?>" loading="lazy">
                    <div class="anime-card-body">
                        <div class="anime-card-title"><?= $t ?></div>
                        <div class="anime-card-meta">
                            <?php if ($s): ?><span class="anime-card-score">⭐ <?= $s ?></span><?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty"><i class="fas fa-tv"></i><p>No watch history yet — go explore some anime!</p></div>
    <?php endif; ?>

    <!-- Favorites -->
    <div class="section-title"><i class="fas fa-heart"></i> My Favorites</div>
    <?php if (!empty($favoritesAnime)): ?>
        <div class="anime-grid">
            <?php foreach ($favoritesAnime as $a):
                $img = $a['images']['webp']['image_url'] ?? $a['images']['jpg']['image_url'] ?? '';
                $t = htmlspecialchars($a['title_english'] ?? $a['title'] ?? 'Unknown');
                $s = $a['score'] ?? null;
            ?>
                <a class="anime-card" href="watch.php?id=<?= (int)$a['mal_id'] ?>">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= $t ?>" loading="lazy">
                    <div class="anime-card-body">
                        <div class="anime-card-title"><?= $t ?></div>
                        <div class="anime-card-meta">
                            <?php if ($s): ?><span class="anime-card-score">⭐ <?= $s ?></span><?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty"><i class="fas fa-heart-broken"></i><p>No favorites yet — click the <strong>♥ Add to Favorites</strong> button on any watch page!</p></div>
    <?php endif; ?>

    <!-- Account Details -->
    <div class="section-title"><i class="fas fa-user-shield"></i> Account Details</div>
    <div class="card">
        <div class="info-row">
            <i class="fas fa-user"></i>
            <div class="info-label">Username</div>
            <div class="info-value"><?= htmlspecialchars($user['username'] ?? 'N/A') ?></div>
        </div>
        <div class="info-row">
            <i class="fas fa-envelope"></i>
            <div class="info-label">Email</div>
            <div class="info-value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></div>
        </div>
        <div class="info-row">
            <i class="fas fa-id-badge"></i>
            <div class="info-label">User ID</div>
            <div class="info-value">#<?= (int)($user['id'] ?? 0) ?></div>
        </div>
        <div class="info-row">
            <i class="fas fa-check-circle"></i>
            <div class="info-label">Status</div>
            <div class="info-value"><span class="badge-approved">Approved &amp; Active</span></div>
        </div>
        <div class="info-row">
            <i class="fas fa-calendar"></i>
            <div class="info-label">Joined</div>
            <div class="info-value"><?= htmlspecialchars($user['created_at'] ?? 'N/A') ?></div>
        </div>
        <div class="info-row">
            <i class="fas fa-crown"></i>
            <div class="info-label">Subscription Tier</div>
            <div class="info-value" style="color:var(--gold); font-weight:700;"><?= htmlspecialchars($user['subscription_tier'] ?? 'Free') ?></div>
        </div>
        <?php if (!empty($user['subscription_expires_at'])): ?>
        <div class="info-row">
            <i class="fas fa-hourglass-half"></i>
            <div class="info-label">Expires At</div>
            <div class="info-value"><?= htmlspecialchars($user['subscription_expires_at']) ?></div>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /main -->
</body>
</html>
