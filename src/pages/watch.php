<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    header('Location: /ash.php');
    exit;
}

$isVercel = (getenv('VERCEL') === '1' || getenv('VERCEL_ENV') !== false);
$cacheDir = $isVercel ? '/tmp/ackerstream_cache' : __DIR__ . '/../cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}
$animeCacheFile = $cacheDir . '/anime_' . $id . '_cache.json';
$recCacheFile = $cacheDir . '/anime_' . $id . '_rec_cache.json';
$cacheTime = 3600;

$anime = null;
$recommendations = [];

if (file_exists($animeCacheFile) && (time() - filemtime($animeCacheFile) < $cacheTime)) {
    $anime = json_decode(file_get_contents($animeCacheFile), true);
} else {
    $options = ['http' => ['header' => "User-Agent: AckerStream/1.0\r\n"]];
    $context = stream_context_create($options);
    $response = @file_get_contents("https://api.jikan.moe/v4/anime/{$id}/full", false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['data'])) {
            $anime = $data['data'];
            file_put_contents($animeCacheFile, json_encode($anime));
        }
    }
}

if (!$anime) {
    die("<div style='background:#0d0d0d; color:white; padding:100px; text-align:center; font-family:sans-serif;'><h2>Anime not found or API error.</h2><a href='/ash.php' style='color:#ff4c60; text-decoration:none;'>Go Back to Home</a></div>");
}

if (file_exists($recCacheFile) && (time() - filemtime($recCacheFile) < $cacheTime)) {
    $recommendations = json_decode(file_get_contents($recCacheFile), true);
} else {
    $options = ['http' => ['header' => "User-Agent: AckerStream/1.0\r\n"]];
    $context = stream_context_create($options);
    $response = @file_get_contents("https://api.jikan.moe/v4/anime/{$id}/recommendations", false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['data'])) {
            $recommendations = $data['data'];
            file_put_contents($recCacheFile, json_encode($recommendations));
        }
    }
}

$title = htmlspecialchars($anime['title_english'] ?? $anime['title'] ?? 'Unknown Title');
$synopsis = htmlspecialchars($anime['synopsis'] ?? 'No synopsis available.');
$score = htmlspecialchars((string)($anime['score'] ?? 'N/A'));
$image = htmlspecialchars($anime['images']['webp']['large_image_url'] ?? $anime['images']['jpg']['large_image_url'] ?? '');
$trailer = $anime['trailer']['embed_url'] ?? null;

$year = $anime['year'] ?? (isset($anime['aired']['prop']['from']['year']) ? $anime['aired']['prop']['from']['year'] : 'N/A');
$episodes = $anime['episodes'] ?? '?';
$type = htmlspecialchars($anime['type'] ?? 'Unknown');

$genresArray = array_map(function($g) { return $g['name']; }, $anime['genres'] ?? []);
$genreStr = !empty($genresArray) ? htmlspecialchars(implode(', ', $genresArray)) : 'Uncategorized';

// Determine Theme
$mainGenre = $anime['genres'][0]['name'] ?? 'Default';
$themeClass = 'theme-' . strtolower(str_replace(' ', '-', $mainGenre));

$studiosArray = array_map(function($s) { return $s['name']; }, $anime['studios'] ?? []);
$studioStr = !empty($studiosArray) ? htmlspecialchars(implode(', ', $studiosArray)) : 'Unknown';
$sourceStr = htmlspecialchars($anime['source'] ?? 'Unknown');

