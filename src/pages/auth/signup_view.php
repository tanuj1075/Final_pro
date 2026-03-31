<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - AckerStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        /* ========== SIGNUP CARD ========== */
        .signup-container {
            position: relative;
            z-index: 2;
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
            font-family: 'Bebas Neue', 'Segoe UI', sans-serif;
            font-size: 1.9rem;
            letter-spacing: 0.08em;
            background: linear-gradient(100deg, #f0b90b 0%, #e6a017 45%, #fff0c0 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            white-space: nowrap;
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

        /* Password strength */
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        .strength-weak { color: #ffaa88; }
        .strength-medium { color: #ffd966; }
        .strength-strong { color: #b0ffb0; }

        /* Button */
        .btn-signup {
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

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-signup:active {
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

        /* Footer link */
        .footer-text {
            text-align: center;
            margin-top: 20px;
            color: #ddd;
            font-size: 14px;
        }
        .footer-text a {
            color: #cbb3ff;
            text-decoration: none;
            font-weight: 600;
            border-bottom: 1px dotted rgba(203, 179, 255, 0.6);
        }
        .footer-text a:hover {
            color: white;
            border-bottom-color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 520px) {
            .signup-container {
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
        }
    </style>
</head>
<body>
<div class="signup-container">
    <div class="logo">
        <h1>
            <img src="/src/assets/images/bird.svg" alt="AckerStream Logo" class="logo-img">
            AckerStream
        </h1>
        <p>Create your account to start watching</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fas fa-<?= $messageType == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form id="signupForm" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
            <input 
                type="text" 
                id="username" 
                name="username" 
                placeholder="Choose a username" 
                required
            >            
        </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="your@email.com" 
                    required
                >          
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Create a strong password" minlength="8" required>
            </div>
            <div class="password-strength" id="strength"></div>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" minlength="8" required>
            </div>
        </div>

        <button type="submit" class="btn-signup">
            <i class="fas fa-user-plus"></i> Create Account
        </button>
        <div id="firebaseError" class="error-message hidden"></div>
    </form>

    <div class="footer-text">
        Already have an account? <a href="login.php">Sign In</a>
    </div>
</div>

<script>
    // Keep native form submission so PHP signup logic executes server-side.
    const form = document.getElementById('signupForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const usernameInput = document.getElementById('username');
    const errorDiv = document.getElementById('firebaseError');

    form.addEventListener('submit', function (e) {
        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        const username = usernameInput.value.trim();

        usernameInput.value = username;
        emailInput.value = email;

        errorDiv.classList.add('hidden');
        errorDiv.innerText = '';

        if (!username || !email || !password || !confirm) {
            e.preventDefault();
            errorDiv.innerText = 'All fields are required!';
            errorDiv.classList.remove('hidden');
            return;
        }

        if (!email.includes('@')) {
            e.preventDefault();
            errorDiv.innerText = 'Please enter a valid email address.';
            errorDiv.classList.remove('hidden');
            return;
        }

        if (password.length < 8) {
            e.preventDefault();
            errorDiv.innerText = 'Password must be at least 8 characters.';
            errorDiv.classList.remove('hidden');
            return;
        }

        if (password !== confirm) {
            e.preventDefault();
            errorDiv.innerText = 'Passwords do not match.';
            errorDiv.classList.remove('hidden');
        }
    });

    // Password strength checker
    const strengthDiv = document.getElementById('strength');
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        if (password.length === 0) {
            strengthDiv.textContent = '';
            return;
        }

        let strength = 0;
        if (password.length >= 8) strength++;
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
