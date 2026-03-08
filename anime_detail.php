<?php
require_once 'security.php';
secure_session_start();
require_once 'db_helper.php';

if (empty($_SESSION['user_logged_in']) && empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$animeId = (int)($_GET['id'] ?? 0);
$db = new DatabaseHelper();
$anime = $db->getAnimeDetailsById($animeId);
$db->close();

if (!$anime) {
    http_response_code(404);
    echo 'Anime not found';
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($anime['title']); ?> · Details</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <a href="anime_hub.php" class="btn btn-outline-secondary btn-sm mb-3">← Back to hub</a>
  <div class="card mb-4">
    <div class="row no-gutters">
      <div class="col-md-4">
        <img src="<?php echo htmlspecialchars((string)($anime['poster_url'] ?: 'icon.png')); ?>" class="img-fluid" alt="poster">
      </div>
      <div class="col-md-8">
        <div class="card-body">
          <h1 class="h3"><?php echo htmlspecialchars($anime['title']); ?></h1>
          <p><strong>Status:</strong> <?php echo htmlspecialchars($anime['status']); ?> · <strong>Rating:</strong> <?php echo htmlspecialchars((string)$anime['rating']); ?></p>
          <p><?php echo nl2br(htmlspecialchars((string)($anime['synopsis'] ?? 'No synopsis yet.'))); ?></p>
          <div class="mb-2">
            <?php foreach (($anime['genres'] ?? []) as $genre): ?>
              <span class="badge badge-dark mr-1"><?php echo htmlspecialchars($genre['name']); ?></span>
            <?php endforeach; ?>
          </div>
          <?php if (!empty($anime['trailer_url'])): ?><a class="btn btn-danger btn-sm" target="_blank" href="<?php echo htmlspecialchars($anime['trailer_url']); ?>">Watch Trailer</a><?php endif; ?>
          <?php if (!empty($anime['stream_url'])): ?><a class="btn btn-primary btn-sm" href="<?php echo htmlspecialchars($anime['stream_url']); ?>">Streaming Link</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <div class="card h-100"><div class="card-body">
        <h5>Manga Image</h5>
        <?php if (!empty($anime['manga_image_url'])): ?>
        <img src="<?php echo htmlspecialchars($anime['manga_image_url']); ?>" class="img-fluid" alt="manga image">
        <?php else: ?><p class="text-muted">No manga artwork added.</p><?php endif; ?>
      </div></div>
    </div>
    <div class="col-md-6 mb-3">
      <div class="card h-100"><div class="card-body">
        <h5>Release Schedule</h5>
        <?php if (!empty($anime['schedule'])): ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($anime['schedule'] as $item): ?>
            <li class="list-group-item px-0">Episode <?php echo (int)$item['episode_number']; ?> · <?php echo htmlspecialchars($item['release_date']); ?> · <span class="text-muted"><?php echo htmlspecialchars($item['status']); ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php else: ?><p class="text-muted">No release schedule yet.</p><?php endif; ?>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>
