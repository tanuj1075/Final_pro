<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - AckerStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 40px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .user-info { display: flex; align-items: center; gap: 20px; }
        .avatar { width: 64px; height: 64px; background: var(--primary); border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; font-weight: 700; }
        .details h1 { font-size: 24px; margin-bottom: 4px; }
        .details p { color: #94a3b8; font-size: 14px; }
        .card { background: var(--card-bg); border-radius: 24px; padding: 32px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .info-row { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }
        .info-row i { color: var(--primary); font-size: 20px; width: 24px; }
        .info-row .label { color: #94a3b8; font-size: 14px; flex: 1; }
        .info-row .value { font-weight: 500; }
        .btn-portal {
            display: inline-block;
            padding: 12px 24px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
            margin-top: 20px;
        }
        .btn-portal:hover { background: var(--primary); color: white; }
        .error-card { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #fca5a5; padding: 16px; border-radius: 12px; margin-bottom: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <div class="avatar"><?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?></div>
                <div class="details">
                    <h1>Welcome, <?= htmlspecialchars($user['username'] ?? 'User') ?></h1>
                    <p>Member since <?= htmlspecialchars($user['created_at'] ?? 'recent') ?></p>
                </div>
            </div>
            <a href="?logout=1" class="btn-portal" style="color: #f87171; background: rgba(248, 113, 113, 0.1);">Logout</a>
        </div>

        <?php if ($dbError): ?>
            <div class="error-card">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($dbError) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2 style="margin-bottom: 24px; font-size: 18px;">Account Details</h2>
            <div class="info-row">
                <i class="fas fa-envelope"></i>
                <div class="label">Email Address</div>
                <div class="value"><?= htmlspecialchars($user['email'] ?? 'N/A') ?></div>
            </div>
            <div class="info-row">
                <i class="fas fa-check-circle"></i>
                <div class="label">Status</div>
                <div class="value" style="color: #10b981;">Approved & Active</div>
            </div>
            <div class="info-row">
                <i class="fas fa-id-badge"></i>
                <div class="label">User ID</div>
                <div class="value">#<?= (int)($user['id'] ?? 0) ?></div>
            </div>

            <a href="ash.php" class="btn-portal">Go to Home Feed</a>
        </div>
    </div>
</body>
</html>
