<?php
require_once 'security.php';
require_once 'db_helper.php';

secure_session_start();

$message = '';
$message_type = '';
$form_values = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request token. Please refresh and try again.';
        $message_type = 'error';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $form_values['username'] = $username;
        $form_values['email'] = $email;

        if (empty($username) || empty($email) || empty($password)) {
            $message = 'All fields are required!';
            $message_type = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format!';
            $message_type = 'error';
        } elseif (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters!';
            $message_type = 'error';
        } elseif ($password !== $confirm_password) {
            $message = 'Passwords do not match!';
            $message_type = 'error';
        } else {
            try {
                $db = new DatabaseHelper();
                $result = $db->registerUser($username, $email, $password);

                if ($result === true) {
                    $message = 'Account created successfully! Please wait for admin approval before logging in.';
                    $message_type = 'success';
                    $form_values = ['username' => '', 'email' => ''];
                } else {
                    $message = $result;
                    $message_type = 'error';
                }
                $db->close();
            } catch (Exception $e) {
                error_log('Signup failure: ' . $e->getMessage());
                $message = 'Registration failed due to a server error. Please try again.';
                $message_type = 'error';
            }
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
        /* (CSS unchanged) */
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
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 {
            font-size: 36px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: bold;
        }
        .logo p { color: #666; margin-top: 5px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        .input-wrapper { position: relative; }
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
        .btn-signup:active { transform: translateY(0); }
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
        .footer-text a:hover { text-decoration: underline; }
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
        .error-message {
            background: #f8d7da;
            color: #721c24;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            font-size: 14px;
            text-align: center;
        }
        .hidden { display: none; }
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

    <form id="signupForm" method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
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

<!-- Firebase SDK and initialization -->
<script type="module">
    import { initializeApp } from "https://www.gstatic.com/firebasejs/12.11.0/firebase-app.js";
    import { getAuth, createUserWithEmailAndPassword, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/12.11.0/firebase-auth.js";
    import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.11.0/firebase-analytics.js";

    const firebaseConfig = {
        apiKey: "AIzaSyDqNVllEX66Tuma5E-Mom-nH-7muh3d59k",
        authDomain: "ackerstream-d52e9.firebaseapp.com",
        projectId: "ackerstream-d52e9",
        storageBucket: "ackerstream-d52e9.firebasestorage.app",
        messagingSenderId: "251934106668",
        appId: "1:251934106668:web:2b2365b78df3254ac19d89",
        measurementId: "G-JHK30XWT7R"
    };

    const app = initializeApp(firebaseConfig);
    const auth = getAuth(app);
    const analytics = getAnalytics(app);

    // Get form elements
    const form = document.getElementById('signupForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const usernameInput = document.getElementById('username');
    const errorDiv = document.getElementById('firebaseError');

    // Intercept form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); // Prevent PHP POST

        const email = emailInput.value.trim();
        const password = passwordInput.value;
        const confirm = confirmInput.value;
        const username = usernameInput.value.trim();

        // Reset error
        errorDiv.classList.add('hidden');
        errorDiv.innerText = '';

        // Basic client-side validation
        if (!username || !email || !password || !confirm) {
            errorDiv.innerText = 'All fields are required!';
            errorDiv.classList.remove('hidden');
            return;
        }

        if (!email.includes('@')) {
            errorDiv.innerText = 'Please enter a valid email address.';
            errorDiv.classList.remove('hidden');
            return;
        }

        if (password.length < 8) {
            errorDiv.innerText = 'Password must be at least 8 characters.';
            errorDiv.classList.remove('hidden');
            return;
        }

        if (password !== confirm) {
            errorDiv.innerText = 'Passwords do not match.';
            errorDiv.classList.remove('hidden');
            return;
        }

        // Attempt Firebase registration
        try {
            const userCredential = await createUserWithEmailAndPassword(auth, email, password);
            const user = userCredential.user;
            console.log("Firebase user created:", user.email);

            // Optional: store additional user data (like username) in Firebase Firestore or Realtime Database
            // For now, just show success and redirect
            alert("Account created successfully! You can now sign in.");
            window.location.href = "login.php"; // Redirect to login page
        } catch (error) {
            console.error("Firebase Signup Error:", error.code);
            let msg = "Registration failed. ";
            switch (error.code) {
                case 'auth/email-already-in-use':
                    msg = "This email is already registered. Please sign in instead.";
                    break;
                case 'auth/invalid-email':
                    msg = "Invalid email address.";
                    break;
                case 'auth/weak-password':
                    msg = "Password is too weak. Use at least 8 characters with a mix of letters, numbers, and symbols.";
                    break;
                default:
                    msg = "Unable to create account. Please try again later.";
            }
            errorDiv.innerText = msg;
            errorDiv.classList.remove('hidden');
        }
    });

    // Password strength checker (keep existing)
    const passwordField = document.getElementById('password');
    const strengthDiv = document.getElementById('strength');
    passwordField.addEventListener('input', function() {
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

    // Optional: Check if already signed in
    onAuthStateChanged(auth, (user) => {
        if (user) {
            console.log("User already signed in:", user.email);
            // Optionally redirect away if already logged in
            // window.location.href = "user_panel.php";
        }
    });
</script>
</body>
</html>
