<?php
// Ensure sessions work on serverless/readonly filesystems (e.g., Vercel).
$sessionDir = sys_get_temp_dir() . '/final_pro_sessions';
if (!is_dir($sessionDir)) {
    @mkdir($sessionDir, 0755, true);
}
if (is_dir($sessionDir) && is_writable($sessionDir)) {
    ini_set('session.save_path', $sessionDir);
}
session_start();
require_once 'db_helper.php';

// ===================== CONFIGURATION =====================
$ADMIN_USERNAME = getenv('ADMIN_USERNAME') ?: 'admin';
$ADMIN_PASSWORD = getenv('ADMIN_PASSWORD') ?: 'password123'; // Change this

// Optional hash-based password (recommended for production)
$PASSWORD_HASH = getenv('ADMIN_PASSWORD_HASH') ?: '$2y$10$YourHashHere'; // Generate with: echo password_hash('your_password', PASSWORD_DEFAULT);
// =========================================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle logout
if(isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Check if user is trying to access main site
if(isset($_GET['access']) && $_GET['access'] === 'main') {
    if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: index.php?error=Please login first to access the main site');
        exit;
    } else {
        // Admin is logged in, redirect to main site
        header('Location: ash.php');  // We'll rename ash.html to ash.php
        exit;
    }
}

// Check login attempt
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = 'Invalid request token. Please refresh and try again.';
    } else {
        $passwordMatch = $password === $ADMIN_PASSWORD;
    if (!$passwordMatch && $PASSWORD_HASH !== '$2y$10$YourHashHere') {
        $passwordMatch = password_verify($password, $PASSWORD_HASH);
    }

    if($username === $ADMIN_USERNAME && $passwordMatch) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['login_time'] = time();

        // Redirect to admin panel
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password!";
    }
    }
}

// Handle user approval/rejection from admin panel
if (
    isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true &&
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (isset($_POST['approve_user']) || isset($_POST['reject_user']))
) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $_SESSION['admin_flash'] = 'Invalid request token. Please refresh and try again.';
        header('Location: index.php');
        exit;
    }

    try {
        $db = new DatabaseHelper();

        if (isset($_POST['approve_user'])) {
            $approveUserId = intval($_POST['approve_user']);
            $approved = $db->approveUser($approveUserId);
            $_SESSION['admin_flash'] = $approved
                ? 'User approved successfully.'
                : 'Unable to approve user (user may already be approved).';
        }

        if (isset($_POST['reject_user'])) {
            $rejectUserId = intval($_POST['reject_user']);
            $rejected = $db->rejectUser($rejectUserId);
            $_SESSION['admin_flash'] = $rejected
                ? 'User rejected and disabled successfully.'
                : 'Unable to reject user.';
        }

        $db->close();
    } catch (Exception $e) {
        $_SESSION['admin_flash'] = 'Action failed: ' . $e->getMessage();
    }

    header('Location: index.php');
    exit;
}

// If admin is already logged in, show admin panel
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    showAdminPanel();
    exit;
}

// Otherwise show login form
showLoginForm();

// ===================== FUNCTIONS =====================

