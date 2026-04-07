<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
<<<<<<< HEAD
=======
require_once __DIR__ . '/../utils/bootstrap.php';
use App\Database\Connection;
use App\Repositories\AnimeRepository;
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f

if (empty($_SESSION['user_logged_in']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

<<<<<<< HEAD
$query      = trim((string)($_GET['q'] ?? ''));
$genre_id   = trim((string)($_GET['genre'] ?? ''));
$status     = trim((string)($_GET['status'] ?? ''));
$sort       = trim((string)($_GET['sort'] ?? 'newest'));

$jikanStatus = '';
if ($status === 'published') $jikanStatus = 'complete';
if ($status === 'ongoing') $jikanStatus = 'airing';

$jikanOrder = '';
if ($sort === 'rating_desc') {
    $jikanOrder = '&order_by=score&sort=desc';
} elseif ($sort === 'title_asc') {
    $jikanOrder = '&order_by=title&sort=asc';
} elseif ($sort === 'newest' || $sort === '') {
    $jikanOrder = '&order_by=start_date&sort=desc';
}

$apiUrl = "https://api.jikan.moe/v4/anime?sfw=true";

if ($query !== '') {
    $apiUrl .= "&q=" . urlencode($query);
    // Jikan API restricts using 'start_date' ordering with text queries.
    if ($sort === 'newest' || $sort === '') {
        $jikanOrder = ''; 
    }
}

$apiUrl .= $jikanOrder;

if ($genre_id !== '') $apiUrl .= "&genres=" . urlencode($genre_id);
if ($jikanStatus !== '') $apiUrl .= "&status=" . urlencode($jikanStatus);

$options = [
    'http' => ['header' => "User-Agent: AckerStream/1.0\r\n"]
];
$context = stream_context_create($options);
$response = @file_get_contents($apiUrl, false, $context);

$animeList = [];
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        $animeList = $data['data'];
    }
}

