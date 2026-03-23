<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AckerStream</title>
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
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: var(--card-bg);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 32px;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 48px;
        }
        .sidebar-header i { font-size: 32px; color: var(--primary); }
        .sidebar-header span { font-size: 20px; font-weight: 700; }
        .nav-items { flex: 1; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 8px;
            transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 75, 43, 0.1);
            color: var(--primary);
        }
        .main-content { flex: 1; padding: 48px; overflow-y: auto; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
        }
        .stat-card {
            background: var(--card-bg);
            padding: 24px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .stat-card .label { color: #94a3b8; font-size: 14px; margin-bottom: 8px; }
        .stat-card .value { font-size: 32px; font-weight: 700; }
        .section-title { font-size: 24px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .section-title i { color: var(--accent); }
        .data-table {
            width: 100%;
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-collapse: collapse;
        }
        .data-table th { background: rgba(255, 255, 255, 0.05); text-align: left; padding: 16px; color: #94a3b8; font-weight: 600; font-size: 14px; }
        .data-table td { padding: 16px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: opacity 0.2s;
        }
        .btn-approve { background: #10b981; color: white; }
        .btn-reject { background: #ef4444; color: white; margin-left: 8px; }
        .flash {
            padding: 16px;
            border-radius: 12px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
            color: var(--accent);
            margin-bottom: 32px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-bolt"></i>
            <span>Admin Central</span>
        </div>
        <div class="nav-items">
            <a href="index.php" class="nav-item active"><i class="fas fa-home"></i> Metrics</a>
            <a href="manage_anime.php" class="nav-item"><i class="fas fa-film"></i> Anime Catalog</a>
            <a href="ash.php" class="nav-item"><i class="fas fa-external-link-alt"></i> Visit Site</a>
        </div>
        <a href="?action=logout" class="nav-item" style="margin-top: auto; color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">
        <?php if ($flashMessage): ?>
            <div class="flash"><?= htmlspecialchars($flashMessage) ?></div>
        <?php endif; ?>

        <h2 class="section-title"><i class="fas fa-chart-pie"></i> Quick Stats</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Users</div>
                <div class="value"><?= (int)$userStats['total'] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Pending Approval</div>
                <div class="value" style="color: var(--primary);"><?= (int)$userStats['pending'] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Anime Titles</div>
                <div class="value"><?= (int)$animeCount ?></div>
            </div>
        </div>

        <h2 class="section-title"><i class="fas fa-user-clock"></i> User Approvals</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Date Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pendingUsers)): ?>
                    <tr><td colspan="4" style="text-align: center; color: #64748b;">All caught up! No pending approvals.</td></tr>
                <?php else: ?>
                    <?php foreach ($pendingUsers as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['created_at'] ?? 'N/A') ?></td>
                            <td>
                                <form method="POST" action="?action=approve" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="approve_user" value="<?= (int)$u['id'] ?>">
                                    <button class="btn-action btn-approve">Approve</button>
                                </form>
                                <form method="POST" action="?action=reject" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                                    <input type="hidden" name="reject_user" value="<?= (int)$u['id'] ?>">
                                    <button class="btn-action btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
