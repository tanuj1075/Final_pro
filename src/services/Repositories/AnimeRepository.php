<?php

namespace App\Repositories;

use PDO;

/**
 * Handles database operations related to anime titles, genres, and schedules.
 */
class AnimeRepository
{
    private const MAX_SEARCH_LENGTH = 120;
    private const MAX_SEARCH_RESULTS = 200;

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAllAnime(?int $limit = null): array
    {
        $sql = "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes
                FROM admin_panel_anime a
                LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
                ORDER BY a.created_at DESC";
        
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, (int)$limit);
        }

        return $this->db->query($sql)->fetchAll();
    }

    public function getAnimeByStatus(string $status, ?int $limit = null): array
    {
        $sql = "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes
                FROM admin_panel_anime a
                LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
                WHERE a.status = :status ORDER BY a.created_at DESC";
        
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, (int)$limit);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);
        return $stmt->fetchAll();
    }

    public function getFeaturedAnime(int $limit = 5): array
    {
        $sql = "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes
                FROM admin_panel_anime a
                LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
                WHERE a.rating >= 8.0 ORDER BY a.rating DESC LIMIT " . max(1, $limit);
                
        return $this->db->query($sql)->fetchAll();
    }

    public function searchAnime(string $query = '', string $genre = '', string $status = '', string $sort = 'rating_desc'): array
    {
        $query = $this->normalizeSearchQuery($query);
        $genre = trim($genre);
        $status = trim($status);

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
            // Escape LIKE wildcards so user input is treated as text, not as pattern syntax.
            $sql .= " AND (
                        a.title LIKE :query ESCAPE '\\' COLLATE NOCASE
                        OR COALESCE(d.synopsis, '') LIKE :query ESCAPE '\\' COLLATE NOCASE
                    )";
            $params['query'] = '%' . $this->escapeSqlLike($query) . '%';
        }

        if ($genre !== '') {
            $sql .= " AND g.name = :genre";
            $params['genre'] = $genre;
        }

        if ($status !== '') {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY " . $sortSql . " LIMIT " . self::MAX_SEARCH_RESULTS;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Normalize and constrain user search input so DB queries remain predictable and fast.
     */
    private function normalizeSearchQuery(string $query): string
    {
        $query = trim($query);
        // Remove control characters that can cause odd matching behavior.
        $query = (string)preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $query);
        $query = (string)preg_replace('/\s+/u', ' ', $query);

        if (mb_strlen($query) > self::MAX_SEARCH_LENGTH) {
            $query = mb_substr($query, 0, self::MAX_SEARCH_LENGTH);
        }

        return trim($query);
    }

    private function escapeSqlLike(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            '%' => '\%',
            '_' => '\_',
        ]);
    }

    public function getAnimeDetailsById(int $animeId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, d.synopsis, d.trailer_url, d.poster_url, d.manga_image_url, d.stream_url, d.total_episodes, d.updated_at
             FROM admin_panel_anime a
             LEFT JOIN admin_panel_anime_detail d ON d.anime_id = a.id
             WHERE a.id = :anime_id LIMIT 1"
        );
        $stmt->execute(['anime_id' => $animeId]);
        $anime = $stmt->fetch();
        
        if (!$anime) {
            return null;
        }

        $anime['genres'] = $this->getAnimeGenres($animeId);
        $anime['schedule'] = $this->getReleaseScheduleByAnimeId($animeId);
        return $anime;
    }

    public function getAllGenres(): array
    {
        return $this->db->query("SELECT id, name FROM admin_panel_genre ORDER BY name ASC")->fetchAll();
    }

    public function upsertAnimeContent(array $payload): string|bool
    {
        $title = trim($payload['title'] ?? '');
        if ($title === '') {
            return 'Title is required';
        }

        $animeId = isset($payload['id']) ? (int)$payload['id'] : 0;
        $status = trim($payload['status'] ?? 'published');
        $rating = (float)($payload['rating'] ?? 0);
        $type = trim($payload['type'] ?? 'Series');
        $releaseYear = !empty($payload['release_year']) ? (int)$payload['release_year'] : null;

        if ($animeId > 0) {
            $updateAnime = $this->db->prepare(
                "UPDATE admin_panel_anime
                 SET title = :title, status = :status, rating = :rating, type = :type, release_year = :release_year
                 WHERE id = :id"
            );
            $updateAnime->execute([
                'title' => $title,
                'status' => $status,
                'rating' => $rating,
                'type' => $type,
                'release_year' => $releaseYear,
                'id' => $animeId,
            ]);
        } else {
            $insertAnime = $this->db->prepare(
                "INSERT INTO admin_panel_anime (title, status, rating, created_at, type, release_year)
                 VALUES (:title, :status, :rating, datetime('now'), :type, :release_year)"
            );
            $insertAnime->execute([
                'title' => $title,
                'status' => $status,
                'rating' => $rating,
                'type' => $type,
                'release_year' => $releaseYear,
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
            'synopsis' => trim($payload['synopsis'] ?? ''),
            'trailer_url' => trim($payload['trailer_url'] ?? ''),
            'poster_url' => trim($payload['poster_url'] ?? ''),
            'manga_image_url' => trim($payload['manga_image_url'] ?? ''),
            'stream_url' => trim($payload['stream_url'] ?? ''),
            'total_episodes' => (int)($payload['total_episodes'] ?? 0),
        ]);

        $genreNames = isset($payload['genres']) ? explode(',', $payload['genres']) : [];
        $this->replaceAnimeGenresByNames($animeId, $genreNames);
        return true;
    }

    private function replaceAnimeGenresByNames(int $animeId, array $genreNames): void
    {
        $this->db->prepare("DELETE FROM admin_panel_anime_genres WHERE anime_id = :anime_id")
            ->execute(['anime_id' => $animeId]);

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
                $linkGenre->execute(['anime_id' => $animeId, 'genre_id' => (int)$genreId]);
            }
        }
    }

    public function addOrUpdateReleaseSchedule(int $animeId, int $episodeNumber, string $releaseDate, string $status = 'upcoming'): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO admin_panel_release_schedule (anime_id, episode_number, release_date, status)
             VALUES (:anime_id, :episode_number, :release_date, :status)
             ON CONFLICT(anime_id, episode_number) DO UPDATE SET
             release_date = excluded.release_date,
             status = excluded.status"
        );

        return $stmt->execute([
            'anime_id' => $animeId,
            'episode_number' => $episodeNumber,
            'release_date' => trim($releaseDate),
            'status' => trim($status),
        ]);
    }

    public function getReleaseScheduleByAnimeId(int $animeId): array
    {
        $stmt = $this->db->prepare(
            "SELECT episode_number, release_date, status
             FROM admin_panel_release_schedule
             WHERE anime_id = :anime_id
             ORDER BY episode_number ASC"
        );
        $stmt->execute(['anime_id' => $animeId]);
        return $stmt->fetchAll();
    }

    public function getAnimeGenres(int $animeId): array
    {
        $sql = "SELECT g.* FROM admin_panel_genre g
                INNER JOIN admin_panel_anime_genres ag ON g.id = ag.genre_id
                WHERE ag.anime_id = :anime_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anime_id' => $animeId]);
        return $stmt->fetchAll();
    }

    public function getEpisodeCount(int $animeId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM admin_panel_episode WHERE anime_id = :anime_id");
        $stmt->execute(['anime_id' => $animeId]);
        return (int)$stmt->fetchColumn();
    }

    public function getAnimeCount(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM admin_panel_anime")->fetchColumn();
    }
}