// ── User activity tracking ────────────────────────────────────────────────
$isFavorited = false;
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    require_once __DIR__ . '/../utils/bootstrap.php';
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        try {
            $db = \App\Database\Connection::getInstance();

            // Save to history (ignore duplicates quickly by not checking first)
            $db->exec("CREATE TABLE IF NOT EXISTS user_history (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                anime_id INTEGER NOT NULL,
                watched_at TEXT NOT NULL DEFAULT (datetime('now'))
            )");
            $histStmt = $db->prepare("INSERT INTO user_history (user_id, anime_id) VALUES (?, ?)");
            $histStmt->execute([$userId, $id]);

            // Check if already favorited
            $db->exec("CREATE TABLE IF NOT EXISTS user_favorites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                anime_id INTEGER NOT NULL,
                added_at TEXT NOT NULL DEFAULT (datetime('now')),
                UNIQUE (user_id, anime_id)
            )");
            $favStmt = $db->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND anime_id = ?");
            $favStmt->execute([$userId, $id]);
            $isFavorited = (bool)$favStmt->fetch();
        } catch (\Exception $e) {
            // Silent fail — never interrupt the watch experience
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo $title; ?> - Watch Now | AckerStream</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Watch <?php echo $title; ?> - <?php echo htmlspecialchars(substr($anime['synopsis'] ?? '', 0, 100)); ?>...">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/src/styles/AT.css">
  <style>
    :root {
      --primary: #ff4c60; /* default AckerStream red */
      --primary-dark: #ff334a;
      --secondary: #6c63ff;
      --dark: #0a0f1e;
      --dark-light: #111827;
      --light: #ffffff;
      --gray: #b3b3b3;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      --transition: all 0.3s ease;
    }
    
    /* Dynamic Anime Genre Themes */
    body.theme-action {
      --primary: #e50914; /* Netflix Red */
      --primary-dark: #b20710;
      --dark: #000000;
      --dark-light: #0a0a0a;
    }
    body.theme-romance, body.theme-romance-anime {
      --primary: #ff69b4; /* Soft Pink */
      --primary-dark: #ff1493;
      --dark: #120910;
    }
    body.theme-horror {
      --primary: #c62828; /* Blood Red */
      --primary-dark: #8b0000;
      --dark: #070000;
      --dark-light: #140404;
    }
    body.theme-fantasy {
      --primary: #9b59b6; /* Mystic Purple */
      --primary-dark: #8e44ad;
      --dark: #0a0512;
    }
    body.theme-sci-fi {
      --primary: #00e5ff; /* Cyber Neon Blue */
      --primary-dark: #00bfff;
      --dark: #020813;
    }
    body.theme-comedy {
      --primary: #fbbf24; /* Cheerful Yellow */
      --primary-dark: #f59e0b;
      --dark: #0f0a00;
    }
    body.theme-adventure {
      --primary: #10b981; /* Emerald Green */
      --primary-dark: #059669;
      --dark: #02120b;
    }
    body.theme-slice-of-life {
      --primary: #f472b6; /* Warm Rose */
      --primary-dark: #db2777;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--dark);
      color: var(--light);
      overflow-x: hidden;
      line-height: 1.6;
    }
    
    /* Hero Section - Netflix Premium Style */
    .fullscreen-video {
      position: relative;
      height: 80vh;
      min-height: 600px;
      width: 100%;
      overflow: hidden;
      display: flex;
      align-items: flex-end;
      justify-content: flex-start;
      margin-top: -80px; /* Pull up behind navbar if transparent */
      padding: 0 4% 80px;
    }
    
    .fullscreen-video img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      object-position: center 15%;
      z-index: 1;
      opacity: 0.6;
      animation: zoomEffect 30s infinite alternate ease-in-out;
    }
    
    @keyframes zoomEffect {
      0% { transform: scale(1); }
      100% { transform: scale(1.08); }
    }
    
    .video-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to right, var(--dark) 0%, transparent 60%), linear-gradient(to top, var(--dark) 0%, transparent 40%);
      z-index: 2;
    }
    
    .video-text {
      position: relative;
      z-index: 3;
      text-align: left;
      max-width: 800px;
      padding: 30px 15px;
      animation: fadeInUp 1.2s ease;
    }
    
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .video-text h1 {
      font-size: 4.5rem;
      font-weight: 800;
      margin-bottom: 15px;
      font-family: 'Montserrat', sans-serif;
      background: linear-gradient(45deg, #fff, var(--primary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 5px 25px rgba(255, 76, 96, 0.3);
    }
    
    .video-text p {
      font-size: 1.2rem;
      margin-bottom: 30px;
      color: var(--light);
      text-shadow: 0 2px 4px rgba(0,0,0,0.8);
      max-width: 750px;
    }
    
    .movie-meta {
      display: flex;
      justify-content: flex-start;
      gap: 15px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }
    
    .meta-item {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.1);
      padding: 8px 20px;
      border-radius: 30px;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .meta-item i {
      color: var(--primary);
    }
    
    .btn-watch {
      background: linear-gradient(45deg, var(--primary), var(--primary-dark));
      color: white;
      border: none;
      padding: 15px 40px;
      border-radius: 30px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 10px 30px rgba(255, 76, 96, 0.3);
      text-decoration: none;
    }
    
    .btn-watch:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 40px rgba(255, 76, 96, 0.4);
    }
    
    .rating-badge {
      position: absolute;
      top: 100px;
      right: 30px;
      background: rgba(0, 0, 0, 0.7);
      padding: 8px 15px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      gap: 5px;
      font-weight: 600;
      z-index: 4;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .rating-badge i {
      color: gold;
    }
    
    /* Video Player */
    .video-screen {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      background: var(--dark);
      z-index: 2000;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }
    
    .video-controls {
      width: 100%;
      padding: 20px;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .video-screen video, .video-screen iframe {
      width: 100%;
      height: calc(100vh - 80px);
      object-fit: contain;
    }
    
    .close-video {
      background: var(--primary);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    /* Tabs */
    .tab-menu {
      background: var(--dark-light);
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      position: sticky;
      top: 70px;
      z-index: 200;
    }
    
    .tab-menu ul {
      list-style: none;
      display: flex;
      justify-content: center;
      gap: 0;
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .tab-item {
      padding: 20px 40px;
      cursor: pointer;
      font-weight: 500;
      color: var(--gray);
      transition: var(--transition);
      border-bottom: 3px solid transparent;
      position: relative;
    }
    
    .tab-item:hover {
      color: var(--light);
      background: rgba(255, 255, 255, 0.05);
    }
    
    .tab-item.active {
      color: var(--light);
      border-bottom: 3px solid var(--primary);
      background: rgba(255, 76, 96, 0.05);
    }
    
    .tab-content {
      padding: 40px;
      max-width: 1400px;
      margin: 0 auto;
      display: none;
      animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    
    #overview { display: block; }
    
    .tab-content h2 {
      font-size: 2rem;
      margin-bottom: 20px;
      color: var(--light);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .tab-content h2 i { color: var(--primary); }
    
    .tab-content p {
      font-size: 1.1rem;
      color: var(--gray);
      margin-bottom: 20px;
      max-width: 800px;
    }
    
    /* Footer */
    footer {
      background: var(--dark-light);
      padding: 50px 0 20px;
      margin-top: 50px;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    
    .footer-content {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 40px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 40px;
    }
    
    .social-links {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }
    
    .social-links a {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      color: var(--light);
      text-decoration: none;
      transition: var(--transition);
    }
    
    .social-links a:hover {
      background: var(--primary);
      transform: translateY(-3px);
    }
    
    .copyright {
      text-align: center;
      padding: 20px;
      color: var(--gray);
      font-size: 0.9rem;
      border-top: 1px solid rgba(255, 255, 255, 0.05);
      margin-top: 40px;
    }
    
    .scroll-top {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 50px;
      height: 50px;
      background: var(--primary);
      color: white;
      border-radius: 50%;
      display: none;
      justify-content: center;
      align-items: center;
      cursor: pointer;
      z-index: 100;
      transition: var(--transition);
      box-shadow: 0 5px 15px rgba(255, 76, 96, 0.3);
    }

    .btn-fav {
      background: transparent;
      color: white;
      border: 2px solid rgba(255,255,255,0.3);
      padding: 14px 30px;
      border-radius: 30px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 10px;
      backdrop-filter: blur(10px);
    }
    .btn-fav:hover, .btn-fav.active {
      background: gold;
      border-color: gold;
      color: #000;
    }
    
    .related-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
    }
    
    .related-item {
      text-decoration: none;
      color: var(--light);
      background: rgba(255, 255, 255, 0.05);
      border-radius: 10px;
      overflow: hidden;
      transition: var(--transition);
    }
    
    .related-item:hover {
      transform: translateY(-5px);
      background: rgba(255, 255, 255, 0.1);
    }
    
    .related-item img {
      width: 100%;
      height: 280px;
      object-fit: cover;
    }
    
    .related-item h4 {
      padding: 10px;
      font-size: 1rem;
    }
  </style>
</head>
<body class="<?php echo htmlspecialchars($themeClass); ?>">

<?php include __DIR__ . '/../components/Navbar.php'; ?>

<!-- Landing Section -->
<section class="fullscreen-video" id="introBanner">
  <div class="video-overlay"></div>
  <img src="<?php echo $image; ?>" alt="<?php echo $title; ?> Banner" style="object-position: center 20%;" onerror="this.onerror=null; this.src='/src/assets/images/LordOfMysteries.jpg';">
  <div class="rating-badge">
    <i class="fas fa-star"></i> <?php echo $score; ?>/10
  </div>
  <div class="video-text">
    <h1><?php echo $title; ?></h1>
    <p><?php echo htmlspecialchars(substr($anime['synopsis'] ?? '', 0, 150)) . '...'; ?></p>
    
    <div class="movie-meta">
      <span class="meta-item"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars((string)$year); ?></span>
      <span class="meta-item"><i class="fas fa-clock"></i> <?php echo htmlspecialchars((string)$episodes); ?> Eps</span>
      <span class="meta-item"><i class="fas fa-tag"></i> <?php echo $genreStr; ?></span>
      <span class="meta-item"><i class="fas fa-film"></i> <?php echo $type; ?></span>
    </div>
    
    <div class="btn-group">
      <button class="btn-watch" id="watchOverviewBtn" type="button">
        <i class="fas fa-play"></i> Watch Overview
      </button>
      <button class="btn-fav <?php echo $isFavorited ? 'active' : ''; ?>" id="favBtn"
              data-anime-id="<?php echo $id; ?>"
              data-favorited="<?php echo $isFavorited ? '1' : '0'; ?>"
              <?php echo !isset($_SESSION['user_logged_in']) ? 'title="Login to save favorites" onclick="window.location=\'login.php\'"' : 'type="button"'; ?>>
        <i class="fas fa-heart"></i>
        <span id="favLabel"><?php echo $isFavorited ? 'Saved' : 'Add to Favorites'; ?></span>
      </button>
    </div>
  </div>
</section>

<!-- Video Player Section -->
<section class="video-screen" id="watchScreen">
  <div class="video-controls">
    <button class="close-video" id="closeVideoBtn">
      <i class="fas fa-times"></i> Close Video
    </button>
    <h3><?php echo $title; ?> - Official Trailer</h3>
  </div>
  <?php if ($trailer): ?>
    <iframe id="aotTrailer" src="<?php echo htmlspecialchars($trailer); ?>&enablejsapi=1" 
            style="width: 100%; height: calc(100vh - 80px);" 
            frameborder="0" allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
  <?php else: ?>
    <!-- Fallback if no trailer -->
    <div style="width: 100%; height: calc(100vh - 80px); display: flex; flex-direction: column; justify-content: center; align-items: center; color: white;">
       <i class="fas fa-video-slash" style="font-size: 64px; color: var(--gray); margin-bottom: 20px;"></i>
       <h2 style="font-weight: 500;">Video not available</h2>
       <p style="color: var(--gray);">No official trailer was found for this anime.</p>
    </div>
  <?php endif; ?>
</section>

<!-- Tab Navigation -->
<nav class="tab-menu">
  <ul>
    <li class="tab-item active" data-tab="overview"><i class="fas fa-info-circle"></i> Overview</li>
    <li class="tab-item" data-tab="related"><i class="fas fa-link"></i> Related</li>
    <li class="tab-item" data-tab="details"><i class="fas fa-ellipsis-h"></i> Info</li>
  </ul>
</nav>

<!-- Tab Content -->
<section class="tab-content" id="overview">
  <h2><i class="fas fa-film"></i> Story Brief</h2>
  <p><?php echo nl2br($synopsis); ?></p>
</section>

<section class="tab-content" id="related">
    <h2><i class="fas fa-link"></i> Related Series</h2>
    <p>If you enjoyed this, check out these recommendations:</p>
    <div class="related-grid" style="margin-top: 20px;">
        <?php if (!empty($recommendations)): ?>
            <?php 
               $count = 0;
               foreach ($recommendations as $rec): 
                 if ($count >= 6) break; 
                 $count++;
                 $recData = $rec['entry'];
            ?>
                <a class="related-item" href="watch.php?id=<?php echo (int)$recData['mal_id']; ?>">
                    <img src="<?php echo htmlspecialchars($recData['images']['webp']['large_image_url'] ?? $recData['images']['jpg']['large_image_url'] ?? $recData['images']['jpg']['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($recData['title']); ?>">
                    <h4><?php echo htmlspecialchars($recData['title']); ?></h4>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No related series found in the database.</p>
        <?php endif; ?>
    </div>
</section>

<section class="tab-content" id="details">
    <h2><i class="fas fa-database"></i> Series Info</h2>
    <p><strong>Original Source:</strong> <?php echo $sourceStr; ?></p>
    <p><strong>Studio:</strong> <?php echo $studioStr; ?></p>
    <p><strong>Genre:</strong> <?php echo $genreStr; ?></p>
</section>

<footer>
  <div class="footer-content">
    <div class="footer-section">
      <h3>AckerStream</h3>
      <p>Your premium destination for high-quality anime and manga content.</p>
      <div class="social-links">
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-discord"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
    <div class="footer-section">
        <h3>Explore</h3>
        <ul class="footer-links">
            <li><a href="/ash.php">Home</a></li>
            <li><a href="/anime_hub.php">Movies</a></li>
            <li><a href="/manga.php">Manga</a></li>
        </ul>
    </div>
  </div>
  <div class="copyright">
    <p>© 2026 AckerStream Elite — All rights reserved.</p>
  </div>
</footer>

<div class="scroll-top" id="scrollTopBtn"><i class="fas fa-arrow-up"></i></div>

<script>
    (function() {
        const watchScreen = document.getElementById('watchScreen');
        const watchBtn = document.getElementById('watchOverviewBtn');
        const closeVideoBtn = document.getElementById('closeVideoBtn');
        const trailerVideo = document.getElementById('aotTrailer');
        const scrollBtn = document.getElementById('scrollTopBtn');
        const introBanner = document.getElementById('introBanner');

        watchBtn.addEventListener('click', () => {
            introBanner.style.display = 'none';
            watchScreen.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            if (trailerVideo && trailerVideo.tagName === 'IFRAME') {
                const src = trailerVideo.src;
                if (!src.includes('autoplay=1')) {
                    trailerVideo.src = src + (src.includes('?') ? '&' : '?') + 'autoplay=1';
                }
            } else if (trailerVideo && trailerVideo.play) {
                trailerVideo.play().catch(e => console.log("Autoplay blocked"));
            }
        });

        closeVideoBtn.addEventListener('click', () => {
            watchScreen.style.display = 'none';
            introBanner.style.display = 'flex';
            document.body.style.overflow = 'auto';
            if (trailerVideo && trailerVideo.tagName === 'IFRAME') {
                trailerVideo.src = trailerVideo.src.replace('&autoplay=1', '').replace('?autoplay=1', '');
            } else if (trailerVideo && trailerVideo.pause) {
                trailerVideo.pause();
                trailerVideo.currentTime = 0;
            }
        });

        const tabs = document.querySelectorAll('.tab-item');
        const contents = {
            overview: document.getElementById('overview'),
            related: document.getElementById('related'),
            details: document.getElementById('details')
        };

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                Object.values(contents).forEach(c => c.style.display = 'none');
                if(contents[target]) contents[target].style.display = 'block';
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });

        window.addEventListener('scroll', () => {
            scrollBtn.style.display = window.scrollY > 400 ? 'flex' : 'none';
        });
        scrollBtn.addEventListener('click', () => { window.scrollTo({ top: 0, behavior: 'smooth' }); });

        // ── Favorites toggle ──────────────────────────────────────────────
        const favBtn = document.getElementById('favBtn');
        if (favBtn && favBtn.dataset.animeId) {
            favBtn.addEventListener('click', function() {
                const label = document.getElementById('favLabel');
                const isFav = this.dataset.favorited === '1';
                const formData = new FormData();
                formData.append('anime_id', this.dataset.animeId);

                fetch('/src/services/api/favorite.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (data.action === 'added') {
                                this.dataset.favorited = '1';
                                this.classList.add('active');
                                label.textContent = 'Saved';
                            } else {
                                this.dataset.favorited = '0';
                                this.classList.remove('active');
                                label.textContent = 'Add to Favorites';
                            }
                        }
                    })
                    .catch(() => {});
            });
        }
    })();
</script>
</body>
</html>
