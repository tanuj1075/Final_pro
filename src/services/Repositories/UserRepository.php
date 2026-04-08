<?php

namespace App\Repositories;

use PDO;
use PDOException;

/**
 * Handles database operations related to users.
 */
class UserRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Register a new user.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @return array{success: bool, message: string}
     */
    public function registerUser(string $username, string $email, string $password): array
    {
        $normalizedUsername = $this->normalizeUsername($username);
        $normalizedEmail = $this->normalizeEmail($email);
        $clientMetadata = $this->getClientMetadata();

        if (!$this->isValidUsername($normalizedUsername)) {
            return ['success' => false, 'message' => 'Username must be 3-30 characters and use only letters, numbers, or underscores'];
        }

        if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
        }

        if ($this->usernameExists($normalizedUsername)) {
            return ['success' => false, 'message' => 'Username is already taken'];
        }

        if ($this->getUserByEmail($normalizedEmail)) {
            return ['success' => false, 'message' => 'Email address is already registered'];
        }

        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO admin_panel_siteuser (
                    username,
                    email,
                    password_hash,
                    is_approved,
                    is_active,
                    created_at,
                    status,
                    registration_ip,
                    registration_user_agent,
                    last_seen_ip,
                    last_seen_user_agent
                )
                 VALUES (
                    :username,
                    :email,
                    :password_hash,
                    0,
                    1,
                    datetime('now'),
                    'offline',
                    :registration_ip,
                    :registration_user_agent,
                    :last_seen_ip,
                    :last_seen_user_agent
                )"
            );

            $stmt->execute([
                'username' => $normalizedUsername,
                'email' => $normalizedEmail,
                'password_hash' => $passwordHash,
                'registration_ip' => $clientMetadata['ip'],
                'registration_user_agent' => $clientMetadata['user_agent'],
                'last_seen_ip' => $clientMetadata['ip'],
                'last_seen_user_agent' => $clientMetadata['user_agent'],
            ]);

            return ['success' => true, 'message' => 'Registration successful. Waiting for admin approval.'];
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log('User registration database error: ' . $errorMsg);
            if ($e->getCode() === '23000' || 
                strpos($errorMsg, 'UNIQUE constraint failed') !== false || 
                strpos($errorMsg, 'is not unique') !== false ||
                strpos($errorMsg, 'Integrity constraint violation') !== false) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            return ['success' => false, 'message' => 'Registration failed due to a database error.'];
        }
    }

    /**
     * Authenticate a user login.
     *
     * @param string $username
     * @param string $password
     * @return array{success: bool, message?: string, user?: array}
     */
    public function loginUser(string $username, string $password): array
    {
        $normalizedUsername = $this->normalizeUsername($username);

        $stmt = $this->db->prepare("SELECT * FROM admin_panel_siteuser WHERE username = :username");
        $stmt->execute(['username' => $normalizedUsername]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        if ((int)$user['is_active'] !== 1) {
            return ['success' => false, 'message' => 'Account disabled by admin'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid username or password'];
        }

        if (!(int)$user['is_approved']) {
            return ['success' => false, 'message' => 'Account pending admin approval'];
        }

        $this->touchLastLogin((int)$user['id']);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Retrieve users pending admin approval.
     *
     * @param int $limit
     * @return array
     */
    public function getPendingUsers(int $limit = 25): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, created_at
             FROM admin_panel_siteuser
             WHERE is_approved = 0 AND is_active = 1
             ORDER BY created_at ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function approveUser(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE admin_panel_siteuser
             SET is_approved = 1, approved_at = datetime('now')
             WHERE id = :id AND is_active = 1"
        );
        $stmt->execute(['id' => $userId]);
        
        return $stmt->rowCount() > 0;
    }

    public function blockUser(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE admin_panel_siteuser SET is_active = 0, status = 'offline' WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        
        return $stmt->rowCount() > 0;
    }

    public function unblockUser(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE admin_panel_siteuser SET is_active = 1 WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        
        return $stmt->rowCount() > 0;
    }
    public function rejectUser(int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE admin_panel_siteuser SET is_active = 0 WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        
        return $stmt->rowCount() > 0;
    }

    public function deleteUser(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM admin_panel_siteuser WHERE id = :id");
        $stmt->execute(['id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM admin_panel_siteuser WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    public function getUserById(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, is_approved, is_active, created_at, last_login, status, last_logout
             FROM admin_panel_siteuser
             WHERE id = :id"
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        
        return $user ?: null;
    }

    public function touchLastLogin(int $userId): void
    {
        $clientMetadata = $this->getClientMetadata();
        $stmt = $this->db->prepare(
            "UPDATE admin_panel_siteuser
             SET last_login = datetime('now'),
                 status = 'online',
                 last_seen_ip = :last_seen_ip,
                 last_seen_user_agent = :last_seen_user_agent
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $userId,
            'last_seen_ip' => $clientMetadata['ip'],
            'last_seen_user_agent' => $clientMetadata['user_agent'],
        ]);
    }

    public function touchLastLogout(int $userId): void
    {
        $clientMetadata = $this->getClientMetadata();
        $stmt = $this->db->prepare(
            "UPDATE admin_panel_siteuser
             SET last_logout = datetime('now'),
                 status = 'offline',
                 last_seen_ip = :last_seen_ip,
                 last_seen_user_agent = :last_seen_user_agent
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $userId,
            'last_seen_ip' => $clientMetadata['ip'],
            'last_seen_user_agent' => $clientMetadata['user_agent'],
        ]);
    }

    public function getUserStats(): array
    {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_siteuser")->fetchColumn();
        $approved = (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_siteuser WHERE is_approved = 1 AND is_active = 1")->fetchColumn();
        $pending = (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_siteuser WHERE is_approved = 0 AND is_active = 1")->fetchColumn();

        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
        ];
    }

    public function generateUniqueUsername(string $base): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($base));
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $index = 1;
        while ($this->usernameExists($candidate)) {
            $candidate = $base . '_' . $index;
            $index++;
        }
        
        return $candidate;
    }

    private function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin_panel_siteuser WHERE username = :username");
        $stmt->execute(['username' => $username]);
        
        return (int)$stmt->fetchColumn() > 0;
    }

    private function normalizeUsername(string $username): string
    {
        return trim($username);
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    private function isValidUsername(string $username): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_]{3,30}$/', $username);
    }

    /**
     * @return array{ip: ?string, user_agent: ?string}
     */
    private function getClientMetadata(): array
    {
        $forwardedFor = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        $candidateIp = $forwardedFor !== '' ? explode(',', $forwardedFor)[0] : ($_SERVER['REMOTE_ADDR'] ?? '');
        $ip = trim((string)$candidateIp);
        $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

        return [
            'ip' => $ip !== '' ? substr($ip, 0, 64) : null,
            'user_agent' => $userAgent !== '' ? substr($userAgent, 0, 255) : null,
        ];
    }
}
