<?php
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../../utils/bootstrap.php';

use App\Database\Connection;
use App\Repositories\AnimeRepository;
use App\Repositories\UserRepository;

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /index.php?error=Admin login required');
    exit;
}

// When this file is reached directly (not through AdminController), hydrate dashboard data here.
if (!isset($userStats, $animeCount, $pendingUsers)) {
    $flashMessage = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    try {
        $db = Connection::getInstance();
        $userRepo = new UserRepository($db);
        $animeRepo = new AnimeRepository($db);
        $pendingUsers = $userRepo->getPendingUsers();
        $userStats = $userRepo->getUserStats();
        $animeCount = $animeRepo->getAnimeCount();
    } catch (\Exception $e) {
        $flashMessage = 'Database warning: unable to load admin metrics right now.';
        $pendingUsers = [];
        $userStats = ['total' => 0, 'pending' => 0];
        $animeCount = 0;
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
            <a href="/admin/dashboard" class="nav-item active"><i class="fas fa-home"></i> Metrics</a>
            <a href="/admin/manage_anime" class="nav-item"><i class="fas fa-film"></i> Anime Catalog</a>
            <a href="/admin/upload_video" class="nav-item"><i class="fas fa-upload"></i> Upload Video</a>
            <a href="/ash.php" class="nav-item"><i class="fas fa-external-link-alt"></i> Visit Site</a>
        </div>
        <a href="/index.php?action=logout" class="nav-item" style="margin-top: auto; color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
        <h2 class="section-title" style="margin-top: 48px;"><i class="fas fa-users"></i> All Users (Live Tracking)</h2>
        <table class="data-table" id="all-users-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Last Logout</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                <tr><td colspan="6" style="text-align: center; color: #64748b;">Loading active users...</td></tr>
            </tbody>
        </table>
    </div>

    <script>
        const CSRF_TOKEN = '<?= htmlspecialchars(csrf_token()) ?>';

        function loadUsers() {
            fetch('/api/users.php')
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById('users-tbody');
                    if (data.success && data.users.length > 0) {
                        tbody.innerHTML = '';
                        data.users.forEach(u => {
                            const statusColor = u.status === 'online' ? '#10b981' : '#64748b';
                            const statusHtml = `<span style="color: ${statusColor}; font-weight: bold;"><i class="fas fa-circle" style="font-size: 10px;"></i> ${u.status.toUpperCase()}</span>`;
                            const loginTxt = u.last_login ? u.last_login : 'Never';
                            const logoutTxt = u.last_logout ? u.last_logout : '-';
                            
                            const isActive = parseInt(u.is_active) === 1;
                            const actionBtnClass = isActive ? 'btn-reject' : 'btn-approve';
                            const actionBtnText = isActive ? 'Block' : 'Unblock';
                            const actionType = isActive ? 'block' : 'unblock';
                            const actionInputName = isActive ? 'block_user' : 'unblock_user';

                            tbody.innerHTML += `
                                <tr>
                                    <td>${u.username}</td>
                                    <td>${u.email}</td>
                                    <td>${statusHtml}</td>
                                    <td>${loginTxt}</td>
                                    <td>${logoutTxt}</td>
                                    <td>
                                        <form method="POST" action="?action=${actionType}" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="${CSRF_TOKEN}">
                                            <input type="hidden" name="${actionInputName}" value="${u.id}">
                                            <button class="btn-action ${actionBtnClass}">${actionBtnText}</button>
                                        </form>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b;">No users found.</td></tr>';
                    }
                })
                .catch(err => console.error('Error fetching users:', err));
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadUsers();
            // Refresh every 10 seconds dynamically
            setInterval(loadUsers, 10000);
        });
    </script>
</body>
</html>
