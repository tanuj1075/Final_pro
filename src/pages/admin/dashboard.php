<?php
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../../utils/bootstrap.php';

use App\Database\Connection;
use App\Repositories\AnimeRepository;
use App\Repositories\UserRepository;

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin?error=Admin login required');
    exit;
}

$flashMessage = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

// ── Bootstrap all data ──────────────────────────────────────────────────────
$userStats      = ['total' => 0, 'approved' => 0, 'pending' => 0];
$animeCount     = 0;
$pendingUsers   = [];
$totalViews     = 0;
$mostWatched    = [];
$mostActive     = [];
$uploadedAnime  = [];

try {
    $db        = Connection::getInstance();
    $userRepo  = new UserRepository($db);
    $animeRepo = new AnimeRepository($db);

    $pendingUsers = $userRepo->getPendingUsers();
    $userStats    = $userRepo->getUserStats();
    $animeCount   = $animeRepo->getAnimeCount();

    // Ensure activity tables exist
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

    // Total views
    $totalViews = (int)$db->query("SELECT COUNT(*) FROM user_history")->fetchColumn();

    // Most watched anime (top 8 by view count)
    $mostWatched = $db->query(
        "SELECT anime_id, COUNT(*) AS views
         FROM user_history
         GROUP BY anime_id
         ORDER BY views DESC
         LIMIT 8"
    )->fetchAll();

    // Most active users (top 5)
    $mostActive = $db->query(
        "SELECT u.username, u.email, COUNT(h.id) AS total_watches
         FROM user_history h
         JOIN admin_panel_siteuser u ON u.id = h.user_id
         GROUP BY h.user_id
         ORDER BY total_watches DESC
         LIMIT 5"
    )->fetchAll();

    // Uploaded anime list for content management
    $uploadedAnime = $db->query(
        "SELECT a.id, a.title, a.status, a.rating, a.created_at, a.cover_image
         FROM admin_panel_anime a
         ORDER BY a.created_at DESC
         LIMIT 20"
    )->fetchAll();

} catch (\Exception $e) {
    error_log('[AdminDashboard] ' . $e->getMessage());
    $flashMessage = 'Database warning: ' . $e->getMessage();
}

