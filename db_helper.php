<?php
/**
 * Database helper for local SQLite runtime.
 *
 * This class initializes all required tables automatically so the
 * project works out-of-the-box without requiring an external backend.
 */
class DatabaseHelper {
    private $db;
    private $db_path;

    public function __construct() {
        $dataDir = __DIR__ . '/data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $this->db_path = $dataDir . '/app.sqlite';

        try {
            $this->db = new PDO('sqlite:' . $this->db_path);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initializeSchema();
            $this->seedInitialData();
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    private function initializeSchema() {
        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_siteuser (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            is_approved INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            last_login TEXT NULL,
            approved_at TEXT NULL
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_anime (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'published',
            rating REAL NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_genre (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_anime_genres (
            anime_id INTEGER NOT NULL,
            genre_id INTEGER NOT NULL,
            PRIMARY KEY (anime_id, genre_id)
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_episode (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            anime_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            episode_number INTEGER NOT NULL
        )");
    }

    private function seedInitialData() {
        $count = (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_anime")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $seed = $this->db->prepare(
            "INSERT INTO admin_panel_anime (title, status, rating, created_at) VALUES (:title, :status, :rating, datetime('now'))"
        );

        $rows = [
            ['title' => 'Your Name', 'status' => 'published', 'rating' => 8.4],
            ['title' => 'Attack on Titan', 'status' => 'published', 'rating' => 9.1],
            ['title' => 'Weathering With You', 'status' => 'published', 'rating' => 8.3],
            ['title' => '5 Centimeters per Second', 'status' => 'published', 'rating' => 8.1],
        ];

        foreach ($rows as $row) {
            $seed->execute($row);
        }
    }

    public function getAllAnime($limit = null) {
        $sql = "SELECT * FROM admin_panel_anime ORDER BY created_at DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        return $this->db->query($sql)->fetchAll();
    }

    public function getAnimeByStatus($status, $limit = null) {
        $sql = "SELECT * FROM admin_panel_anime WHERE status = :status ORDER BY created_at DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public function getFeaturedAnime($limit = 5) {
        $sql = "SELECT * FROM admin_panel_anime WHERE rating >= 8.0 ORDER BY rating DESC LIMIT " . intval($limit);
        return $this->db->query($sql)->fetchAll();
    }

    public function getAnimeGenres($anime_id) {
        $sql = "SELECT g.* FROM admin_panel_genre g
                INNER JOIN admin_panel_anime_genres ag ON g.id = ag.genre_id
                WHERE ag.anime_id = :anime_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anime_id' => $anime_id]);
        return $stmt->fetchAll();
    }

    public function getEpisodeCount($anime_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin_panel_episode WHERE anime_id = :anime_id");
        $stmt->execute(['anime_id' => $anime_id]);
        return (int)$stmt->fetchColumn();
    }

    public function registerUser($username, $email, $password) {
        $username = trim($username);
        $email = strtolower(trim($email));

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            return 'Username must be 3-30 chars and contain only letters, numbers, and underscores';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }

        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters';
        }

        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO admin_panel_siteuser (username, email, password_hash, is_approved, is_active, created_at)
                 VALUES (:username, :email, :password_hash, 0, 1, datetime('now'))"
            );

            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash,
            ]);

            return true;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                return 'Username or email already exists';
            }
            return 'Registration failed: ' . $e->getMessage();
        }
    }

    public function loginUser($username, $password) {
        $stmt = $this->db->prepare(
            "SELECT * FROM admin_panel_siteuser WHERE username = :username"
        );
        $stmt->execute(['username' => trim($username)]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if ((int)$user['is_active'] !== 1) {
            return ['success' => false, 'message' => 'Account disabled by admin'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }

        if (!(int)$user['is_approved']) {
            return ['success' => false, 'message' => 'Account pending admin approval'];
        }

        $updateStmt = $this->db->prepare(
            "UPDATE admin_panel_siteuser SET last_login = datetime('now') WHERE id = :id"
        );
        $updateStmt->execute(['id' => $user['id']]);

        return ['success' => true, 'user' => $user];
    }

    public function getPendingUsers($limit = 25) {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, created_at
             FROM admin_panel_siteuser
             WHERE is_approved = 0 AND is_active = 1
             ORDER BY created_at ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function approveUser($userId) {
        $stmt = $this->db->prepare(
            "UPDATE admin_panel_siteuser
             SET is_approved = 1, approved_at = datetime('now')
             WHERE id = :id AND is_active = 1"
        );
        $stmt->execute(['id' => (int)$userId]);
        return $stmt->rowCount() > 0;
    }

    public function rejectUser($userId) {
        $stmt = $this->db->prepare(
            "UPDATE admin_panel_siteuser
             SET is_active = 0
             WHERE id = :id"
        );
        $stmt->execute(['id' => (int)$userId]);
        return $stmt->rowCount() > 0;
    }


    public function getUserByEmail($email) {
        $stmt = $this->db->prepare(
            "SELECT * FROM admin_panel_siteuser WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function generateUniqueUsername($base) {
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

    private function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin_panel_siteuser WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function createOAuthUser($username, $email, $provider, $providerId) {
        $randomPassword = bin2hex(random_bytes(16));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            "INSERT INTO admin_panel_siteuser (username, email, password_hash, is_approved, is_active, created_at, approved_at)
             VALUES (:username, :email, :password_hash, 1, 1, datetime('now'), datetime('now'))"
        );

        return $stmt->execute([
            'username' => $username,
            'email' => strtolower(trim($email)),
            'password_hash' => $passwordHash,
        ]);
    }

    public function touchLastLogin($userId) {
        $stmt = $this->db->prepare(
            "UPDATE admin_panel_siteuser SET last_login = datetime('now') WHERE id = :id"
        );
        $stmt->execute(['id' => (int)$userId]);
    }


    public function getUserById($userId) {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, is_approved, is_active, created_at, last_login
             FROM admin_panel_siteuser
             WHERE id = :id"
        );
        $stmt->execute(['id' => (int)$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function getAnimeCount() {
        return (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_anime")->fetchColumn();
    }

    public function getUserStats() {
        $total = (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_siteuser")->fetchColumn();
        $approved = (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_siteuser WHERE is_approved = 1 AND is_active = 1")->fetchColumn();
        $pending = (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_siteuser WHERE is_approved = 0 AND is_active = 1")->fetchColumn();

        return [
            'total' => $total,
            'approved' => $approved,
            'pending' => $pending,
        ];
    }

    public function close() {
        $this->db = null;
    }
}
?>
