<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../utils/bootstrap.php';
use App\Database\Connection;
use App\Repositories\AnimeRepository;

if (empty($_SESSION['user_logged_in']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = Connection::getInstance();
$animeRepo = new AnimeRepository($db);
$query = trim((string)($_GET['q'] ?? ''));
$genre = trim((string)($_GET['genre'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$sort = trim((string)($_GET['sort'] ?? 'rating_desc'));

$animeList = $animeRepo->searchAnime($query, $genre, $status, $sort);
$genres = $animeRepo->getAllGenres();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Anime Hub</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Centralized Anime Hub</h1>
    <a class="btn btn-outline-secondary" href="ash.php">Back</a>
  </div>

  <form class="card card-body mb-4" method="get">
    <div class="form-row">
      <div class="col-md-4 mb-2"><input class="form-control" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search title or synopsis"></div>
      <div class="col-md-2 mb-2">
        <select class="form-control" name="genre">
          <option value="">All genres</option>
          <?php foreach ($genres as $g): ?>
            <option value="<?php echo htmlspecialchars($g['name']); ?>" <?php echo $genre === $g['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 mb-2">
        <select class="form-control" name="status">
          <option value="">All status</option>
          <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
          <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
        </select>
      </div>
      <div class="col-md-2 mb-2">
        <select class="form-control" name="sort">
          <option value="rating_desc" <?php echo $sort === 'rating_desc' ? 'selected' : ''; ?>>Top rated</option>
          <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Title A-Z</option>
          <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
        </select>
      </div>
      <div class="col-md-2 mb-2"><button class="btn btn-primary btn-block">Apply</button></div>
    </div>
  </form>

  <div class="row">
    <?php if (!$animeList): ?>
      <div class="col-12"><div class="alert alert-warning">No anime matched your filters.</div></div>
    <?php endif; ?>
    <?php foreach ($animeList as $anime): ?>
      <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100">
          <?php if (!empty($anime['poster_url'])): ?>
            <img src="<?php echo htmlspecialchars(resolve_asset_url($anime['poster_url'])); ?>" class="card-img-top" style="height:220px;object-fit:cover" alt="<?php echo htmlspecialchars($anime['title']); ?>">
          <?php endif; ?>
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?php echo htmlspecialchars($anime['title']); ?></h5>
            <p class="small text-muted mb-2">Rating: <?php echo htmlspecialchars((string)$anime['rating']); ?> · <?php echo htmlspecialchars($anime['status']); ?></p>
            <p class="card-text small"><?php echo htmlspecialchars(substr((string)($anime['synopsis'] ?? ''), 0, 130)); ?>...</p>
            <a class="btn btn-sm btn-outline-primary mt-auto" href="anime_detail.php?id=<?php echo (int)$anime['id']; ?>">Open full details</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
