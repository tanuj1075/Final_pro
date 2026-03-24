<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
require_once __DIR__ . '/../utils/bootstrap.php';
use App\Database\Connection;
use App\Repositories\AnimeRepository;

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php?error=Admin login required');
    exit;
}

$db = Connection::getInstance();
$animeRepo = new AnimeRepository($db);
$flash = $_SESSION['manage_anime_flash'] ?? '';
unset($_SESSION['manage_anime_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_valid_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['manage_anime_flash'] = 'Invalid CSRF token.';
    } else {
        $saveResult = $animeRepo->upsertAnimeContent($_POST);
        if ($saveResult === true) {
            $animeId = (int)($_POST['id'] ?? 0);
            if ($animeId === 0) {
                $latest = $animeRepo->searchAnime(trim((string)($_POST['title'] ?? '')), '', '', 'newest');
                if (!empty($latest[0]['id'])) {
                    $animeId = (int)$latest[0]['id'];
                }
            }

            $episodeNumber = (int)($_POST['schedule_episode'] ?? 0);
            $releaseDate = trim((string)($_POST['schedule_date'] ?? ''));
            $scheduleStatus = trim((string)($_POST['schedule_status'] ?? 'upcoming'));
            if ($animeId > 0 && $episodeNumber > 0 && $releaseDate !== '') {
                $animeRepo->addOrUpdateReleaseSchedule($animeId, $episodeNumber, $releaseDate, $scheduleStatus);
            }
            $_SESSION['manage_anime_flash'] = 'Anime content saved successfully.';
        } else {
            $_SESSION['manage_anime_flash'] = $saveResult;
        }
    }
    header('Location: manage_anime.php');
    exit;
}

$animeList = $animeRepo->getAllAnime();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manage Anime Content</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Admin: Manage Anime Data</h1>
    <a href="index.php" class="btn btn-outline-secondary">Admin Panel</a>
  </div>

  <?php if ($flash !== ''): ?><div class="alert alert-info"><?php echo htmlspecialchars($flash); ?></div><?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <h2 class="h5">Add / Update Anime Record</h2>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="form-row">
          <div class="col-md-2 mb-2"><input class="form-control" name="id" type="number" placeholder="Anime ID (optional)"></div>
          <div class="col-md-3 mb-2"><input class="form-control" name="title" required placeholder="Title"></div>
          <div class="col-md-2 mb-2">
            <select class="form-control" name="type">
              <option value="Series">Series</option>
              <option value="Movie">Movie</option>
            </select>
          </div>
          <div class="col-md-1 mb-2"><input class="form-control" name="release_year" type="number" placeholder="Year"></div>
          <div class="col-md-1 mb-2"><input class="form-control" name="rating" type="number" min="0" max="10" step="0.1" placeholder="Rating"></div>
          <div class="col-md-2 mb-2">
            <select class="form-control" name="status"><option value="published">Published</option><option value="ongoing">Ongoing</option></select>
          </div>
          <div class="col-md-1 mb-2"><input class="form-control" name="total_episodes" type="number" min="0" placeholder="Eps"></div>
        </div>
        <div class="form-group"><textarea class="form-control" name="synopsis" rows="3" placeholder="Synopsis"></textarea></div>
        <div class="form-row">
          <div class="col-md-4 mb-2"><input class="form-control" name="trailer_url" placeholder="Trailer URL"></div>
          <div class="col-md-4 mb-2"><input class="form-control" name="poster_url" placeholder="Poster image path/URL"></div>
          <div class="col-md-4 mb-2"><input class="form-control" name="manga_image_url" placeholder="Manga image path/URL"></div>
        </div>
        <div class="form-row">
          <div class="col-md-4 mb-2"><input class="form-control" name="stream_url" placeholder="Streaming link"></div>
          <div class="col-md-8 mb-2"><input class="form-control" name="genres" placeholder="Genres comma separated (e.g. Action, Drama)"></div>
        </div>

        <h3 class="h6 mt-3">Optional Schedule Update</h3>
        <div class="form-row">
          <div class="col-md-2 mb-2"><input class="form-control" name="schedule_episode" type="number" min="1" placeholder="Episode #"></div>
          <div class="col-md-3 mb-2"><input class="form-control" name="schedule_date" type="date"></div>
          <div class="col-md-3 mb-2">
            <select class="form-control" name="schedule_status"><option value="upcoming">upcoming</option><option value="released">released</option><option value="delayed">delayed</option></select>
          </div>
        </div>
        <button class="btn btn-primary">Save Anime Content</button>
      </form>
    </div>
  </div>

  <div class="card"><div class="card-body">
    <h2 class="h5">Current Catalog</h2>
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th><th>Rating</th><th>Episodes</th></tr></thead>
        <tbody>
        <?php foreach ($animeList as $anime): ?>
          <tr>
            <td><?php echo (int)$anime['id']; ?></td>
            <td><?php echo htmlspecialchars($anime['title']); ?></td>
            <td><?php echo htmlspecialchars($anime['type'] ?? 'Series'); ?></td>
            <td><?php echo htmlspecialchars($anime['status']); ?></td>
            <td><?php echo htmlspecialchars((string)$anime['rating']); ?></td>
            <td><?php echo (int)($anime['total_episodes'] ?? 0); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div></div>
</div>
</body>
</html>
