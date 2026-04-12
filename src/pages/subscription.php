<?php
require_once __DIR__ . '/../utils/security.php';
require_once __DIR__ . '/../utils/bootstrap.php';

secure_session_start();
check_user_active();

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php?error=Please login to view plans');
    exit;
}

$userRepo = new \App\Repositories\UserRepository(\App\Database\Connection::getInstance());
$user = $userRepo->getUserById((int)$_SESSION['user_id']);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan'] ?? '';
    $billing = $_POST['billing'] ?? 'monthly';

    $validPlans = ['Fan', 'Mega Fan', 'Ultimate Fan'];
    if (in_array($plan, $validPlans)) {
        $expiresAt = ($billing === 'yearly') ? date('Y-m-d H:i:s', strtotime('+1 year')) : date('Y-m-d H:i:s', strtotime('+1 month'));

        if ($userRepo->updateSubscription((int)$user['id'], $plan, $expiresAt)) {
            $message = "Successfully upgraded to $plan!";
            $user = $userRepo->getUserById((int)$user['id']); // Refresh user data
        } else {
            $error = "Failed to update subscription. Please try again.";
        }
    }
}

$currentTier = $user['subscription_tier'] ?? 'Free';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AckerStream Plans · Subscription</title>
    <!-- Font Awesome 6 (free) for check icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: #0a0e1a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }

        .plans-container {
            max-width: 1280px;
            width: 100%;
            background: #111827;
            border-radius: 2.5rem;
            box-shadow: 0 20px 35px -8px rgba(0,0,0,0.5);
            padding: 2.5rem 2rem;
            border: 1px solid rgba(255,255,255,0.08);
        }

        /* header */
        .brand-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .crunchyroll-logo {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            border-right: 2px solid rgba(255,255,255,0.1);
            padding-right: 1.8rem;
        }

        .available-plans {
            font-size: 1.5rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-right: auto;
        }

        /* toggle switch */
        .billing-toggle {
            display: flex;
            gap: 0.25rem;
            background: #1f2937;
            padding: 0.25rem;
            border-radius: 100px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .toggle-btn {
            border: none;
            background: transparent;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.6rem 2rem;
            border-radius: 100px;
            cursor: pointer;
            color: #94a3b8;
            transition: all 0.15s ease;
            letter-spacing: 0.01em;
        }

        .toggle-btn.active {
            background: #374151;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            font-weight: 600;
        }

        /* card grid */
        .pricing-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1.8rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .pricing-card {
            flex: 1 1 280px;
            max-width: 360px;
            background: #1f2937;
            border-radius: 2rem;
            padding: 1.8rem 1.8rem 2rem;
            box-shadow: 0 10px 25px -10px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 35px -12px rgba(99,102,241,0.2);
            border-color: rgba(99,102,241,0.3);
        }

        /* most popular chip */
        .popular-chip {
            background: #6366f1;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 1rem;
            align-self: flex-start;
            text-transform: uppercase;
        }

        .plan-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 0.75rem;
        }

        .price-block {
            margin: 1rem 0 0.5rem;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
            gap: 0.2rem;
        }

        .price-currency {
            font-size: 1.5rem;
            font-weight: 600;
            color: #f1f5f9;
        }

        .price-amount {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1;
            color: #ffffff;
        }

        .price-period {
            font-size: 1.1rem;
            font-weight: 500;
            color: #94a3b8;
            margin-left: 0.2rem;
        }

        .taxes {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 1.5rem;
            letter-spacing: 0.02em;
        }

        .plan-button {
            width: 100%;
            padding: 0.9rem 0;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            background: #6366f1;
            color: white;
            margin-bottom: 1.8rem;
            transition: background 0.15s, box-shadow 0.15s;
            box-shadow: 0 6px 14px rgba(99,102,241,0.25);
        }

        .plan-button:hover {
            background: #4f46e5;
            box-shadow: 0 10px 18px rgba(99,102,241,0.35);
        }

        .plan-button.current-plan {
            background: #374151;
            color: #94a3b8;
            box-shadow: none;
            pointer-events: none;
            border: 1px solid rgba(255,255,255,0.1);
            font-weight: 600;
        }

        .features-list {
            list-style: none;
            margin-top: 0.5rem;
            flex-grow: 1;
        }

        .features-list li {
            display: flex;
            gap: 0.7rem;
            align-items: flex-start;
            font-size: 0.95rem;
            color: #d1d5db;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .features-list li i {
            color: #6366f1;
            font-size: 1rem;
            margin-top: 0.2rem;
            min-width: 1.2rem;
        }

        .feature-text {
            flex: 1;
        }

        .alert {
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
        }
        .alert-success { background: rgba(16,185,129,0.2); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
        .alert-error { background: rgba(239,68,68,0.2); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .back-link:hover { color: #f1f5f9; }

        /* responsive */
        @media (max-width: 900px) {
            .brand-header { flex-direction: column; align-items: flex-start; }
            .crunchyroll-logo { border-right: none; padding-right: 0; }
            .billing-toggle { width: 100%; justify-content: stretch; }
            .toggle-btn { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="plans-container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="brand-header">
            <div class="crunchyroll-logo">AckerStream</div>
            <div class="available-plans">Upgrade Your Experience</div>
            <div class="billing-toggle" id="billingToggle">
                <button class="toggle-btn active" data-billing="monthly">Monthly</button>
                <button class="toggle-btn" data-billing="yearly">Yearly</button>
            </div>
        </div>

        <div class="pricing-grid">
            <!-- Fan card -->
            <div class="pricing-card">
                <div class="plan-name">Fan</div>
                <div class="price-block">
                    <span class="price-currency">$</span>
                    <span class="price-amount" id="fan-amount">7.99</span>
                    <span class="price-period" id="fan-period">/mo</span>
                </div>
                <div class="taxes">+ APPLICABLE TAXES</div>
                <form method="POST">
                    <input type="hidden" name="plan" value="Fan">
                    <input type="hidden" name="billing" class="billing-input" value="monthly">
                    <button type="submit" class="plan-button <?= $currentTier === 'Fan' ? 'current-plan' : '' ?>">
                        <?= $currentTier === 'Fan' ? 'CURRENT PLAN' : 'CHOOSE PLAN' ?>
                    </button>
                </form>
                <ul class="features-list">
                    <li><i class="fas fa-check"></i><span class="feature-text">Stream on 1 device at a time</span></li>
                    <li><i class="fas fa-check"></i><span class="feature-text">No Ads on all content</span></li>
                </ul>
            </div>

            <!-- Mega Fan -->
            <div class="pricing-card">
                <div class="popular-chip">MOST POPULAR</div>
                <div class="plan-name">Mega Fan</div>
                <div class="price-block">
                    <span class="price-currency">$</span>
                    <span class="price-amount" id="mega-amount">11.99</span>
                    <span class="price-period" id="mega-period">/mo</span>
                </div>
                <div class="taxes">+ APPLICABLE TAXES</div>
                <form method="POST">
                    <input type="hidden" name="plan" value="Mega Fan">
                    <input type="hidden" name="billing" class="billing-input" value="monthly">
                    <button type="submit" class="plan-button <?= $currentTier === 'Mega Fan' ? 'current-plan' : '' ?>">
                        <?= $currentTier === 'Mega Fan' ? 'CURRENT PLAN' : 'CHOOSE PLAN' ?>
                    </button>
                </form>
                <ul class="features-list">
                    <li><i class="fas fa-check"></i><span class="feature-text">Stream on up to 4 devices at a time</span></li>
                    <li><i class="fas fa-check"></i><span class="feature-text">Offline Viewing</span></li>
                    <li><i class="fas fa-check"></i><span class="feature-text">Priority access to new episodes</span></li>
                </ul>
            </div>

            <!-- Ultimate Fan -->
            <div class="pricing-card">
                <div class="plan-name">Ultimate Fan</div>
                <div class="price-block">
                    <span class="price-currency">$</span>
                    <span class="price-amount" id="ultimate-amount">15.99</span>
                    <span class="price-period" id="ultimate-period">/mo</span>
                </div>
                <div class="taxes">+ APPLICABLE TAXES</div>
                <form method="POST">
                    <input type="hidden" name="plan" value="Ultimate Fan">
                    <input type="hidden" name="billing" class="billing-input" value="monthly">
                    <button type="submit" class="plan-button <?= $currentTier === 'Ultimate Fan' ? 'current-plan' : '' ?>">
                        <?= $currentTier === 'Ultimate Fan' ? 'CURRENT PLAN' : 'CHOOSE PLAN' ?>
                    </button>
                </form>
                <ul class="features-list">
                    <li><i class="fas fa-check"></i><span class="feature-text">Stream on up to 6 devices at a time</span></li>
                    <li><i class="fas fa-check"></i><span class="feature-text">Offline Viewing</span></li>
                    <li><i class="fas fa-check"></i><span class="feature-text">Exclusive early access to movies</span></li>
                    <li><i class="fas fa-check"></i><span class="feature-text">Monthly digital swag bag</span></li>
                </ul>
            </div>
        </div>

        <a href="user_panel.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Profile</a>
    </div>

    <script>
        (function() {
            const toggleBtns = document.querySelectorAll('.toggle-btn');
            const billingInputs = document.querySelectorAll('.billing-input');
            const amounts = {
                fan: document.getElementById('fan-amount'),
                mega: document.getElementById('mega-amount'),
                ultimate: document.getElementById('ultimate-amount')
            };
            const periods = {
                fan: document.getElementById('fan-period'),
                mega: document.getElementById('mega-period'),
                ultimate: document.getElementById('ultimate-period')
            };

            const prices = {
                monthly: { fan: 7.99, mega: 11.99, ultimate: 15.99, period: '/mo' },
                yearly: { fan: 79.99, mega: 119.99, ultimate: 159.99, period: '/yr' }
            };

            function setBilling(plan) {
                toggleBtns.forEach(btn => btn.classList.toggle('active', btn.dataset.billing === plan));
                billingInputs.forEach(input => input.value = plan);

                const data = prices[plan];
                amounts.fan.textContent = data.fan.toFixed(2);
                amounts.mega.textContent = data.mega.toFixed(2);
                amounts.ultimate.textContent = data.ultimate.toFixed(2);

                Object.values(periods).forEach(p => p.textContent = data.period);
            }

            toggleBtns.forEach(btn => {
                btn.addEventListener('click', (e) => setBilling(e.currentTarget.dataset.billing));
            });
        })();
    </script>
</body>
</html>