// Resolve anime titles for most-watched (from DB first, else label as API)
$isVercel = (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false);
$cacheDir = $isVercel ? '/tmp/ackerstream_cache' : __DIR__ . '/../../cache';
$watchedResolved = [];
foreach ($mostWatched as $row) {
    $aid = (int)$row['anime_id'];
    // Check uploaded DB first
    try {
        $stmt = $db->prepare("SELECT title, cover_image FROM admin_panel_anime WHERE id = ?");
        $stmt->execute([$aid]);
        $dbAnime = $stmt->fetch();
    } catch (\Exception $e) { $dbAnime = false; }

    if ($dbAnime) {
        $watchedResolved[] = [
            'id'    => $aid,
            'title' => $dbAnime['title'],
            'image' => $dbAnime['cover_image'] ? '/src/assets/anime/' . $dbAnime['cover_image'] : '',
            'views' => $row['views'],
            'type'  => 'uploaded',
        ];
    } else {
        // Try local JSON cache (from Jikan)
        $cf = is_dir($cacheDir) ? ($cacheDir . '/anime_' . $aid . '_cache.json') : '';
        $apiAnime = ($cf && file_exists($cf)) ? json_decode(file_get_contents($cf), true) : null;
        $watchedResolved[] = [
            'id'    => $aid,
            'title' => $apiAnime['title_english'] ?? $apiAnime['title'] ?? 'Anime #' . $aid,
            'image' => $apiAnime['images']['jpg']['image_url'] ?? '',
            'views' => $row['views'],
            'type'  => 'api',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AckerStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #ff4b2b;
            --secondary: #ff416c;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #38bdf8;
            --border: rgba(255,255,255,0.08);
            --green: #10b981;
            --yellow: #fbbf24;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Outfit',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        /* ── SIDEBAR ── */
        .sidebar { width:260px; background:var(--card-bg); border-right:1px solid var(--border); padding:28px 20px; display:flex; flex-direction:column; flex-shrink:0; }
        .sidebar-header { display:flex; align-items:center; gap:10px; margin-bottom:40px; }
        .sidebar-header i { font-size:28px; color:var(--primary); }
        .sidebar-header span { font-size:18px; font-weight:700; }
        .nav-item { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px; color:var(--muted); text-decoration:none; margin-bottom:6px; transition:all .2s; font-size:14px; cursor:pointer; border:none; background:none; width:100%; }
        .nav-item:hover, .nav-item.active { background:rgba(255,75,43,.12); color:var(--primary); }
        .nav-section { font-size:11px; color:#475569; text-transform:uppercase; letter-spacing:.1em; margin:18px 0 8px 14px; }

        /* ── MAIN ── */
        .main { flex:1; padding:40px; overflow-y:auto; }
        .page-header { margin-bottom:36px; }
        .page-header h1 { font-size:26px; font-weight:700; margin-bottom:4px; }
        .page-header p { color:var(--muted); font-size:14px; }

        /* ── STATS GRID ── */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:20px; margin-bottom:40px; }
        .stat-card { background:var(--card-bg); padding:22px; border-radius:18px; border:1px solid var(--border); }
        .stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; margin-bottom:14px; }
        .stat-card .label { color:var(--muted); font-size:13px; margin-bottom:6px; }
        .stat-card .value { font-size:30px; font-weight:700; }

        /* ── SECTION TITLE ── */
        .section-title { font-size:20px; font-weight:700; margin-bottom:18px; display:flex; align-items:center; gap:10px; }
        .section-title i { color:var(--accent); }
        .section-title small { color:var(--muted); font-size:13px; font-weight:400; margin-left:auto; }

        /* ── TABLES ── */
        .data-table { width:100%; background:var(--card-bg); border-radius:16px; overflow:hidden; border:1px solid var(--border); border-collapse:collapse; margin-bottom:40px; }
        .data-table th { background:rgba(255,255,255,.04); text-align:left; padding:14px 16px; color:var(--muted); font-weight:600; font-size:13px; border-bottom:1px solid var(--border); }
        .data-table td { padding:13px 16px; border-bottom:1px solid var(--border); font-size:14px; vertical-align:middle; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tr:hover td { background:rgba(255,255,255,.02); }

        /* ── BADGES ── */
        .badge { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-green { background:rgba(16,185,129,.12); color:#34d399; }
        .badge-red { background:rgba(239,68,68,.12); color:#f87171; }
        .badge-yellow { background:rgba(251,191,36,.12); color:var(--yellow); }
        .badge-blue { background:rgba(56,189,248,.12); color:var(--accent); }

        /* ── BUTTONS ── */
        .btn-action { padding:5px 12px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:none; transition:opacity .2s; }
        .btn-approve { background:var(--green); color:#fff; }
        .btn-danger  { background:#ef4444; color:#fff; margin-left:6px; }
        .btn-info    { background:var(--accent); color:#0f172a; margin-left:6px; }
        .btn-warn    { background:var(--yellow); color:#0f172a; }

        /* ── CONTENT TABS ── */
        .tab-bar { display:flex; gap:4px; background:var(--card-bg); border-radius:12px; padding:4px; margin-bottom:28px; border:1px solid var(--border); width:fit-content; }
        .tab-btn { padding:8px 20px; border-radius:9px; border:none; background:transparent; color:var(--muted); font-size:14px; font-weight:600; cursor:pointer; transition:all .2s; }
        .tab-btn.active { background:var(--primary); color:#fff; }

        /* ── CONTENT PANELS ── */
        .tab-panel { display:none; }
        .tab-panel.active { display:block; }

        /* ── MOST WATCHED GRID ── */
        .watched-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:14px; margin-bottom:40px; }
        .watched-card { background:var(--card-bg); border-radius:12px; overflow:hidden; border:1px solid var(--border); }
        .watched-card img { width:100%; aspect-ratio:2/3; object-fit:cover; display:block; }
        .watched-card-body { padding:10px; }
        .watched-card-title { font-size:12px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:4px; }
        .watched-card-views { font-size:11px; color:var(--muted); }
        .type-tag { display:inline-block; font-size:10px; padding:2px 7px; border-radius:10px; margin-bottom:4px; }

        /* ── SEARCH ── */
        .search-bar { display:flex; gap:10px; margin-bottom:16px; }
        .search-bar input { flex:1; background:rgba(255,255,255,.06); border:1px solid var(--border); color:#fff; padding:10px 16px; border-radius:10px; font-size:14px; }
        .search-bar input::placeholder { color:var(--muted); }

        /* ── FLASH ── */
        .flash { padding:14px 18px; border-radius:12px; background:rgba(56,189,248,.1); border:1px solid rgba(56,189,248,.2); color:var(--accent); margin-bottom:28px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-bolt"></i>
        <span>Admin Central</span>
    </div>

    <div class="nav-section">Overview</div>
    <button class="nav-item active" onclick="showTab('dashboard')"><i class="fas fa-chart-pie"></i> Dashboard</button>
    <button class="nav-item" onclick="showTab('analytics')"><i class="fas fa-fire"></i> Analytics</button>

    <div class="nav-section">Users</div>
    <button class="nav-item" onclick="showTab('approvals')"><i class="fas fa-user-clock"></i> Approvals <span id="pendingBadge" style="margin-left:auto;background:var(--primary);color:#fff;border-radius:20px;padding:2px 8px;font-size:11px;"><?= count($pendingUsers) ?></span></button>
    <button class="nav-item" onclick="showTab('users')"><i class="fas fa-users"></i> All Users</button>

    <div class="nav-section">Content</div>
    <button class="nav-item" onclick="showTab('content')"><i class="fas fa-film"></i> Anime Library</button>
    <a href="/admin/upload_video" class="nav-item"><i class="fas fa-upload"></i> Upload Video</a>
    <a href="/admin/manage_manga" class="nav-item"><i class="fas fa-book-open"></i> Manage Manga</a>

    <div class="nav-section">Account</div>
    <a href="/admin/admin_profile" class="nav-item"><i class="fas fa-user-cog"></i> My Profile</a>

    <div class="nav-section">Site</div>
    <a href="/ash.php" class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> Visit Site</a>
    <a href="/admin?action=logout" class="nav-item" style="margin-top:auto; color:#f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- MAIN CONTENT -->
<div class="main">

    <?php if ($flashMessage): ?>
        <div class="flash"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($flashMessage) ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></strong> &nbsp;·&nbsp; <?= date('D, d M Y · H:i') ?></p>
    </div>

    <!-- ══════════════════════════════ TAB: DASHBOARD ══════════════════════════ -->
    <div class="tab-panel active" id="tab-dashboard">

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,.15); color:#818cf8;"><i class="fas fa-users"></i></div>
                <div class="label">Total Users</div>
                <div class="value"><?= (int)$userStats['total'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(255,75,43,.15); color:var(--primary);"><i class="fas fa-user-clock"></i></div>
                <div class="label">Pending Approval</div>
                <div class="value" style="color:var(--primary);"><?= (int)$userStats['pending'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,.15); color:var(--green);"><i class="fas fa-film"></i></div>
                <div class="label">Uploaded Anime</div>
                <div class="value"><?= (int)$animeCount ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(56,189,248,.15); color:var(--accent);"><i class="fas fa-eye"></i></div>
                <div class="label">Total Views</div>
                <div class="value"><?= number_format($totalViews) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(251,191,36,.15); color:var(--yellow);"><i class="fas fa-heart"></i></div>
                <div class="label">API Anime (Jikan)</div>
                <div class="value">25,000+</div>
            </div>
        </div>

        <!-- Pending Approvals Quick-view -->
        <?php if (!empty($pendingUsers)): ?>
        <div class="section-title"><i class="fas fa-user-check"></i> Pending Approvals</div>
        <table class="data-table">
            <thead><tr><th>Username</th><th>Email</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($pendingUsers as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars(substr($u['created_at'] ?? '', 0, 10)) ?></td>
                    <td>
                        <form method="POST" action="/admin?action=approve" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="approve_user" value="<?= (int)$u['id'] ?>">
                            <button class="btn-action btn-approve">Approve</button>
                        </form>
                        <form method="POST" action="/admin?action=reject" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="reject_user" value="<?= (int)$u['id'] ?>">
                            <button class="btn-action btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div><!-- /tab-dashboard -->

    <!-- ══════════════════════════════ TAB: ANALYTICS ══════════════════════════ -->
    <div class="tab-panel" id="tab-analytics">

        <div class="section-title"><i class="fas fa-fire"></i> Most Watched Anime</div>
        <?php if (!empty($watchedResolved)): ?>
        <div class="watched-grid">
            <?php foreach ($watchedResolved as $w): ?>
            <div class="watched-card">
                <?php if ($w['image']): ?>
                    <img src="<?= htmlspecialchars($w['image']) ?>" alt="<?= htmlspecialchars($w['title']) ?>" onerror="this.style.display='none'">
                <?php else: ?>
                    <div style="aspect-ratio:2/3;background:#1e293b;display:flex;align-items:center;justify-content:center;color:#475569;"><i class="fas fa-image" style="font-size:32px;"></i></div>
                <?php endif; ?>
                <div class="watched-card-body">
                    <div class="type-tag <?= $w['type'] === 'uploaded' ? 'badge-green' : 'badge-blue' ?>">
                        <?= $w['type'] === 'uploaded' ? 'Uploaded' : 'Jikan API' ?>
                    </div>
                    <div class="watched-card-title"><?= htmlspecialchars($w['title']) ?></div>
                    <div class="watched-card-views"><i class="fas fa-eye" style="font-size:10px;"></i> <?= number_format($w['views']) ?> views</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p style="color:var(--muted); padding:20px 0;">No watch history recorded yet. Views will appear here as users watch anime.</p>
        <?php endif; ?>

        <div class="section-title"><i class="fas fa-trophy"></i> Most Active Users</div>
        <?php if (!empty($mostActive)): ?>
        <table class="data-table">
            <thead><tr><th>Username</th><th>Email</th><th>Total Watches</th></tr></thead>
            <tbody>
            <?php foreach ($mostActive as $a): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($a['username']) ?></strong></td>
                    <td><?= htmlspecialchars($a['email']) ?></td>
                    <td><span class="badge badge-blue"><i class="fas fa-play"></i> <?= number_format($a['total_watches']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="color:var(--muted);">No activity data yet.</p>
        <?php endif; ?>

    </div><!-- /tab-analytics -->

    <!-- ══════════════════════════════ TAB: APPROVALS ══════════════════════════ -->
    <div class="tab-panel" id="tab-approvals">
        <div class="section-title"><i class="fas fa-user-check"></i> User Approvals <small><?= count($pendingUsers) ?> pending</small></div>
        <table class="data-table">
            <thead><tr><th>Username</th><th>Email</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($pendingUsers)): ?>
                <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:30px;">No pending approvals. All caught up! 🎉</td></tr>
            <?php else: ?>
                <?php foreach ($pendingUsers as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars(substr($u['created_at'] ?? '', 0, 10)) ?></td>
                    <td>
                        <form method="POST" action="/admin?action=approve" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="approve_user" value="<?= (int)$u['id'] ?>">
                            <button class="btn-action btn-approve">Approve</button>
                        </form>
                        <form method="POST" action="/admin?action=reject" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="reject_user" value="<?= (int)$u['id'] ?>">
                            <button class="btn-action btn-danger">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div><!-- /tab-approvals -->

    <!-- ══════════════════════════════ TAB: ALL USERS ══════════════════════════ -->
    <div class="tab-panel" id="tab-users">
        <div class="section-title"><i class="fas fa-users"></i> All Users <small>Live updates every 10s</small></div>
        <div class="search-bar">
            <input type="text" id="userSearch" placeholder="Search by username or email…" oninput="filterUsers(this.value)">
        </div>
        <table class="data-table" id="all-users-table">
            <thead>
                <tr>
                    <th>User</th><th>Email</th><th>Status</th><th>Last Login</th><th>Last Logout</th>
                    <th>Reg. IP</th><th>Watches</th><th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:30px;">Loading users…</td></tr>
            </tbody>
        </table>
    </div><!-- /tab-users -->

    <!-- ══════════════════════════════ TAB: CONTENT ══════════════════════════ -->
    <div class="tab-panel" id="tab-content">
        <div class="section-title"><i class="fas fa-film"></i> Uploaded Anime Library <small><a href="/admin/upload_video" style="color:var(--accent);text-decoration:none;">+ Upload New</a></small></div>
        <table class="data-table">
            <thead><tr><th>Cover</th><th>Title</th><th>Status</th><th>Rating</th><th>Added</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (empty($uploadedAnime)): ?>
                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px;">No uploaded anime yet. <a href="/admin/upload_video" style="color:var(--accent);">Upload your first one!</a></td></tr>
            <?php else: ?>
                <?php foreach ($uploadedAnime as $anime): ?>
                <tr>
                    <td>
                        <?php if ($anime['cover_image']): ?>
                            <img src="/src/assets/anime/<?= htmlspecialchars($anime['cover_image']) ?>" style="width:48px;height:64px;object-fit:cover;border-radius:6px;" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div style="width:48px;height:64px;background:#1e293b;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#475569;font-size:18px;"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($anime['title']) ?></strong></td>
                    <td>
                        <?php if ($anime['status'] === 'published'): ?>
                            <span class="badge badge-green">Published</span>
                        <?php elseif ($anime['status'] === 'ongoing'): ?>
                            <span class="badge badge-yellow">Ongoing</span>
                        <?php else: ?>
                            <span class="badge badge-blue"><?= htmlspecialchars($anime['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format((float)$anime['rating'], 1) ?></td>
                    <td><?= htmlspecialchars(substr($anime['created_at'] ?? '', 0, 10)) ?></td>
                    <td style="white-space:nowrap;">
                        <a href="/admin/edit_anime?id=<?= (int)$anime['id'] ?>" class="btn-action" style="background:rgba(56,189,248,.1); color:var(--accent); text-decoration:none;"><i class="fas fa-edit"></i> Edit</a>
                        <form method="POST" action="/admin?action=delete_anime" style="display:inline;"
                              onsubmit="return confirm('Delete \'<?= htmlspecialchars(addslashes($anime['title'])) ?>\'? This cannot be undone.');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                            <input type="hidden" name="anime_id" value="<?= (int)$anime['id'] ?>">
                            <button class="btn-action btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div><!-- /tab-content -->

</div><!-- /main -->

<script>
    const CSRF_TOKEN = '<?= htmlspecialchars(csrf_token()) ?>';
    let allUsersCache = [];

    // ── Tab Navigation ────────────────────────────────────────────────────────
    function showTab(name) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.getElementById('tab-' + name)?.classList.add('active');
        event.currentTarget.classList.add('active');
        if (name === 'users') loadUsers();
    }

    // ── User table load ───────────────────────────────────────────────────────
    function renderUsers(users) {
        const tbody = document.getElementById('users-tbody');
        if (!users.length) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#64748b;padding:30px;">No users found.</td></tr>';
            return;
        }
        tbody.innerHTML = users.map(u => {
            const statusColor = u.status === 'online' ? '#10b981' : '#64748b';
            const isActive    = parseInt(u.is_active) === 1;
            const loginTxt    = u.last_login  ?? 'Never';
            const logoutTxt   = u.last_logout ?? '—';
            const regIp       = u.registration_ip ?? '—';
            return `<tr>
                <td><strong>${u.username}</strong></td>
                <td>${u.email}</td>
                <td><span style="color:${statusColor};font-size:12px;font-weight:600;">● ${u.status?.toUpperCase() ?? 'OFFLINE'}</span>
                    ${parseInt(u.is_active) === 0 ? '<span class="badge badge-red" style="margin-left:6px;">Banned</span>' : ''}
                </td>
                <td style="font-size:13px;">${loginTxt}</td>
                <td style="font-size:13px;">${logoutTxt}</td>
                <td style="font-size:12px;color:#94a3b8;">${regIp}</td>
                <td><a href="/admin/user_detail?id=${u.id}" class="btn-action btn-info" style="text-decoration:none;">View</a></td>
                <td>
                    <form method="POST" action="/admin?action=${isActive?'block':'unblock'}" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                        <input type="hidden" name="${isActive?'block_user':'unblock_user'}" value="${u.id}">
                        <button class="btn-action ${isActive?'btn-warn':'btn-approve'}">${isActive?'Ban':'Unban'}</button>
                    </form>
                    <form method="POST" action="/admin?action=delete" style="display:inline;" onsubmit="return confirm('Delete this user permanently?');">
                        <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                        <input type="hidden" name="delete_user" value="${u.id}">
                        <button class="btn-action btn-danger">Delete</button>
                    </form>
                </td>
            </tr>`;
        }).join('');
    }

    function loadUsers() {
        fetch('/api/users.php')
            .then(r => r.json())
            .then(data => {
                allUsersCache = data.users ?? [];
                renderUsers(allUsersCache);
            })
            .catch(err => console.error('Error fetching users:', err));
    }

    function filterUsers(query) {
        const q = query.toLowerCase();
        renderUsers(allUsersCache.filter(u =>
            u.username.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
        ));
    }

    // Auto-refresh user list if that tab is active
    setInterval(() => {
        if (document.getElementById('tab-users')?.classList.contains('active')) loadUsers();
    }, 10000);
</script>
</body>
</html>
