<?php
namespace App\Controllers;

use App\Database\Connection;
use App\Repositories\UserRepository;

/**
 * Handles user authentication including login, registration, and logout.
 */
class AuthController
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository(Connection::getInstance());
    }

    /**
     * Display the login form and process login requests.
     *
     * @return void
     */
    public function login(): void
    {
        $message = '';
        $messageType = '';
        $allowedTypes = ['success', 'error', 'info'];

        // Handle logout
        if (isset($_GET['logout'])) {
            destroy_session_and_cookie();
            $message = 'You have been logged out successfully!';
            $messageType = 'success';
        }

        // Handle error from GET parameter
        if (isset($_GET['error']) && trim($_GET['error']) !== '') {
            $message = trim($_GET['error']);
            $messageType = 'error';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
                $message = 'Invalid request token. Please refresh and try again.';
                $messageType = 'error';
            } else {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($username) || empty($password)) {
                    $message = 'Please enter both username and password!';
                    $messageType = 'error';
                } elseif (strlen($username) > 50) {
                    $message = 'Username is too long.';
                    $messageType = 'error';
                } else {
                    try {
                        $result = $this->userRepo->loginUser($username, $password);

                        if ($result['success']) {
                            session_regenerate_id(true);
                            $_SESSION['user_logged_in'] = true;
                            $_SESSION['user_id'] = $result['user']['id'];
                            $_SESSION['username'] = $result['user']['username'];
                            $_SESSION['email'] = $result['user']['email'];

                            header('Location: user_panel.php');
                            exit;
                        } else {
                            $message = $result['message'];
                            $messageType = ($result['message'] === 'Account pending admin approval') ? 'info' : 'error';
                        }
                    } catch (\Exception $e) {
                        error_log('Login failure: ' . $e->getMessage());
                        $message = 'Unable to log in at this time. Please try again later.';
                        $messageType = 'error';
                    }
                }
            }
        }

        $csrf_token = csrf_token();
        require __DIR__ . '/../../pages/auth/login_view.php';
    }

    /**
     * Display the signup form and process registration requests.
     *
     * @return void
     */
    public function signup(): void
    {
        $message = '';
        $messageType = '';
        $formValues = ['username' => '', 'email' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
                $message = 'Invalid request token. Please refresh and try again.';
                $messageType = 'error';
            } else {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                $formValues['username'] = $username;
                $formValues['email'] = $email;

                if (empty($username) || empty($email) || empty($password)) {
                    $message = 'All fields are required!';
                    $messageType = 'error';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Invalid email format!';
                    $messageType = 'error';
                } elseif (strlen($password) < 8) {
                    $message = 'Password must be at least 8 characters!';
                    $messageType = 'error';
                } elseif ($password !== $confirmPassword) {
                    $message = 'Passwords do not match!';
                    $messageType = 'error';
                } else {
                    try {
                        $result = $this->userRepo->registerUser($username, $email, $password);

                        if ($result['success'] === true) {
                            $message = 'Account created successfully! Please wait for admin approval before logging in.';
                            $messageType = 'success';
                            $formValues = ['username' => '', 'email' => ''];
                        } else {
                            $message = $result['message'];
                            $messageType = 'error';
                        }
                    } catch (\Exception $e) {
                        error_log('Signup failure: ' . $e->getMessage());
                        $message = 'Registration failed due to a server error. Please try again.';
                        $messageType = 'error';
                    }
                }
            }
        }

        $csrf_token = csrf_token();
        require __DIR__ . '/../../pages/auth/signup_view.php';
    }
}
