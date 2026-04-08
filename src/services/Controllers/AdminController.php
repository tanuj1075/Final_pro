<?php

namespace App\Controllers;

use App\Repositories\AnimeRepository;
use App\Repositories\UserRepository;
use Exception;

class AdminController
{
    private AnimeRepository $animeRepo;
    private UserRepository $userRepo;

    public function __construct(AnimeRepository $animeRepo, UserRepository $userRepo)
    {
        $this->animeRepo = $animeRepo;
        $this->userRepo = $userRepo;
    }

    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? '';

        if ($action === 'logout') {
            $this->logout();
            return;
        }
        if ($action === 'main_site') {
            $this->handleMainAccess();
            return;
        }


        if (isset($_GET['access']) && $_GET['access'] === 'main') {
            $this->handleMainAccess();
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'login') {
                $this->login();
                return;
            }

            if ($this->isAdminLoggedIn()) {
                if ($action === 'approve' && isset($_POST['approve_user'])) {
                    $this->approveUser((int)$_POST['approve_user']);
                    return;
                }
                if ($action === 'reject' && isset($_POST['reject_user'])) {
                    $this->rejectUser((int)$_POST['reject_user']);
                    return;
                }
                if ($action === 'block' && isset($_POST['block_user'])) {
                    $this->blockUser((int)$_POST['block_user']);
                    return;
                }
                if ($action === 'unblock' && isset($_POST['unblock_user'])) {
                    $this->unblockUser((int)$_POST['unblock_user']);
                    return;
                }
                if ($action === 'delete' && isset($_POST['delete_user'])) {
                    $this->handleDeleteUser((int)$_POST['delete_user']);
                    return;
                }
                if ($action === 'delete_anime' && isset($_POST['anime_id'])) {
                    $this->deleteAnime((int)$_POST['anime_id']);
                    return;
                }

            }
        }

        if ($this->isAdminLoggedIn()) {
            if ($action === 'user_detail' || (isset($_GET['page']) && $_GET['page'] === 'user_detail')) {
                require __DIR__ . '/../../pages/admin/user_detail.php';
                return;
            }
            if ($action === 'admin_profile' || (isset($_GET['page']) && $_GET['page'] === 'admin_profile')) {
                require __DIR__ . '/../../pages/admin/admin_profile.php';
                return;
            }
            if ($action === 'edit_anime' || (isset($_GET['page']) && $_GET['page'] === 'edit_anime')) {
                require __DIR__ . '/../../pages/admin/edit_anime.php';
                return;
            }

            $this->showDashboard();
        } else {
            $this->showLogin();
        }
    }

    private function isAdminLoggedIn(): bool
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    private function handleMainAccess(): void
    {
        if (!$this->isAdminLoggedIn()) {
            header('Location: /admin?error=Please login first to access the main site');
            exit;
        }
        // Redirect to main site
        header('Location: /ash.php');
        exit;
    }

    private function logout(): void
    {
        destroy_session_and_cookie();
        header('Location: /admin');
        exit;
    }

    private function login(): void
    {
        global $ADMIN_USERNAME, $ADMIN_PASSWORD, $PASSWORD_HASH, $adminCredentialsConfigured;

        if (!$adminCredentialsConfigured) {
            $this->showLogin('Admin login is disabled in this environment.');
            return;
        }

        if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
            $this->showLogin('Invalid request token. Please refresh and try again.');
            return;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $passwordMatch = $password === $ADMIN_PASSWORD;
        if (!$passwordMatch && $PASSWORD_HASH !== '$2y$10$YourHashHere') {
            $passwordMatch = password_verify($password, $PASSWORD_HASH);
        }

        if ($username === $ADMIN_USERNAME && $passwordMatch) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            $_SESSION['login_time'] = time();

            header('Location: /admin');
            exit;
        }

        $this->showLogin("Invalid username or password!");
    }

    private function approveUser(int $userId): void
    {
        if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['admin_flash'] = 'Invalid request token. Please try again.';
            header('Location: /admin');
            exit;
        }

        try {
            $approved = $this->userRepo->approveUser($userId);
            $_SESSION['admin_flash'] = $approved
                ? 'User approved successfully.'
                : 'Unable to approve user (user may already be approved).';
        } catch (Exception $e) {
            $_SESSION['admin_flash'] = 'Action failed: ' . $e->getMessage();
        }

        header('Location: /admin');
        exit;
    }

    private function rejectUser(int $userId): void
    {
        if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['admin_flash'] = 'Invalid request token. Please try again.';
            header('Location: /admin');
            exit;
        }

        try {
            $rejected = $this->userRepo->rejectUser($userId);
            $_SESSION['admin_flash'] = $rejected
                ? 'User application rejected.'
                : 'Unable to reject user.';
        } catch (Exception $e) {
            $_SESSION['admin_flash'] = 'Action failed: ' . $e->getMessage();
        }

        header('Location: /admin');
        exit;
    }

    private function blockUser(int $userId): void
    {
        if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['admin_flash'] = 'Invalid request token. Please try again.';
            header('Location: /admin');
            exit;
        }

        try {
            $blocked = $this->userRepo->blockUser($userId);
            $_SESSION['admin_flash'] = $blocked
                ? 'User blocked successfully.'
                : 'Unable to block user.';
        } catch (Exception $e) {
            $_SESSION['admin_flash'] = 'Action failed: ' . $e->getMessage();
        }

        header('Location: /admin');
        exit;
    }

    private function unblockUser(int $userId): void
    {
        if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['admin_flash'] = 'Invalid request token. Please try again.';
            header('Location: /admin');
            exit;
        }

        try {
            $unblocked = $this->userRepo->unblockUser($userId);
            $_SESSION['admin_flash'] = $unblocked
                ? 'User unblocked successfully.'
                : 'Unable to unblock user.';
        } catch (Exception $e) {
            $_SESSION['admin_flash'] = 'Action failed: ' . $e->getMessage();
        }

        header('Location: /admin');
        exit;
    }

    private function handleDeleteUser(int $userId): void
    {
        if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['admin_flash'] = 'Invalid request token. Please try again.';
            header('Location: /admin');
            exit;
        }

        try {
            $deleted = $this->userRepo->deleteUser($userId);
            $_SESSION['admin_flash'] = $deleted
                ? 'User deleted permanently.'
                : 'Unable to delete user.';
        } catch (Exception $e) {
            $_SESSION['admin_flash'] = 'Action failed: ' . $e->getMessage();
        }

        header('Location: /admin');
        exit;
    }

    private function deleteAnime(int $animeId): void
    {
        if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
            $_SESSION['admin_flash'] = 'Invalid request token. Please try again.';
            header('Location: /admin');
            exit;
        }

        try {
            // Use AnimeRepository if a deleteAnime method exists, otherwise use direct DB
            $db = \App\Database\Connection::getInstance();
            $stmt = $db->prepare("DELETE FROM admin_panel_anime WHERE id = ?");
            $stmt->execute([$animeId]);
            $_SESSION['admin_flash'] = 'Anime deleted successfully.';
        } catch (\Exception $e) {
            $_SESSION['admin_flash'] = 'Delete failed: ' . $e->getMessage();
        }

        header('Location: /admin');
        exit;
    }


    private function showLogin(?string $error = null): void
    {
        global $adminCredentialsConfigured, $isUsingDefaultAdminCredentials;
        require __DIR__ . '/../../pages/admin/login.php';
    }

    private function showDashboard(): void
    {
        $flashMessage = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        try {
            $pendingUsers = $this->userRepo->getPendingUsers();
            $userStats = $this->userRepo->getUserStats();
            $animeCount = $this->animeRepo->getAnimeCount();
        } catch (Exception $e) {
            error_log('[AdminController] ' . $e->getMessage());
            $flashMessage = 'Database warning: unable to load admin metrics right now.';
            $pendingUsers = [];
            $userStats = ['total' => 0, 'approved' => 0, 'pending' => 0];
            $animeCount = 0;
        }

        require __DIR__ . '/../../pages/admin/dashboard.php';
    }
}
