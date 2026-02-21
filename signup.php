<?php
// Start session at the very beginning before any output
session_start();
require_once 'db_helper.php';

$message = '';
$message_type = '';
$form_values = ['username' => '', 'email' => ''];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Store form values for repopulation (except passwords)
    $form_values['username'] = $username;
    $form_values['email'] = $email;

    $csrf = $_POST['csrf_token'] ?? '';

    // Validation
    if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $message = 'Invalid request token. Please refresh and try again.';
        $message_type = 'error';
    } elseif (empty($username) || empty($email) || empty($password)) {
        $message = 'All fields are required!';
        $message_type = 'error';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $message = 'Username must be 3-30 chars and contain only letters, numbers, and underscores.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format!';
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters!';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match!';
        $message_type = 'error';
    } else {
        // Register user
        try {
            $db = new DatabaseHelper();
            $result = $db->registerUser($username, $email, $password);
            
            if ($result === true) {
                $message = 'Account created successfully! Please wait for admin approval before logging in.';
                $message_type = 'success';
                // Clear form on success
                $form_values = ['username' => '', 'email' => ''];
            } else {
                $message = $result;
                $message_type = 'error';
            }
            $db->close();
        } catch (Exception $e) {
            $message = 'Registration failed: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Crunchrolly</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }

        .logo p {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-signup {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-signup:active {
            transform: translateY(0);
        }

        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .footer-text a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }

        .strength-weak { color: #f44336; }
        .strength-medium { color: #ff9800; }
        .strength-strong { color: #4caf50; }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="logo">
            <h1>🎬 Crunchrolly</h1>
            <p>Create your account to start watching</p>
        </div>



        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <i class="fas fa-<?= $message_type == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($form_values['username']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($form_values['email']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                </div>
                <div class="password-strength" id="strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                </div>
            </div>

            <button type="submit" class="btn-signup">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

        <div class="footer-text">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthDiv.textContent = '⚠️ Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                strengthDiv.textContent = '⚡ Medium password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = '✅ Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });
    </script>
</body>
</html>
