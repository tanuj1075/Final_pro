<?php
require_once __DIR__ . '/../../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../../utils/bootstrap.php';

use App\Database\Connection;

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php?error=Admin login required');
    exit;
}

$db = Connection::getInstance();

// Ensure admin_profile table exists
$db->exec("CREATE TABLE IF NOT EXISTS admin_profile (
    id INTEGER PRIMARY KEY DEFAULT 1,
    username TEXT NOT NULL DEFAULT 'admin',
    display_name TEXT NOT NULL DEFAULT 'Administrator',
    bio TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    avatar_color TEXT NOT NULL DEFAULT '#ff4b2b',
    password_hash TEXT NULL,
    updated_at TEXT NOT NULL DEFAULT (datetime('now'))
)");

$columns = $db->query("PRAGMA table_info(admin_profile)")->fetchAll();
$hasPasswordHashColumn = false;
foreach ($columns as $column) {
    if (($column['name'] ?? '') === 'password_hash') {
        $hasPasswordHashColumn = true;
        break;
    }
}
if (!$hasPasswordHashColumn) {
    $db->exec("ALTER TABLE admin_profile ADD COLUMN password_hash TEXT NULL");
}

// Seed default row if empty
$count = (int)$db->query("SELECT COUNT(*) FROM admin_profile")->fetchColumn();
if ($count === 0) {
    $db->exec("INSERT INTO admin_profile (id, username, display_name) VALUES (1, 'admin', 'Administrator')");
}

$flash = '';
$flashType = 'success';

// ── Handle form submissions ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash = 'Invalid request token. Please refresh and try again.';
        $flashType = 'error';
    } else {
        $action = $_POST['form_action'] ?? '';

        if ($action === 'update_profile') {
            $displayName = trim($_POST['display_name'] ?? '');
            $bio         = trim($_POST['bio'] ?? '');
            $email       = trim($_POST['email'] ?? '');
            $color       = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['avatar_color'] ?? '') ? $_POST['avatar_color'] : '#ff4b2b';

            if ($displayName === '') {
                $flash = 'Display name cannot be empty.';
                $flashType = 'error';
            } else {
                $stmt = $db->prepare(
                    "UPDATE admin_profile SET display_name=?, bio=?, email=?, avatar_color=?, updated_at=datetime('now') WHERE id=1"
                );
                $stmt->execute([$displayName, $bio, $email, $color]);
                $_SESSION['admin_display_name'] = $displayName;
                $flash = 'Profile updated successfully!';
            }

        } elseif ($action === 'change_password') {
            $currentPw  = $_POST['current_password'] ?? '';
            $newPw      = $_POST['new_password'] ?? '';
            $confirmPw  = $_POST['confirm_password'] ?? '';

            $profilePasswordHash = (string)$db->query("SELECT COALESCE(password_hash, '') FROM admin_profile WHERE id=1")->fetchColumn();

            // Verify current password
            $envPassword = (string)(getenv('ADMIN_PASSWORD') ?: 'rkmb123#');
            $actualPw = $profilePasswordHash !== '' ? $profilePasswordHash : $envPassword;

            $currentMatch = ($currentPw === $actualPw) || password_verify($currentPw, $actualPw);

            if (!$currentMatch) {
                $flash = 'Current password is incorrect.';
                $flashType = 'error';
            } elseif (strlen($newPw) < 6) {
                $flash = 'New password must be at least 6 characters.';
                $flashType = 'error';
            } elseif ($newPw !== $confirmPw) {
                $flash = 'New passwords do not match.';
                $flashType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE admin_profile SET password_hash=?, updated_at=datetime('now') WHERE id=1");
                $stmt->execute([password_hash($newPw, PASSWORD_BCRYPT)]);
                $flash = 'Password changed successfully! Use the new password on next login.';
            }

        } elseif ($action === 'change_username') {
            $newUsername = trim($_POST['new_username'] ?? '');
            if ($newUsername === '') {
                $flash = 'Username cannot be empty.';
                $flashType = 'error';
            } else {
                $_SESSION['admin_username'] = $newUsername;

                $stmt = $db->prepare("UPDATE admin_profile SET username=?, updated_at=datetime('now') WHERE id=1");
                $stmt->execute([$newUsername]);
                $flash = 'Username changed to "' . htmlspecialchars($newUsername) . '"! Use it on next login.';
            }
        }
    }
}

