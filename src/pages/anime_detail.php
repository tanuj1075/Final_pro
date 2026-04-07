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

$animeId = (int)($_GET['id'] ?? 0);
$db = Connection::getInstance();
$animeRepo = new AnimeRepository($db);
$anime = $animeRepo->getAnimeDetailsById($animeId);

if (!$anime) {
    http_response_code(404);
    echo 'Anime not found';
    exit;
}

// Fetch uploaded episodes from admin_panel_episode
$episodes = [];
try {
    $epStmt = $db->prepare(
        "SELECT id, episode_number, title, thumbnail_url, video_url, created_at
         FROM admin_panel_episode
         WHERE anime_id = :anime_id
         ORDER BY episode_number ASC"
    );
    $epStmt->execute(['anime_id' => $animeId]);
    $episodes = $epStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    // Table may not exist yet — gracefully skip
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($anime['title']); ?> · Details</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root { --primary: #ff4b2b; --secondary: #ff416c; --dark: #0f172a; --card: #1e293b; }
    body { background: var(--dark); color: #f1f5f9; font-family: 'Segoe UI', sans-serif; }
    .badge-genre { background: rgba(255,75,43,0.15); color: #ff4b2b; border: 1px solid rgba(255,75,43,0.3); padding: 4px 10px; border-radius: 20px; font-size: 12px; }
    .hero-poster { width: 100%; border-radius: 16px; object-fit: cover; max-height: 420px; }
    .info-card { background: var(--card); border-radius: 16px; padding: 28px; border: 1px solid rgba(255,255,255,0.07); }
    .section-head { font-size: 20px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; color: #f8fafc; }
    .section-head i { color: #38bdf8; }

    /* Episode Grid */
    .ep-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
    .ep-card { background: var(--card); border-radius: 14px; overflow: hidden; border: 1px solid rgba(255,255,255,0.07); transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; text-decoration: none; color: inherit; }
    .ep-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(255,75,43,0.2); color: inherit; text-decoration: none; }
    .ep-thumb { width: 100%; height: 130px; object-fit: cover; background: #0f172a; display: block; }
    .ep-thumb-placeholder { width: 100%; height: 130px; background: linear-gradient(135deg, #1e293b, #0f172a); display: flex; align-items: center; justify-content: center; }
    .ep-thumb-placeholder i { font-size: 36px; color: #334155; }
    .ep-info { padding: 12px; }
    .ep-num { font-size: 11px; color: #94a3b8; margin-bottom: 2px; }
    .ep-title { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Schedule */
    .sched-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06); }
    .sched-ep { font-weight: 700; color: #38bdf8; }
    .sched-date { font-size: 13px; color: #94a3b8; }
    .sched-status { font-size: 11px; background: rgba(16,185,129,0.1); color: #10b981; padding: 2px 8px; border-radius: 20px; }

    .btn-back { background: rgba(255,255,255,0.08); color: #f1f5f9; border: none; border-radius: 10px; padding: 8px 16px; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 28px; transition: background 0.2s; }
    .btn-back:hover { background: rgba(255,255,255,0.15); color: white; text-decoration: none; }
    .btn-primary-custom { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white !important; border: none; border-radius: 10px; padding: 10px 20px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: opacity 0.2s; }
    .btn-primary-custom:hover { opacity: 0.9; text-decoration: none; }
  </style>
</head>
<body>
<div class="container py-5" style="max-width: 1100px;">
  <a href="anime_hub.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Hub</a>

  <!-- Hero Info Card -->
  <div class="row mb-4">
    <div class="col-md-3 mb-3 mb-md-0">
      <img src="<?php echo htmlspecialchars(resolve_asset_url($anime['poster_url'])); ?>" class="hero-poster" alt="poster" onerror="this.src='https://images.unsplash.com/photo-1578632767115-351597cf2477?w=300'">
    </div>
    <div class="col-md-9">
      <div class="info-card h-100">
        <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 8px;"><?php echo htmlspecialchars($anime['title']); ?></h1>
        <div class="mb-3" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
          <span style="color: #94a3b8; font-size: 13px;"><i class="fas fa-star" style="color:#f59e0b;"></i> <?php echo htmlspecialchars((string)$anime['rating']); ?></span>
          <span style="color: #94a3b8; font-size: 13px;"><i class="fas fa-circle" style="color:<?php echo $anime['status']==='published'?'#10b981':'#94a3b8'; ?>; font-size:8px;"></i> <?php echo htmlspecialchars(ucfirst($anime['status'])); ?></span>
          <?php if (!empty($anime['type'])): ?>
          <span style="color: #94a3b8; font-size: 13px;"><i class="fas fa-film"></i> <?php echo htmlspecialchars($anime['type']); ?></span>
          <?php endif; ?>
          <?php if (!empty($anime['release_year'])): ?>
          <span style="color: #94a3b8; font-size: 13px;"><?php echo (int)$anime['release_year']; ?></span>
          <?php endif; ?>
        </div>
        <div class="mb-3">
          <?php foreach (($anime['genres'] ?? []) as $genre): ?>
            <span class="badge-genre mr-1"><?php echo htmlspecialchars($genre['name']); ?></span>
          <?php endforeach; ?>
        </div>
        <p style="color: #cbd5e1; line-height: 1.7;"><?php echo nl2br(htmlspecialchars((string)($anime['synopsis'] ?? 'No synopsis yet.'))); ?></p>
        <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 20px;">
          <?php if (!empty($anime['trailer_url'])): ?>
            <a class="btn-primary-custom" style="background: linear-gradient(135deg,#dc2626,#b91c1c);" target="_blank" href="<?php echo htmlspecialchars($anime['trailer_url']); ?>"><i class="fab fa-youtube"></i> Trailer</a>
          <?php endif; ?>
          <?php if (!empty($anime['stream_url'])): ?>
            <a class="btn-primary-custom" href="<?php echo htmlspecialchars($anime['stream_url']); ?>"><i class="fas fa-play"></i> Watch Now</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Episodes Section -->
  <?php if (!empty($episodes)): ?>
  <div class="info-card mb-4">
    <div class="section-head"><i class="fas fa-play-circle"></i> Episodes (<?php echo count($episodes); ?>)</div>
    <div class="ep-grid">
      <?php foreach ($episodes as $ep): ?>
        <a href="<?php echo htmlspecialchars($ep['video_url']); ?>" target="_blank" class="ep-card">
          <?php if (!empty($ep['thumbnail_url'])): ?>
            <img src="<?php echo htmlspecialchars($ep['thumbnail_url']); ?>" class="ep-thumb" alt="ep thumbnail" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="ep-thumb-placeholder" style="display:none;"><i class="fas fa-film"></i></div>
          <?php else: ?>
            <div class="ep-thumb-placeholder"><i class="fas fa-film"></i></div>
          <?php endif; ?>
          <div class="ep-info">
            <div class="ep-num">Episode <?php echo htmlspecialchars((string)$ep['episode_number']); ?></div>
            <div class="ep-title"><?php echo htmlspecialchars($ep['title'] ?: 'Untitled Episode'); ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php elseif (($anime['type'] ?? '') === 'Series'): ?>
  <div class="info-card mb-4" style="text-align:center; padding: 40px;">
    <i class="fas fa-film" style="font-size:48px; color:#334155; margin-bottom:16px; display:block;"></i>
    <p style="color:#64748b;">No episodes uploaded yet. Check back soon.</p>
  </div>
  <?php endif; ?>

  <div class="row">
    <!-- Manga Image -->
    <div class="col-md-6 mb-4">
      <div class="info-card h-100">
        <div class="section-head"><i class="fas fa-book-open"></i> Manga Artwork</div>
        <?php if (!empty($anime['manga_image_url'])): ?>
          <img src="<?php echo htmlspecialchars(resolve_asset_url($anime['manga_image_url'])); ?>" class="img-fluid" style="border-radius:10px;" alt="manga image">
        <?php else: ?>
          <p style="color:#64748b;">No manga artwork added.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Release Schedule -->
    <div class="col-md-6 mb-4">
      <div class="info-card h-100">
        <div class="section-head"><i class="fas fa-calendar-alt"></i> Release Schedule</div>
        <?php if (!empty($anime['schedule'])): ?>
          <?php foreach ($anime['schedule'] as $item): ?>
            <div class="sched-item">
              <span class="sched-ep">Ep <?php echo (int)$item['episode_number']; ?></span>
              <span class="sched-date"><?php echo htmlspecialchars($item['release_date']); ?></span>
              <span class="sched-status"><?php echo htmlspecialchars($item['status']); ?></span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p style="color:#64748b;">No release schedule yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
</body>
</html>