function showLoginForm() {
    global $error;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Anime Site - Admin Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.2);
                width: 100%;
                max-width: 450px;
            }
            .logo {
                text-align: center;
                margin-bottom: 30px;
            }
            .logo i {
                font-size: 50px;
                color: #667eea;
                margin-bottom: 15px;
            }
            .error-alert {
                animation: shake 0.5s;
            }
            @keyframes shake {
                0%, 100% {transform: translateX(0);}
                25% {transform: translateX(-10px);}
                75% {transform: translateX(10px);}
            }

            .social-login-section {
                margin-top: 18px;
                text-align: center;
            }
            .social-login-buttons {
                display: flex;
                justify-content: center;
                gap: 10px;
                flex-wrap: wrap;
            }
            .social-btn {
                border: 1px solid #e5e7eb;
                background: #f8fafc;
                color: #334155;
                border-radius: 12px;
                min-width: 105px;
                padding: 9px 12px;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
                font-size: 13px;
                font-weight: 600;
                display: inline-flex;
            text-decoration: none;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }
            .social-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
            }
            .social-note {
                margin-top: 12px;
                color: #64748b;
                font-size: 13px;
            }
            .social-note a {
                color: #667eea;
                text-decoration: none;
                font-weight: 700;
            }
            .social-note a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <h3>Admin Access Required</h3>
                <p class="text-muted">Enter credentials to continue</p>
            </div>
            
            <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger error-alert">
                <i class="fas fa-exclamation-triangle"></i> 
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
            <div class="alert alert-danger error-alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                
                <input type="hidden" name="login" value="1">
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
                </button>
            </form>
            


            <div class="social-login-section" aria-label="Social authentication options">
                <div class="social-login-buttons">
                    <a href="oauth_start.php?provider=google" class="social-btn"><i class="fab fa-google"></i> Google</a>
                    <a href="oauth_start.php?provider=facebook" class="social-btn"><i class="fab fa-facebook-f"></i> Facebook</a>
                    <a href="oauth_start.php?provider=apple" class="social-btn"><i class="fab fa-apple"></i> Apple</a>
                </div>
                <div class="social-note">Don't have an account? <a href="signup.php">Register Now</a></div>
            </div>
            <div class="mt-4 text-center text-muted small">
                <i class="fas fa-info-circle"></i> Set ADMIN_USERNAME and ADMIN_PASSWORD/ADMIN_PASSWORD_HASH for production
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showAdminPanel() {
    $pendingUsers = [];
    $userStats = ['total' => 0, 'approved' => 0, 'pending' => 0];
    $animeCount = 0;
    $flashMessage = $_SESSION['admin_flash'] ?? null;
    unset($_SESSION['admin_flash']);

    try {
        $db = new DatabaseHelper();
        $pendingUsers = $db->getPendingUsers();
        $userStats = $db->getUserStats();
        $animeCount = $db->getAnimeCount();
        $db->close();
    } catch (Exception $e) {
        $flashMessage = 'Database warning: ' . $e->getMessage();
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel - Anime Site</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            body {
                background: #f8f9fa;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            .navbar-custom {
                background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            }
            .card {
                border: none;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.08);
                transition: transform 0.3s;
                margin-bottom: 20px;
            }
            .card:hover {
                transform: translateY(-5px);
            }
            .stat-box {
                text-align: center;
                padding: 25px 15px;
            }
            .stat-icon {
                font-size: 40px;
                color: #3498db;
                margin-bottom: 15px;
            }
            .main-site-btn {
                background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
                border: none;
                padding: 12px 30px;
                font-size: 18px;
                border-radius: 10px;
                color: white;
                transition: all 0.3s;
            }
            .main-site-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            .session-info {
                background: #e3f2fd;
                border-left: 5px solid #2196f3;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 25px;
            }
            .pending-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }
            @media (max-width: 576px) {
                .pending-actions {
                    flex-direction: column;
                    gap: 6px;
                }
                .pending-actions form {
                    width: 100%;
                }
                .pending-actions .btn {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-shield-alt"></i> Anime Dashboard
                </a>
                <div class="navbar-nav ms-auto">
                    <span class="nav-link">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                    <a class="nav-link" href="?logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </nav>

        <div class="container mt-4">
            <!-- Welcome Message -->
            <div class="session-info">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h5>
                        <p class="mb-0">You have full access to manage the anime site.</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-0">
                            <i class="fas fa-clock"></i> 
                            Logged in at: <?php echo date('H:i:s', $_SESSION['login_time']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-film"></i>
                        </div>
                        <h3><?php echo intval($animeCount); ?></h3>
                        <p class="text-muted">Anime Titles</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3><?php echo intval($userStats['total']); ?></h3>
                        <p class="text-muted">Registered Users</p>

                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3><?php echo intval($userStats['approved']); ?></h3>
                        <p class="text-muted">Approved Users</p>

                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-box">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3><?php echo round((time() - $_SESSION['login_time']) / 60); ?>m</h3>
                        <p class="text-muted">Session Time</p>
                    </div>
                </div>
            </div>

            <!-- Main Access Button -->
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <div class="card">
                        <div class="card-body">
                            <h3><i class="fas fa-tv"></i> Access Main Anime Site</h3>
                            <p class="text-muted">Click below to enter the main anime streaming site</p>
                            
                            <a href="?access=main" class="btn main-site-btn">
                                <i class="fas fa-external-link-alt"></i> ENTER MAIN SITE
                            </a>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> You will be redirected to the main anime streaming page
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if($flashMessage): ?>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($flashMessage); ?>
            </div>
            <?php endif; ?>

            <div class="card mt-3 mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-user-check"></i> Pending User Approvals</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($pendingUsers)): ?>
                        <p class="mb-0 text-muted">No pending users. All good ✅</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($pendingUsers as $pendingUser): ?>
                                    <tr>
                                        <td><?php echo intval($pendingUser['id']); ?></td>
                                        <td><?php echo htmlspecialchars($pendingUser['username']); ?></td>
                                        <td><?php echo htmlspecialchars($pendingUser['email']); ?></td>
                                        <td><?php echo htmlspecialchars($pendingUser['created_at']); ?></td>
                                        <td>
                                            <div class="pending-actions">
                                                <form method="POST" action="index.php" class="mb-0">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="approve_user" value="<?php echo intval($pendingUser['id']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="index.php" class="mb-0" onsubmit="return confirm('Reject this user?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="reject_user" value="<?php echo intval($pendingUser['id']); ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Admin Controls -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cog"></i> Site Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-film me-2"></i> Manage Anime Content
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-sliders-h me-2"></i> Edit Carousel Slides
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-users me-2"></i> User Management
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <i class="fas fa-chart-line me-2"></i> View Analytics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-tools"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-outline-primary w-100">
                                        <i class="fas fa-download"></i> Backup
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-success w-100">
                                        <i class="fas fa-sync"></i> Update
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-info w-100">
                                        <i class="fas fa-history"></i> Logs
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-warning w-100">
                                        <i class="fas fa-cogs"></i> Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-5 text-muted">
                <small>
                    <i class="fas fa-lock"></i> Secure Admin Panel • 
                    Session ID: <?php echo substr(session_id(), 0, 10); ?>... •
                    <?php echo date('Y-m-d H:i:s'); ?>
                </small>
            </div>
        </div>

        <script>
            // Auto logout after 30 minutes of inactivity
            let timeout;
            function resetTimer() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    if(confirm('Session will expire due to inactivity. Continue?')) {
                        resetTimer();
                    } else {
                        window.location.href = '?logout';
                    }
                }, 30 * 60 * 1000); // 30 minutes
            }
            
            // Reset timer on user activity
            document.addEventListener('mousemove', resetTimer);
            document.addEventListener('keypress', resetTimer);
            
            resetTimer(); // Start timer
        </script>
    </body>
    </html>
    <?php
}
?>
