<?php

namespace App\Repositories;

use PDO;
use Exception;
use Throwable;

/**
 * Handles database operations related to OAuth authentication.
 */
class OAuthRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new user from OAuth provider identity.
     *
     * @param string $username
     * @param string $email
     * @param string $provider
     * @param string $providerId
     * @return bool
     * @throws Throwable
     */
    public function createOAuthUser(string $username, string $email, string $provider, string $providerId): bool
    {
        $randomPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);
        $email = strtolower(trim($email));

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO admin_panel_siteuser (username, email, password_hash, is_approved, is_active, created_at, approved_at)
                 VALUES (:username, :email, :password_hash, 1, 1, datetime('now'), datetime('now'))"
            );

            $ok = $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);

            if (!$ok) {
                $this->db->rollBack();
                return false;
            }

            $userId = (int)$this->db->lastInsertId();
            $linked = $this->linkOAuthIdentity($userId, $provider, $providerId);
            
            if (!$linked) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Link an OAuth provider to an existing user.
     *
     * @param int $userId
     * @param string $provider
     * @param string $providerId
     * @return bool
     */
    public function linkOAuthIdentity(int $userId, string $provider, string $providerId): bool
    {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO admin_panel_oauth_identity (user_id, provider, provider_user_id, created_at)
             VALUES (:user_id, :provider, :provider_user_id, datetime('now'))"
        );

        $stmt->execute([
            'user_id' => $userId,
            'provider' => strtolower(trim($provider)),
            'provider_user_id' => trim($providerId),
        ]);

        return $stmt->rowCount() > 0 || $this->getOAuthIdentity($provider, $providerId) !== null;
    }

    /**
     * Get OAuth identity record.
     *
     * @param string $provider
     * @param string $providerId
     * @return array|null
     */
    public function getOAuthIdentity(string $provider, string $providerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM admin_panel_oauth_identity
             WHERE provider = :provider AND provider_user_id = :provider_user_id
             LIMIT 1"
        );

        $stmt->execute([
            'provider' => strtolower(trim($provider)),
            'provider_user_id' => trim($providerId),
        ]);

        $identity = $stmt->fetch();
        return $identity ?: null;
    }

    /**
     * Retrieve a user by their linked OAuth provider identity.
     *
     * @param string $provider
     * @param string $providerId
     * @return array|null
     */
    public function getUserByOAuthIdentity(string $provider, string $providerId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT u.*
             FROM admin_panel_siteuser u
             INNER JOIN admin_panel_oauth_identity oi ON oi.user_id = u.id
             WHERE oi.provider = :provider AND oi.provider_user_id = :provider_user_id
             LIMIT 1"
        );

        $stmt->execute([
            'provider' => strtolower(trim($provider)),
            'provider_user_id' => trim($providerId),
        ]);

        $user = $stmt->fetch();
        return $user ?: null;
    }
}
