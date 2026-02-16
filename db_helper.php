<?php
/**
 * Database Helper for Django SQLite Database
 * Connects PHP frontend to Django backend database
 */

class DatabaseHelper {
    private $db;
    private $db_path;
    
    public function __construct() {
        // Path to Django SQLite database
        $this->db_path = __DIR__ . '/anime_admin_project/db.sqlite3';
        
        // Connect to database
        try {
            $this->db = new PDO('sqlite:' . $this->db_path);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get all anime from database
     */
    public function getAllAnime($limit = null) {
        $sql = "SELECT * FROM admin_panel_anime ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get anime by status
     */
    public function getAnimeByStatus($status, $limit = null) {
        $sql = "SELECT * FROM admin_panel_anime WHERE status = :status ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get featured/popular anime for carousel
     */
    public function getFeaturedAnime($limit = 5) {
        $sql = "SELECT * FROM admin_panel_anime WHERE rating >= 8.0 ORDER BY rating DESC LIMIT " . intval($limit);
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get genres for an anime
     */
    public function getAnimeGenres($anime_id) {
        $sql = "SELECT g.* FROM admin_panel_genre g 
                INNER JOIN admin_panel_anime_genres ag ON g.id = ag.genre_id 
                WHERE ag.anime_id = :anime_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anime_id' => $anime_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total episode count for anime
     */
    public function getEpisodeCount($anime_id) {
        $sql = "SELECT COUNT(*) as count FROM admin_panel_episode WHERE anime_id = :anime_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anime_id' => $anime_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    /**
     * Close database connection
     */
    public function close() {
        $this->db = null;
    }
    
    /**
     * Register a new user
     */
    public function registerUser($username, $email, $password) {
        try {
            // Hash password using PBKDF2 (Django compatible)
            $password_hash = password_hash($password, PASSWORD_ARGON2ID);
            
            // Insert user
            $sql = "INSERT INTO admin_panel_siteuser (username, email, password_hash, is_approved, is_active, created_at) 
                    VALUES (:username, :email, :password_hash, 0, 1, datetime('now'))";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash
            ]);
            
            return true;
        } catch(PDOException $e) {
            // Check if username or email already exists
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                return "Username or email already exists";
            }
            return "Registration failed: " . $e->getMessage();
        }
    }
    
    /**
     * Login user
     */
    public function loginUser($username, $password) {
        $sql = "SELECT * FROM admin_panel_siteuser WHERE username = :username AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid password'];
        }
        
        // Check if approved
        if (!$user['is_approved']) {
            return ['success' => false, 'message' => 'Account pending admin approval'];
        }
        
        // Update last login
        $update_sql = "UPDATE admin_panel_siteuser SET last_login = datetime('now') WHERE id = :id";
        $update_stmt = $this->db->prepare($update_sql);
        $update_stmt->execute(['id' => $user['id']]);
        
        return ['success' => true, 'user' => $user];
    }
}
?>
