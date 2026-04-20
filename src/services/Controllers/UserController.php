<?php
namespace App\Controllers;

use App\Database\Connection;
use App\Repositories\UserRepository;

class UserController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository(Connection::getInstance());
    }

    public function dashboard(): void
    {
        if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
            header('Location: login.php?error=Please login first');
            exit;
        }

        if (isset($_GET['logout'])) {
            if (isset($_SESSION['user_id'])) {
                $this->userRepo->touchLastLogout((int)$_SESSION['user_id']);
            }
            destroy_session_and_cookie();
            header('Location: login.php?logout=1');
            exit;
        }

        $user = null;
        $loginHistory = [];
        $dbError = null;
        try {
            $user = $this->userRepo->getUserById($_SESSION['user_id'] ?? 0);
            $loginHistory = $this->userRepo->getRecentLoginHistory((int)($_SESSION['user_id'] ?? 0), 8);
        } catch (\Exception $e) {
            error_log('User panel database warning: ' . $e->getMessage());
            $dbError = 'Unable to load live account details right now.';
        }

        if (!$user || (int)$user['is_active'] !== 1 || (int)$user['is_approved'] !== 1) {
            destroy_session_and_cookie();
            header('Location: login.php?error=Account state changed. Please login again.');
            exit;
        }

        require __DIR__ . '/../../pages/user/panel_view.php';
    }
}