// Hardcode top genres for dropdown
$genres = [
    ['id' => 1, 'name' => 'Action'],
    ['id' => 2, 'name' => 'Adventure'],
    ['id' => 4, 'name' => 'Comedy'],
    ['id' => 8, 'name' => 'Drama'],
    ['id' => 10, 'name' => 'Fantasy'],
    ['id' => 22, 'name' => 'Romance'],
    ['id' => 24, 'name' => 'Sci-Fi'],
    ['id' => 36, 'name' => 'Slice of Life'],
];
=======
$db = Connection::getInstance();
$animeRepo = new AnimeRepository($db);
$query  = trim((string)($_GET['q']      ?? ''));
$genre  = trim((string)($_GET['genre']  ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$sort   = trim((string)($_GET['sort']   ?? 'newest'));

// Keep filters in a known-safe set to avoid invalid combinations and confusing UX.
$allowedStatus = ['', 'published', 'ongoing'];
$allowedSort = ['newest', 'rating_desc', 'title_asc'];
if (!in_array($status, $allowedStatus, true)) {
    $status = '';
}
if (!in_array($sort, $allowedSort, true)) {
    $sort = 'newest';
}

$animeList = $animeRepo->searchAnime($query, $genre, $status, $sort);
$genres    = $animeRepo->getAllGenres();

// If the selected genre was removed/renamed, reset instead of querying a stale value.
$knownGenres = array_column($genres, 'name');
if ($genre !== '' && !in_array($genre, $knownGenres, true)) {
    $genre = '';
    $animeList = $animeRepo->searchAnime($query, $genre, $status, $sort);
}
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Anime Hub · Browse</title>
  <meta name="description" content="Browse all anime series and movies. Filter by genre, status, or rating.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0a0f1e;
      --surface: #111827;
      --card: #1a2235;
      --border: rgba(255,255,255,0.06);
      --primary: #ff4b2b;
      --secondary: #ff416c;
      --accent: #38bdf8;
      --text: #f1f5f9;
      --muted: #64748b;
      --radius: 16px;
    }
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* NAV */
    nav {
      position: sticky; top: 0; z-index: 100;
      background: rgba(10,15,30,0.85);
      backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
      padding: 14px 32px;
      display: flex; align-items: center; justify-content: space-between; gap: 24px;
    }
    .nav-brand { font-size: 20px; font-weight: 800; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; display: flex; align-items: center; gap: 8px; }
    .nav-links { display: flex; gap: 8px; }
    .nav-link { color: var(--muted); text-decoration: none; padding: 8px 14px; border-radius: 10px; font-size: 14px; font-weight: 500; transition: all 0.2s; }
    .nav-link:hover, .nav-link.active { background: rgba(255,75,43,0.1); color: var(--primary); }

    /* HERO SEARCH */
    .search-hero {
      padding: 60px 32px 40px;
      text-align: center;
      background: radial-gradient(ellipse at 50% 0%, rgba(255,75,43,0.08) 0%, transparent 70%);
    }
    .search-hero h1 { font-size: clamp(28px, 5vw, 48px); font-weight: 800; margin-bottom: 8px; }
    .search-hero h1 span { background: linear-gradient(135deg, var(--primary), var(--accent)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
    .search-hero p { color: var(--muted); margin-bottom: 32px; font-size: 16px; }

    .search-bar {
      display: flex; align-items: center; gap: 0;
      max-width: 600px; margin: 0 auto 32px;
      background: var(--card); border: 1px solid var(--border); border-radius: 14px; overflow: hidden;
    }
    .search-bar i { padding: 0 16px; color: var(--muted); }
    .search-bar input { flex: 1; background: transparent; border: none; color: var(--text); padding: 16px 0; font-size: 15px; font-family: inherit; outline: none; }
    .search-bar input::placeholder { color: var(--muted); }
    .search-bar button { background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; color: white; padding: 16px 24px; font-size: 15px; font-weight: 600; cursor: pointer; font-family: inherit; transition: opacity 0.2s; }
    .search-bar button:hover { opacity: 0.9; }

    /* FILTERS */
    .filters {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: center;
      padding: 0 32px 32px;
    }
    .filter-label { color: var(--muted); font-size: 13px; margin-right: 4px; }
    .filter-select {
      background: var(--card); border: 1px solid var(--border); color: var(--text);
      padding: 8px 14px; border-radius: 10px; font-size: 13px; font-family: inherit; cursor: pointer; outline: none;
    }
    .filter-select:focus { border-color: var(--primary); }
    .btn-apply {
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      border: none; color: white; padding: 9px 20px; border-radius: 10px;
      font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; transition: opacity 0.2s;
    }
    .btn-apply:hover { opacity: 0.88; }

    /* STATS BAR */
    .stats-bar {
      padding: 0 32px 24px;
      display: flex; align-items: center; justify-content: space-between;
    }
    .stats-bar .count { color: var(--muted); font-size: 14px; }
    .stats-bar .count span { color: var(--text); font-weight: 700; }

    /* GRID */
    .anime-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
      gap: 20px;
      padding: 0 32px 60px;
    }
    .anime-card {
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      border: 1px solid var(--border);
      transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
      text-decoration: none;
      color: var(--text);
      display: flex; flex-direction: column;
      position: relative;
    }
    .anime-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,0.5); border-color: rgba(255,75,43,0.3); color: var(--text); text-decoration: none; }
    .anime-card:hover .card-overlay { opacity: 1; }

    .card-poster { width: 100%; height: 280px; object-fit: cover; display: block; background: #111; }
    .poster-placeholder { width: 100%; height: 280px; background: linear-gradient(135deg, #1a2235, #0a0f1e); display: flex; align-items: center; justify-content: center; }
    .poster-placeholder i { font-size: 48px; color: #334155; }

    .card-overlay {
      position: absolute; top: 0; left: 0; right: 0; height: 280px;
      background: linear-gradient(to top, rgba(10,15,30,0.95) 0%, transparent 50%);
      opacity: 0; transition: opacity 0.25s;
      display: flex; align-items: flex-end; padding: 16px;
    }
    .overlay-play {
      width: 44px; height: 44px; border-radius: 50%; background: var(--primary);
      display: flex; align-items: center; justify-content: center; margin-left: auto;
    }

    .type-badge {
      position: absolute; top: 12px; left: 12px;
      background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
      padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; color: var(--accent);
    }
    .rating-badge {
      position: absolute; top: 12px; right: 12px;
      background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
      padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #fbbf24;
      display: flex; align-items: center; gap: 4px;
    }

    .card-body { padding: 14px 16px 18px; flex: 1; display: flex; flex-direction: column; }
    .card-title-text { font-size: 15px; font-weight: 700; margin-bottom: 6px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .card-meta { font-size: 12px; color: var(--muted); margin-bottom: 8px; display: flex; gap: 8px; flex-wrap: wrap; }
    .card-synopsis { font-size: 12px; color: var(--muted); line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; flex: 1; }

    /* Status dot */
    .status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; margin-right: 4px; }
    .status-published { background: #10b981; }
    .status-ongoing   { background: #3b82f6; }

    /* EMPTY STATE */
    .empty-state { grid-column: 1/-1; text-align: center; padding: 80px 20px; }
    .empty-state i { font-size: 64px; color: #1e293b; margin-bottom: 20px; display: block; }
    .empty-state h3 { color: var(--muted); font-size: 18px; margin-bottom: 8px; }
    .empty-state p  { color: #334155; font-size: 14px; }
  </style>
</head>
<body>

<form method="get" id="filterForm">
  <!-- Nav -->
  <nav>
    <a href="/ash.php" class="nav-brand"><i class="fas fa-bolt"></i> AnimePlex</a>
    <div class="nav-links">
      <a href="/ash.php" class="nav-link"><i class="fas fa-home"></i> Home</a>
      <a href="/anime_hub.php" class="nav-link active"><i class="fas fa-film"></i> Browse</a>
      <a href="/manga.php" class="nav-link"><i class="fas fa-book-open"></i> Manga</a>
    </div>
  </nav>

  <!-- Hero Search -->
  <div class="search-hero">
    <h1>Discover <span>Anime</span> You'll Love</h1>
    <p>Browse <?php echo count($animeList); ?> titles · Search, filter, and explore</p>
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search title or synopsis…">
      <button type="submit">Search</button>
    </div>
  </div>

  <!-- Filters -->
  <div class="filters">
    <span class="filter-label"><i class="fas fa-filter"></i> Filter:</span>
    <select class="filter-select" name="genre" onchange="this.form.submit()">
      <option value="">All Genres</option>
      <?php foreach ($genres as $g): ?>
<<<<<<< HEAD
        <option value="<?php echo (int)$g['id']; ?>" <?php echo $genre_id == $g['id'] ? 'selected' : ''; ?>>
=======
        <option value="<?php echo htmlspecialchars($g['name']); ?>" <?php echo $genre === $g['name'] ? 'selected' : ''; ?>>
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
          <?php echo htmlspecialchars($g['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select class="filter-select" name="status" onchange="this.form.submit()">
      <option value="">All Status</option>
      <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
      <option value="ongoing"   <?php echo $status === 'ongoing'   ? 'selected' : ''; ?>>Ongoing</option>
    </select>
    <select class="filter-select" name="sort" onchange="this.form.submit()">
      <option value="newest"      <?php echo $sort === 'newest'      ? 'selected' : ''; ?>>Newest First</option>
      <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>Top Rated</option>
      <option value="title_asc"   <?php echo $sort === 'title_asc'   ? 'selected' : ''; ?>>Title A–Z</option>
    </select>
<<<<<<< HEAD
    <?php if ($query || $genre_id || $status): ?>
=======
    <?php if ($query || $genre || $status): ?>
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
      <a href="/anime_hub.php" style="color: var(--muted); font-size: 13px; text-decoration: none; padding: 8px 12px; border-radius: 10px; border: 1px solid var(--border);">
        <i class="fas fa-times"></i> Clear
      </a>
    <?php endif; ?>
  </div>
</form>

<!-- Stats -->
<div class="stats-bar">
  <div class="count"><span><?php echo count($animeList); ?></span> titles found</div>
</div>

<!-- Anime Grid -->
<div class="anime-grid">
  <?php if (empty($animeList)): ?>
    <div class="empty-state">
      <i class="fas fa-search"></i>
      <h3>No anime matched your filters</h3>
      <p>Try adjusting your search or clearing the filters.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($animeList as $anime): ?>
<<<<<<< HEAD
    <a href="/watch.php?id=<?php echo (int)$anime['mal_id']; ?>" class="anime-card">
      <?php if (!empty($anime['images']['jpg']['image_url'])): ?>
        <img src="<?php echo htmlspecialchars($anime['images']['jpg']['image_url']); ?>" class="card-poster" alt="<?php echo htmlspecialchars($anime['title']); ?>" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
=======
    <a href="/anime_detail.php?id=<?php echo (int)$anime['id']; ?>" class="anime-card">
      <?php if (!empty($anime['poster_url'])): ?>
        <img src="<?php echo htmlspecialchars(resolve_asset_url($anime['poster_url'])); ?>" class="card-poster" alt="<?php echo htmlspecialchars($anime['title']); ?>" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
        <div class="poster-placeholder" style="display:none;"><i class="fas fa-image"></i></div>
      <?php else: ?>
        <div class="poster-placeholder"><i class="fas fa-image"></i></div>
      <?php endif; ?>

      <!-- Badges -->
      <?php if (!empty($anime['type'])): ?>
        <div class="type-badge"><?php echo htmlspecialchars($anime['type']); ?></div>
      <?php endif; ?>
<<<<<<< HEAD
      <?php if (!empty($anime['score'])): ?>
        <div class="rating-badge"><i class="fas fa-star" style="color:#fbbf24; font-size:10px;"></i> <?php echo htmlspecialchars((string)$anime['score']); ?></div>
=======
      <?php if (!empty($anime['rating'])): ?>
        <div class="rating-badge"><i class="fas fa-star" style="color:#fbbf24; font-size:10px;"></i> <?php echo htmlspecialchars((string)$anime['rating']); ?></div>
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
      <?php endif; ?>

      <!-- Hover Play Overlay -->
      <div class="card-overlay">
        <div class="overlay-play"><i class="fas fa-play" style="margin-left: 2px;"></i></div>
      </div>

      <div class="card-body">
        <div class="card-title-text"><?php echo htmlspecialchars($anime['title']); ?></div>
        <div class="card-meta">
          <span>
<<<<<<< HEAD
            <span class="status-dot status-<?php echo htmlspecialchars(strtolower($anime['status'] ?? '')); ?>"></span>
            <?php echo htmlspecialchars($anime['status'] ?? 'Unknown'); ?>
          </span>
          <?php if (!empty($anime['episodes'])): ?>
            <span><i class="fas fa-list" style="font-size:10px;"></i> <?php echo (int)$anime['episodes']; ?> eps</span>
          <?php endif; ?>
          <?php if (!empty($anime['year'])): ?>
            <span><?php echo (int)$anime['year']; ?></span>
          <?php endif; ?>
        </div>
        <p class="card-synopsis"><?php echo htmlspecialchars(substr($anime['synopsis'] ?? 'No synopsis available.', 0, 100)); ?>...</p>
=======
            <span class="status-dot status-<?php echo htmlspecialchars($anime['status']); ?>"></span>
            <?php echo htmlspecialchars(ucfirst($anime['status'])); ?>
          </span>
          <?php if (!empty($anime['total_episodes'])): ?>
            <span><i class="fas fa-list" style="font-size:10px;"></i> <?php echo (int)$anime['total_episodes']; ?> eps</span>
          <?php endif; ?>
          <?php if (!empty($anime['release_year'])): ?>
            <span><?php echo (int)$anime['release_year']; ?></span>
          <?php endif; ?>
        </div>
        <p class="card-synopsis"><?php echo htmlspecialchars((string)($anime['synopsis'] ?? 'No synopsis available.')); ?></p>
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
      </div>
    </a>
  <?php endforeach; ?>
</div>

</body>
</html>
