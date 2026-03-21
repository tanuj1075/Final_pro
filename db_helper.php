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
        $dataDir = $this->resolveDataDirectory();
        $this->db_path = $dataDir . '/app.sqlite';

        try {
            $this->db = new PDO('sqlite:' . $this->db_path);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->initializeSchema();
            $this->seedInitialData();
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage() . ' (path: ' . $this->db_path . ')');
        }
    }

    private function resolveDataDirectory() {
        $candidates = [];

        $envDataDir = getenv('APP_DATA_DIR');
        if (is_string($envDataDir) && trim($envDataDir) !== '') {
            $candidates[] = rtrim(trim($envDataDir), '/');
        }

        $candidates[] = __DIR__ . '/data';

        $tmpRoot = rtrim(sys_get_temp_dir(), '/');
        if ($tmpRoot !== '') {
            $candidates[] = $tmpRoot . '/final_pro_data';
        }

        $homeDir = getenv('HOME');
        if (is_string($homeDir) && trim($homeDir) !== '') {
            $candidates[] = rtrim(trim($homeDir), '/') . '/.final_pro/data';
        }

        foreach ($candidates as $candidate) {
            if ($this->ensureWritableDirectory($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException('Unable to create or write to any data directory candidate');
    }

    private function ensureWritableDirectory($path) {
        if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
            return false;
        }

        return is_writable($path);
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
            episode_number INTEGER NOT NULL,
            release_date TEXT NULL,
            stream_url TEXT NULL
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_anime_detail (
            anime_id INTEGER PRIMARY KEY,
            synopsis TEXT NOT NULL DEFAULT '',
            trailer_url TEXT NULL,
            poster_url TEXT NULL,
            manga_image_url TEXT NULL,
            stream_url TEXT NULL,
            total_episodes INTEGER NOT NULL DEFAULT 0,
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_release_schedule (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            anime_id INTEGER NOT NULL,
            episode_number INTEGER NOT NULL,
            release_date TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'upcoming',
            UNIQUE (anime_id, episode_number)
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_oauth_identity (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            provider TEXT NOT NULL,
            provider_user_id TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            UNIQUE (provider, provider_user_id),
            UNIQUE (user_id, provider)
        )");

        $this->db->exec("ALTER TABLE admin_panel_anime ADD COLUMN cover_image TEXT NULL");
        $this->db->exec("ALTER TABLE admin_panel_anime ADD COLUMN type TEXT NOT NULL DEFAULT 'Series'");
        $this->db->exec("ALTER TABLE admin_panel_anime ADD COLUMN release_year INTEGER NULL");
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

        $seedDetails = $this->db->prepare(
            "INSERT OR IGNORE INTO admin_panel_anime_detail
            (anime_id, synopsis, trailer_url, poster_url, manga_image_url, stream_url, total_episodes, updated_at)
            VALUES (:anime_id, :synopsis, :trailer_url, :poster_url, :manga_image_url, :stream_url, :total_episodes, datetime('now'))"
        );

        $detailRows = [
            [
                'anime_id' => 1,
                'synopsis' => 'Two teenagers mysteriously swap bodies and begin searching for each other across time and memory.',
                'trailer_url' => 'https://www.youtube.com/watch?v=xU47nhruN-Q',
                'poster_url' => 'your-name.jpg',
                'manga_image_url' => 'your-name-vol-1-manga-1.jpg',
                'stream_url' => 'watch1.html',
                'total_episodes' => 1,
            ],
            [
                'anime_id' => 2,
                'synopsis' => 'Eren and his allies fight for humanity\'s future in a world threatened by colossal Titans.',
                'trailer_url' => 'https://www.youtube.com/watch?v=MGRm4IzK1SQ',
                'poster_url' => 'attack-on-titan.jpg',
                'manga_image_url' => 'aot.jpg',
                'stream_url' => 'watch2.html',
                'total_episodes' => 87,
            ],
            [
                'anime_id' => 3,
                'synopsis' => 'A runaway student meets a girl able to bring back sunny skies in rain-soaked Tokyo.',
                'trailer_url' => 'https://www.youtube.com/watch?v=Q6iK6DjV_iE',
                'poster_url' => 'weathering-with-you.jpg',
                'manga_image_url' => 'Weathering With You  card.jpg',
                'stream_url' => 'video.html',
                'total_episodes' => 1,
            ],
            [
                'anime_id' => 4,
                'synopsis' => 'Three interconnected stories explore love, distance, and time passing in modern Japan.',
                'trailer_url' => 'https://www.youtube.com/watch?v=wdM7athAem0',
                'poster_url' => '8your-name.jpg',
                'manga_image_url' => '5 centimeters per second card.jpg',
                'stream_url' => 'video.html',
                'total_episodes' => 1,
            ],
        ];

        foreach ($detailRows as $detailRow) {
            $seedDetails->execute($detailRow);
        }
    }

    private function normalizeUsername($username) {
        return trim((string)$username);
    }

    private function normalizeEmail($email) {
        return strtolower(trim((string)$email));
    }

    private function isValidUsername($username) {
        return (bool)preg_match('/^[A-Za-z0-9_]{3,30}$/', $username);
    }

    public function getAllAnime($limit = null) {
        $sql = "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes
                FROM admin_panel_anime a
                LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
                ORDER BY a.created_at DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        return $this->db->query($sql)->fetchAll();
    }

    public function getAnimeByStatus($status, $limit = null) {
        $sql = "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes
                FROM admin_panel_anime a
                LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
                WHERE a.status = :status ORDER BY a.created_at DESC";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . intval($limit);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public function getFeaturedAnime($limit = 5) {
        $sql = "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes
                FROM admin_panel_anime a
                LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
                WHERE a.rating >= 8.0 ORDER BY a.rating DESC LIMIT " . intval($limit);
        return $this->db->query($sql)->fetchAll();
    }

    public function searchAnime($query = '', $genre = '', $status = '', $sort = 'rating_desc') {
        $sortSql = 'a.rating DESC';
        if ($sort === 'title_asc') {
            $sortSql = 'a.title ASC';
        } elseif ($sort === 'newest') {
            $sortSql = 'a.created_at DESC';
        }

        $sql = "SELECT DISTINCT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes
                FROM admin_panel_anime a
                LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
                LEFT JOIN admin_panel_anime_genres ag ON ag.anime_id = a.id
                LEFT JOIN admin_panel_genre g ON g.id = ag.genre_id
                WHERE 1=1";
        $params = [];

        if ($query !== '') {
            $sql .= " AND (a.title LIKE :query OR d.synopsis LIKE :query)";
            $params['query'] = '%' . $query . '%';
        }

        if ($genre !== '') {
            $sql .= " AND g.name = :genre";
            $params['genre'] = $genre;
        }

        if ($status !== '') {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY " . $sortSql;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAnimeDetailsById($animeId) {
        $stmt = $this->db->prepare(
            "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes, d.updated_at
             FROM admin_panel_anime a
             LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
             WHERE a.id = :anime_id LIMIT 1"
        );
        $stmt->execute(['anime_id' => (int)$animeId]);
        $anime = $stmt->fetch();
        if (!$anime) {
            return null;
        }

        $anime['genres'] = $this->getAnimeGenres($animeId);
        $anime['schedule'] = $this->getReleaseScheduleByAnimeId($animeId);
        return $anime;
    }

    public function getAllGenres() {
        return $this->db->query("SELECT id, name FROM admin_panel_genre ORDER BY name ASC")->fetchAll();
    }

    public function upsertAnimeContent($payload) {
        $title = trim((string)($payload['title'] ?? ''));
        if ($title === '') {
            return 'Title is required';
        }

        $animeId = isset($payload['id']) ? (int)$payload['id'] : 0;
        $status = trim((string)($payload['status'] ?? 'published'));
        $rating = (float)($payload['rating'] ?? 0);

        if ($animeId > 0) {
            $updateAnime = $this->db->prepare(
                "UPDATE admin_panel_anime
                 SET title = :title, status = :status, rating = :rating
                 WHERE id = :id"
            );
            $updateAnime->execute([
                'title' => $title,
                'status' => $status,
                'rating' => $rating,
                'id' => $animeId,
            ]);
        } else {
            $insertAnime = $this->db->prepare(
                "INSERT INTO admin_panel_anime (title, status, rating, created_at)
                 VALUES (:title, :status, :rating, datetime('now'))"
            );
            $insertAnime->execute([
                'title' => $title,
                'status' => $status,
                'rating' => $rating,
            ]);
            $animeId = (int)$this->db->lastInsertId();
        }

        $upsertDetail = $this->db->prepare(
            "INSERT INTO admin_panel_anime_detail (anime_id, synopsis, trailer_url, poster_url, manga_image_url, stream_url, total_episodes, updated_at)
             VALUES (:anime_id, :synopsis, :trailer_url, :poster_url, :manga_image_url, :stream_url, :total_episodes, datetime('now'))
             ON CONFLICT(anime_id) DO UPDATE SET
             synopsis = excluded.synopsis,
             trailer_url = excluded.trailer_url,
             poster_url = excluded.poster_url,
             manga_image_url = excluded.manga_image_url,
             stream_url = excluded.stream_url,
             total_episodes = excluded.total_episodes,
             updated_at = datetime('now')"
        );

        $upsertDetail->execute([
            'anime_id' => $animeId,
            'synopsis' => trim((string)($payload['synopsis'] ?? '')),
            'trailer_url' => trim((string)($payload['trailer_url'] ?? '')),
            'poster_url' => trim((string)($payload['poster_url'] ?? '')),
            'manga_image_url' => trim((string)($payload['manga_image_url'] ?? '')),
            'stream_url' => trim((string)($payload['stream_url'] ?? '')),
            'total_episodes' => (int)($payload['total_episodes'] ?? 0),
        ]);

        $genreNames = isset($payload['genres']) ? explode(',', (string)$payload['genres']) : [];
        $this->replaceAnimeGenresByNames($animeId, $genreNames);
        return true;
    }

    private function replaceAnimeGenresByNames($animeId, $genreNames) {
        $this->db->prepare("DELETE FROM admin_panel_anime_genres WHERE anime_id = :anime_id")
            ->execute(['anime_id' => (int)$animeId]);

        $insertGenre = $this->db->prepare("INSERT OR IGNORE INTO admin_panel_genre (name) VALUES (:name)");
        $findGenreId = $this->db->prepare("SELECT id FROM admin_panel_genre WHERE name = :name LIMIT 1");
        $linkGenre = $this->db->prepare("INSERT OR IGNORE INTO admin_panel_anime_genres (anime_id, genre_id) VALUES (:anime_id, :genre_id)");

        foreach ($genreNames as $genreNameRaw) {
            $genreName = trim($genreNameRaw);
            if ($genreName === '') {
                continue;
            }

            $insertGenre->execute(['name' => $genreName]);
            $findGenreId->execute(['name' => $genreName]);
            $genreId = $findGenreId->fetchColumn();
            if ($genreId) {
                $linkGenre->execute(['anime_id' => (int)$animeId, 'genre_id' => (int)$genreId]);
            }
        }
    }

    public function addOrUpdateReleaseSchedule($animeId, $episodeNumber, $releaseDate, $status = 'upcoming') {
        $stmt = $this->db->prepare(
            "INSERT INTO admin_panel_release_schedule (anime_id, episode_number, release_date, status)
             VALUES (:anime_id, :episode_number, :release_date, :status)
             ON CONFLICT(anime_id, episode_number) DO UPDATE SET
             release_date = excluded.release_date,
             status = excluded.status"
        );

        return $stmt->execute([
            'anime_id' => (int)$animeId,
            'episode_number' => (int)$episodeNumber,
            'release_date' => trim((string)$releaseDate),
            'status' => trim((string)$status),
        ]);
    }

    public function getReleaseScheduleByAnimeId($animeId) {
        $stmt = $this->db->prepare(
            "SELECT episode_number, release_date, status
             FROM admin_panel_release_schedule
             WHERE anime_id = :anime_id
             ORDER BY episode_number ASC"
        );
        $stmt->execute(['anime_id' => (int)$animeId]);
        return $stmt->fetchAll();
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
        $normalizedUsername = $this->normalizeUsername($username);
        $normalizedEmail = $this->normalizeEmail($email);

        if (!$this->isValidUsername($normalizedUsername)) {
            return 'Username must be 3-30 characters and use only letters, numbers, or underscores';
        }

        if (!filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address';
        }

        if (strlen((string)$password) < 8) {
            return 'Password must be at least 8 characters long';
        }

        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->db->prepare(
                "INSERT INTO admin_panel_siteuser (username, email, password_hash, is_approved, is_active, created_at)
                 VALUES (:username, :email, :password_hash, 0, 1, datetime('now'))"
            );

            $stmt->execute([
                'username' => $normalizedUsername,
                'email' => $normalizedEmail,
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
        $normalizedUsername = $this->normalizeUsername($username);

        $stmt = $this->db->prepare(
            "SELECT * FROM admin_panel_siteuser WHERE username = :username"
        );
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

    public function linkOAuthIdentity($userId, $provider, $providerId) {
        $stmt = $this->db->prepare(
            "INSERT OR IGNORE INTO admin_panel_oauth_identity (user_id, provider, provider_user_id, created_at)
             VALUES (:user_id, :provider, :provider_user_id, datetime('now'))"
        );

        $stmt->execute([
            'user_id' => (int)$userId,
            'provider' => strtolower(trim((string)$provider)),
            'provider_user_id' => trim((string)$providerId),
        ]);

        return $stmt->rowCount() > 0 || $this->getOAuthIdentity((string)$provider, (string)$providerId) !== null;
    }

    public function getOAuthIdentity($provider, $providerId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM admin_panel_oauth_identity
             WHERE provider = :provider AND provider_user_id = :provider_user_id
             LIMIT 1"
        );

        $stmt->execute([
            'provider' => strtolower(trim((string)$provider)),
            'provider_user_id' => trim((string)$providerId),
        ]);

        $identity = $stmt->fetch();
        return $identity ?: null;
    }

    public function getUserByOAuthIdentity($provider, $providerId) {
        $stmt = $this->db->prepare(
            "SELECT u.*
             FROM admin_panel_siteuser u
             INNER JOIN admin_panel_oauth_identity oi ON oi.user_id = u.id
             WHERE oi.provider = :provider AND oi.provider_user_id = :provider_user_id
             LIMIT 1"
        );

        $stmt->execute([
            'provider' => strtolower(trim((string)$provider)),
            'provider_user_id' => trim((string)$providerId),
        ]);

        $user = $stmt->fetch();
        return $user ?: null;
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
