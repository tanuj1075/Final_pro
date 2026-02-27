<?php
require_once 'security.php';
secure_session_start();
require_once 'db_helper.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php?error=Please login first');
    exit;
}

if (isset($_GET['logout'])) {
    destroy_session_and_cookie();
    header('Location: login.php?logout=1');
    exit;
}

$user = null;
$dbError = null;
try {
    $db = new DatabaseHelper();
    $user = $db->getUserById($_SESSION['user_id'] ?? 0);
    $db->close();
} catch (Exception $e) {
    error_log('User panel database warning: ' . $e->getMessage());
    $dbError = 'Unable to load live account details right now.';
}

if (!$user || (int)$user['is_active'] !== 1 || (int)$user['is_approved'] !== 1) {
    // User missing/deactivated/unapproved in DB: force logout for safety.
    destroy_session_and_cookie();
    header('Location: login.php?error=Account state changed. Please login again.');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel - Crunchrolly</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #e2e8f0;
        }
        .panel-wrap {
            max-width: 980px;
            margin: 40px auto;
            padding: 0 16px;
        }
        .panel-card {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(2, 6, 23, 0.45);
        }
        .meta-label { color: #94a3b8; font-size: 0.88rem; }
        .meta-value { font-weight: 600; }
        .btn-main {
            background: linear-gradient(135deg, #22c55e, #0ea5e9);
            border: none;
        }
    </style>
</head>
<body>
    <div class="panel-wrap">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0"><i class="fas fa-user-circle"></i> User Panel</h3>
            <a href="?logout=1" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <?php if ($dbError): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Database warning: <?php echo htmlspecialchars($dbError); ?>
            </div>
        <?php endif; ?>

        <div class="panel-card p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="meta-label">Username</div>
                    <div class="meta-value"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="col-md-6">
                    <div class="meta-label">Email</div>
                    <div class="meta-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="col-md-6">
                    <div class="meta-label">Account Status</div>
                    <div class="meta-value"><?php echo (int)$user['is_approved'] === 1 ? 'Approved' : 'Pending'; ?></div>
                </div>
                <div class="col-md-6">
                    <div class="meta-label">Last Login</div>
                    <div class="meta-value"><?php echo htmlspecialchars($user['last_login'] ?: 'First login'); ?></div>
                </div>
            </div>

            <hr class="my-4" style="border-color: rgba(148,163,184,0.2)">

            <div class="d-flex flex-wrap gap-2">
                <a href="ash.php?from_panel=1" class="btn btn-main text-white">
                    <i class="fas fa-tv"></i> Open Anime Home
                </a>
                <a href="manga.html" class="btn btn-outline-info">
                    <i class="fas fa-book-open"></i> Manga
                </a>
                <a href="video.html" class="btn btn-outline-warning">
                    <i class="fas fa-play-circle"></i> Videos
                </a>
            </div>
        </div>
    </div>
</body>
</html>
