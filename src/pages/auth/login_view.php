
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ackerstream - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ========== RESET & GLOBAL ========== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url("mainlogo.png") no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        /* Softer overlay for better text contrast */
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }

        /* ========== LOGIN CARD ========== */
        .login-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 38;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
            padding: 2rem 1.8rem;
            width: 90%;
            max-width: 440px;
            transition: all 0.3s ease;
        }

        /* Logo & header */
        .logo {
            text-align: center;
            margin-bottom: 1.8rem;
        }

        .logo h1 {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.9rem;
            letter-spacing: 0.08em;
            /* Fallback gradient if variables are missing */
            background: linear-gradient(100deg, #f0b90b 0%, #e6a017 45%, #fff0c0 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            white-space: nowrap;
        }

        /* Optional – define custom properties for gradient */
        :root {
            --amber-bright: #f0b90b;
            --amber: #e6a017;
        }

        .logo-img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .logo p {
            color: #f0f0f0;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }

        /* Form groups */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #c0c0ff;
            font-size: 1rem;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 42px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            backdrop-filter: blur(2px);
        }

        .form-group input:focus {
            outline: none;
            border-color: #8a6eff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.25);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.65);
            font-weight: 400;
        }

        /* Button */
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(1px);
        }

        /* Alert messages */
        .alert {
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 1.2rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.2);
            backdrop-filter: blur(4px);
            border-left: 3px solid #2ecc71;
            color: #d0ffd0;
        }

        .alert-error {
            background: rgba(231, 76, 60, 0.2);
            backdrop-filter: blur(4px);
            border-left: 3px solid #e74c3c;
            color: #ffc9c9;
        }

        .alert-info {
            background: rgba(52, 152, 219, 0.2);
            backdrop-filter: blur(4px);
            border-left: 3px solid #3498db;
            color: #c7f0ff;
        }

        /* Social login */
        .social-login-section {
            margin-top: 1.5rem;
            text-align: center;
        }

        .social-login-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .social-btn {
            border: 1px solid rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            border-radius: 40px;
            padding: 8px 16px;
            font-size: 0.85rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
            backdrop-filter: blur(4px);
        }

        .social-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
            border-color: rgba(255, 255, 255, 0.7);
        }

        .social-note {
            margin-top: 1rem;
            color: #ddd;
            font-size: 0.85rem;
        }

        .social-note a {
            color: #cbb3ff;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px dotted rgba(203, 179, 255, 0.6);
        }

        .social-note a:hover {
            color: white;
            border-bottom-color: white;
        }

        .error-message {
            background: rgba(231, 76, 60, 0.2);
            backdrop-filter: blur(4px);
            border-radius: 12px;
            padding: 10px;
            margin-top: 12px;
            font-size: 0.8rem;
            text-align: center;
            color: #ffaeae;
        }

        .hidden {
            display: none;
        }

        /* Responsive adjustments */
        @media (max-width: 520px) {
            .login-container {
                padding: 1.5rem;
                width: 95%;
            }

            .logo h1 {
                font-size: 1.6rem;
                white-space: normal;
                flex-wrap: wrap;
            }

            .logo-img {
                width: 40px;
                height: 40px;
            }

            .social-login-buttons {
                gap: 8px;
            }

            .social-btn {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logo">
        <h1>
            <img src="/src/assets/images/bird.svg" alt="AckerStream Logo" class="logo-img">
            AckerStream
        </h1>
        <p>Sign in to continue watching</p>
    </div>

    <?php if (!empty($message) && in_array($messageType, $allowedTypes)): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'info' ? 'info-circle' : 'exclamation-circle') ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="form-group">
            <label for="username">Email or Username</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="text" id="username" name="username" placeholder="Enter your email or username" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
        <div id="firebaseError" class="error-message hidden"></div>
    </form>
    <div class="social-login-section" aria-label="Social authentication options">
        
        <div class="social-note">Don't have an account? <a href="signup.php">Register Now</a></div>
    </div>
</div>

<script>
    // Trim username field before submission
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');

    if (loginForm && usernameInput) {
        loginForm.addEventListener('submit', () => {
            usernameInput.value = usernameInput.value.trim();
        });
    }
</script>
</body>
</html>