// Load profile
$profile = $db->query("SELECT * FROM admin_profile WHERE id=1")->fetch();
$currentUsername = $profile['username'] ?? $_SESSION['admin_username'] ?? 'admin';
$displayName     = $profile['display_name'] ?? 'Administrator';
$bio             = $profile['bio'] ?? '';
$email           = $profile['email'] ?? '';
$avatarColor     = $profile['avatar_color'] ?? '#ff4b2b';
$avatarLetter    = strtoupper(substr($displayName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - AckerStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: <?= htmlspecialchars($avatarColor) ?>;
            --bg: #0f172a; --card: #1e293b; --text: #f8fafc;
            --muted: #94a3b8; --border: rgba(255,255,255,.08); --green: #10b981;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Outfit',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        /* SIDEBAR */
        .sidebar { width:240px; background:var(--card); border-right:1px solid var(--border); padding:24px 16px; display:flex; flex-direction:column; flex-shrink:0; }
        .sidebar-header { display:flex; align-items:center; gap:10px; margin-bottom:32px; }
        .sidebar-header i { font-size:24px; color:var(--primary); }
        .sidebar-header span { font-size:16px; font-weight:700; }
        .nav-item { display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px; color:var(--muted); text-decoration:none; margin-bottom:6px; transition:all .2s; font-size:14px; }
        .nav-item:hover, .nav-item.active { background:rgba(255,75,43,.12); color:var(--primary); }

        /* MAIN */
        .main { flex:1; padding:40px; overflow-y:auto; max-width:860px; }
        .page-title { font-size:24px; font-weight:700; margin-bottom:6px; }
        .page-sub { color:var(--muted); font-size:14px; margin-bottom:36px; }

        /* AVATAR HERO */
        .profile-hero { background:var(--card); border-radius:20px; padding:32px; border:1px solid var(--border); display:flex; align-items:center; gap:28px; margin-bottom:28px; }
        .avatar { width:88px; height:88px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:36px; font-weight:700; flex-shrink:0; background:var(--primary); box-shadow:0 0 40px rgba(255,75,43,.3); }
        .hero-info h2 { font-size:22px; margin-bottom:4px; }
        .hero-info p { color:var(--muted); font-size:14px; }
        .hero-info .bio { margin-top:8px; font-size:14px; color:#cbd5e1; font-style:italic; }

        /* CARDS */
        .card { background:var(--card); border-radius:18px; padding:28px; border:1px solid var(--border); margin-bottom:24px; }
        .card h3 { font-size:16px; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
        .card h3 i { color:var(--primary); }

        /* FORM */
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; margin-bottom:7px; color:var(--muted); font-size:13px; font-weight:600; }
        .form-control { width:100%; padding:11px 16px; border-radius:10px; border:1px solid var(--border); background:rgba(255,255,255,.05); color:#fff; font-size:14px; font-family:inherit; transition:border .2s; }
        .form-control:focus { outline:none; border-color:var(--primary); }
        textarea.form-control { resize:vertical; min-height:90px; }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .btn-save { background:var(--primary); color:#fff; border:none; padding:12px 28px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; transition:opacity .2s; }
        .btn-save:hover { opacity:.85; }
        .color-row { display:flex; align-items:center; gap:12px; }
        .color-preview { width:40px; height:40px; border-radius:50%; border:2px solid var(--border); flex-shrink:0; }

        /* FLASH */
        .flash { padding:14px 18px; border-radius:12px; margin-bottom:24px; font-size:14px; display:flex; align-items:center; gap:10px; }
        .flash.success { background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.2); color:#34d399; }
        .flash.error { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.2); color:#f87171; }

        /* DANGER ZONE */
        .danger-title { color:#f87171; }
        .btn-danger { background:rgba(239,68,68,.15); color:#f87171; border:1px solid rgba(239,68,68,.25); padding:11px 24px; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; transition:all .2s; }
        .btn-danger:hover { background:#ef4444; color:#fff; }

        /* PASSWORD STRENGTH */
        .strength-bar { height:4px; border-radius:4px; background:rgba(255,255,255,.1); margin-top:6px; overflow:hidden; }
        .strength-fill { height:100%; border-radius:4px; transition:all .3s; width:0%; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-bolt"></i>
        <span>Admin Central</span>
    </div>
    <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-pie"></i> Dashboard</a>
    <a href="upload_video.php" class="nav-item"><i class="fas fa-upload"></i> Upload Video</a>
    <a href="manage_manga.php" class="nav-item"><i class="fas fa-book-open"></i> Manage Manga</a>
    <a href="admin_profile.php" class="nav-item active"><i class="fas fa-user-cog"></i> My Profile</a>
    <a href="../../pages/ash.php" class="nav-item" target="_blank"><i class="fas fa-external-link-alt"></i> Visit Site</a>
    <a href="dashboard.php" class="nav-item" style="margin-top:auto;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <a href="../index.php?action=logout" class="nav-item" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- MAIN -->
<div class="main">
    <div class="page-title">Admin Profile</div>
    <div class="page-sub">Edit your account information, password, and appearance</div>

    <?php if ($flash): ?>
        <div class="flash <?= $flashType ?>">
            <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <!-- AVATAR HERO -->
    <div class="profile-hero">
        <div class="avatar" id="avatarPreview"><?= htmlspecialchars($avatarLetter) ?></div>
        <div class="hero-info">
            <h2><?= htmlspecialchars($displayName) ?></h2>
            <p>@<?= htmlspecialchars($currentUsername) ?> &nbsp;·&nbsp; <span style="color:var(--green);">● Active Admin</span></p>
            <?php if ($bio): ?><div class="bio">"<?= htmlspecialchars($bio) ?>"</div><?php endif; ?>
            <?php if ($email): ?><p style="margin-top:6px; font-size:13px;"><?= htmlspecialchars($email) ?></p><?php endif; ?>
        </div>
    </div>

    <!-- 1. EDIT PROFILE -->
    <div class="card">
        <h3><i class="fas fa-id-card"></i> Profile Details</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="update_profile">
            <div class="form-row">
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" class="form-control" value="<?= htmlspecialchars($displayName) ?>" required id="displayNameInput">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" placeholder="admin@ackerstream.com">
                </div>
            </div>
            <div class="form-group">
                <label>Bio / Tagline</label>
                <textarea name="bio" class="form-control" placeholder="Say something about yourself..."><?= htmlspecialchars($bio) ?></textarea>
            </div>
            <div class="form-group">
                <label>Avatar Color</label>
                <div class="color-row">
                    <div class="color-preview" id="colorPreview" style="background:<?= htmlspecialchars($avatarColor) ?>;"></div>
                    <input type="color" name="avatar_color" class="form-control" style="width:70px;padding:4px;cursor:pointer;" value="<?= htmlspecialchars($avatarColor) ?>" id="colorPicker">
                    <span style="font-size:13px;color:var(--muted);">Pick your avatar background color</span>
                </div>
            </div>
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Profile</button>
        </form>
    </div>

    <!-- 2. CHANGE USERNAME -->
    <div class="card">
        <h3><i class="fas fa-user-edit"></i> Change Login Username</h3>
        <p style="color:var(--muted); font-size:13px; margin-bottom:18px;">This changes the username you use to log in to the admin panel.</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="change_username">
            <div class="form-row">
                <div class="form-group">
                    <label>Current Username</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($currentUsername) ?>" disabled style="opacity:.5;">
                </div>
                <div class="form-group">
                    <label>New Username</label>
                    <input type="text" name="new_username" class="form-control" placeholder="e.g. superadmin" required>
                </div>
            </div>
            <button type="submit" class="btn-save"><i class="fas fa-user-check"></i> Update Username</button>
        </form>
    </div>

    <!-- 3. CHANGE PASSWORD -->
    <div class="card">
        <h3 class="danger-title"><i class="fas fa-key"></i> Change Password</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="form_action" value="change_password">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" id="newPw" class="form-control" required placeholder="At least 6 characters" oninput="checkStrength(this.value)">
                    <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat new password">
                </div>
            </div>
            <button type="submit" class="btn-save btn-danger"><i class="fas fa-lock"></i> Change Password</button>
        </form>
    </div>

</div><!-- /main -->

<script>
    // Live avatar color preview
    const colorPicker = document.getElementById('colorPicker');
    const colorPreview = document.getElementById('colorPreview');
    const avatarPreview = document.getElementById('avatarPreview');
    if (colorPicker) {
        colorPicker.addEventListener('input', e => {
            const v = e.target.value;
            colorPreview.style.background = v;
            avatarPreview.style.background = v;
            document.documentElement.style.setProperty('--primary', v);
        });
    }

    // Live avatar letter from display name
    const displayNameInput = document.getElementById('displayNameInput');
    if (displayNameInput) {
        displayNameInput.addEventListener('input', e => {
            avatarPreview.textContent = (e.target.value.trim().charAt(0) || 'A').toUpperCase();
        });
    }

    // Password strength indicator
    function checkStrength(pw) {
        const fill = document.getElementById('strengthFill');
        let score = 0;
        if (pw.length >= 6)  score += 25;
        if (pw.length >= 10) score += 25;
        if (/[A-Z]/.test(pw)) score += 25;
        if (/[0-9!@#$%^&*]/.test(pw)) score += 25;
        fill.style.width = score + '%';
        fill.style.background = score <= 25 ? '#ef4444' : score <= 50 ? '#f59e0b' : score <= 75 ? '#3b82f6' : '#10b981';
    }
</script>
</body>
</html>
