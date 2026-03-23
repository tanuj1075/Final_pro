<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AckerStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ff4b2b;
            --secondary: #ff416c;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text);
        }
        .login-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .header { text-align: center; margin-bottom: 32px; }
        .header i { font-size: 48px; color: var(--primary); margin-bottom: 16px; }
        .header h1 { font-size: 24px; font-weight: 700; letter-spacing: -0.5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 14px; color: #94a3b8; }
        .input-group { position: relative; }
        .input-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .input-group input {
            width: 100%;
            padding: 12px 16px 12px 48px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 12px;
            color: white;
            transition: all 0.2s;
        }
        .input-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 75, 43, 0.2); }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 8px;
        }
        .btn-submit:hover { opacity: 0.9; }
        .alert {
            padding: 12px;
            border-radius: 12px;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #fcd34d;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="header">
            <i class="fas fa-shield-alt"></i>
            <h1>Admin Access</h1>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="?action=login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <div class="form-group">
                <label>Username</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">Sign In to Dashboard</button>
        </form>

        <?php if (isset($isUsingDefaultAdminCredentials) && $isUsingDefaultAdminCredentials): ?>
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                Warning: Using default admin credentials. Please update your environment variables.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
