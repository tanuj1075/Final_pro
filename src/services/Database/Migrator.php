<?php

namespace App\Database;

use PDO;

/**
 * Handles database schema creation and initial seeding.
 */
class Migrator
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Initialize all database tables if they do not exist.
     */
    public function up(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_siteuser (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            is_approved INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            last_login TEXT NULL,
            approved_at TEXT NULL,
            status TEXT NOT NULL DEFAULT 'offline',
            last_logout TEXT NULL,
            registration_ip TEXT NULL,
            registration_user_agent TEXT NULL,
            last_seen_ip TEXT NULL,
            last_seen_user_agent TEXT NULL,
            subscription_tier TEXT NOT NULL DEFAULT 'Free',
            subscription_expires_at TEXT NULL
        )");

        $this->ensureSiteUserColumns();

        $this->db->exec("CREATE TABLE IF NOT EXISTS admin_panel_anime (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'published',
            rating REAL NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            cover_image TEXT NULL,
            type TEXT NOT NULL DEFAULT 'Series',
            release_year INTEGER NULL
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
            episode_number REAL NOT NULL,
            title TEXT NULL,
            thumbnail_url TEXT NULL,
            video_url TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (anime_id) REFERENCES admin_panel_anime(id) ON DELETE CASCADE
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

        // ── User Activity Tables ──────────────────────────────────────────────
        $this->db->exec("CREATE TABLE IF NOT EXISTS user_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            anime_id INTEGER NOT NULL,
            watched_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY (user_id) REFERENCES admin_panel_siteuser(id) ON DELETE CASCADE
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS user_favorites (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            anime_id INTEGER NOT NULL,
            added_at TEXT NOT NULL DEFAULT (datetime('now')),
            UNIQUE (user_id, anime_id),
            FOREIGN KEY (user_id) REFERENCES admin_panel_siteuser(id) ON DELETE CASCADE
        )");

        // Indexes for fast per-user lookups
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_history_user_id ON user_history(user_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_favorites_user_id ON user_favorites(user_id)");

        // ── Query performance indexes for browse/search/filter endpoints ──────
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_anime_title ON admin_panel_anime(title)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_anime_status ON admin_panel_anime(status)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_anime_created_at ON admin_panel_anime(created_at)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_anime_detail_anime_id ON admin_panel_anime_detail(anime_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_anime_genres_genre_id ON admin_panel_anime_genres(genre_id)");
    }

    /**
     * Ensure user tracking columns exist for already-created databases.
     */
    private function ensureSiteUserColumns(): void
    {
        $columns = [
            'status' => "TEXT NOT NULL DEFAULT 'offline'",
            'last_logout' => "TEXT NULL",
            'registration_ip' => "TEXT NULL",
            'registration_user_agent' => "TEXT NULL",
            'last_seen_ip' => "TEXT NULL",
            'last_seen_user_agent' => "TEXT NULL",
            'subscription_tier' => "TEXT NOT NULL DEFAULT 'Free'",
            'subscription_expires_at' => "TEXT NULL",
        ];

        foreach ($columns as $name => $definition) {
            if (!$this->siteUserHasColumn($name)) {
                $this->db->exec("ALTER TABLE admin_panel_siteuser ADD COLUMN {$name} {$definition}");
            }
        }
    }

    private function siteUserHasColumn(string $columnName): bool
    {
        $stmt = $this->db->query("PRAGMA table_info(admin_panel_siteuser)");
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            if (($row['name'] ?? '') === $columnName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Seed initial content into empty tables.
     */
    public function seed(): void
    {
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
}
