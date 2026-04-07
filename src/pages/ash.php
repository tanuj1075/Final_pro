<?php
<<<<<<< HEAD

$cacheDir = __DIR__ . '/../cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}
$topAnimeCache = $cacheDir . '/top_anime_cache.json';
$allAnime = [];

if (file_exists($topAnimeCache) && (time() - filemtime($topAnimeCache) < 3600)) {
    $allAnime = json_decode(file_get_contents($topAnimeCache), true);
} else {
    for ($i = 1; $i <= 5; $i++) { // 5 pages = 100+ anime
        $url = "https://api.jikan.moe/v4/top/anime?page=$i";

        $options = [
            'http' => [
                'header' => "User-Agent: AckerStream/1.0\r\n"
            ]
        ];
        $context = stream_context_create($options);

        $response = @file_get_contents($url, false, $context);
        if (!$response) break;

        $data = json_decode($response, true);

        if (isset($data['data'])) {
            $allAnime = array_merge($allAnime, $data['data']);
        }

        usleep(500000); // 0.5 sec delay
    }

    if (!empty($allAnime)) {
        file_put_contents($topAnimeCache, json_encode($allAnime));
    }
}

// Group anime by genre
$animeByGenre = [];
$genreMap = [];
foreach ($allAnime as $anime) {
    if (!empty($anime['genres'])) {
        foreach ($anime['genres'] as $genre) {
            $genreName = $genre['name'];
            $genreMap[$genreName] = $genre['mal_id'];
            $animeByGenre[$genreName][] = $anime;
        }
    } else {
        $animeByGenre['Uncategorized'][] = $anime;
    }
}
ksort($animeByGenre); // Sort genres alphabetically

require_once __DIR__ . '/../utils/security.php';
include 'anime.php';
=======
require_once __DIR__ . '/../utils/security.php';
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
secure_session_start();
check_user_active();

$isUserLoggedIn  = isset($_SESSION['user_logged_in'])  && $_SESSION['user_logged_in']  === true;
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$displayName     = $_SESSION['username'] ?? $_SESSION['admin_username'] ?? 'Guest';

if (!$isUserLoggedIn && !$isAdminLoggedIn) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    destroy_session_and_cookie();
    header('Location: login.php?logout=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AckerStream</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<<<<<<< HEAD
  <link href="../styles/AT.css" rel="stylesheet">
=======
  <link href="/src/styles/AT.css" rel="stylesheet">
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
</head>

<body>

  <div class="cursor-dot" id="cursorDot" aria-hidden="true"></div>

  <?php require_once __DIR__ . '/../components/Navbar.php'; ?>

  <div id="animeCarousel" class="carousel slide" data-ride="carousel">
    <ol class="carousel-indicators">
      <li data-target="#animeCarousel" data-slide-to="0" class="active"></li>
      <li data-target="#animeCarousel" data-slide-to="1"></li>
      <li data-target="#animeCarousel" data-slide-to="2"></li>
      <li data-target="#animeCarousel" data-slide-to="3"></li>
      <li data-target="#animeCarousel" data-slide-to="4"></li>
    </ol>

    <div class="carousel-inner">

      <div class="carousel-item active">
        <div class="carousel-overlay"></div>
        <img src="/src/assets/images/LordOfMysteries.jpg" class="carousel-img" alt="Lord of Mysteries" height="650" width="600">
        <div class="carousel-content">
          <img src="/src/assets/images/lord-logo.avg.avif" alt="Lord of Mysteries" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Action</span>
            <span class="badge">Slice of Life</span>
          </div>
          <p class="anime-description">
            In a dark Victorian-era world where supernatural powers rule, a man awakens with mysterious
            abilities and a secret that could unravel civilization itself.
          </p>
          <div class="action-buttons">
            <button class="watch-btn" type="button"><i class="fas fa-play"></i> WATCH NOW</button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button"><i class="fas fa-bookmark"></i></button>
            <button class="info-btn" aria-label="More Info" type="button"><i class="fas fa-info-circle"></i></button>
          </div>
        </div>
      </div>

      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="/src/assets/images/attack-on-titan.jpg" class="carousel-img" alt="Attack on Titan">
        <div class="carousel-content">
          <img src="/src/assets/images/Attack-on-Titan-Logo.png" alt="Attack on Titan" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Action</span>
            <span class="badge">Drama</span>
            <span class="badge">Fantasy</span>
          </div>
          <p class="anime-description">
            Humanity faces extinction behind massive walls, but when Titans breach the gates,
            a young soldier vows revenge. A war for survival begins.
          </p>
          <div class="action-buttons">
            <button class="watch-btn" type="button"><i class="fas fa-play"></i> WATCH NOW</button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button"><i class="fas fa-bookmark"></i></button>
            <button class="info-btn" aria-label="More Info" type="button"><i class="fas fa-info-circle"></i></button>
          </div>
        </div>
      </div>

      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="/src/assets/images/your-name.jpg" class="carousel-img" alt="Your Name">
        <div class="carousel-content">
          <img src="/src/assets/images/logo.svg" alt="Your Name" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Romance</span>
            <span class="badge">Drama</span>
            <span class="badge">Slice-of-Life</span>
          </div>
          <p class="anime-description">
            A quiet high school girl meets a boy who holds the key to her forgotten past.
            As memories return, a powerful story of fate, love, and sacrifice unfolds.
          </p>
          <div class="action-buttons">
            <button class="watch-btn" type="button"><i class="fas fa-play"></i> WATCH NOW</button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button"><i class="fas fa-bookmark"></i></button>
            <button class="info-btn" aria-label="More Info" type="button"><i class="fas fa-info-circle"></i></button>
          </div>
        </div>
      </div>

      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="/src/assets/images/weathering-with-you.jpg" class="carousel-img" alt="Weathering with You">
        <div class="carousel-content">
          <img src="/src/assets/images/logo3.webp" alt="Weathering with You" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Romance</span>
            <span class="badge">Fantasy</span>
            <span class="badge">Drama</span>
            <span class="badge">Supernatural</span>
          </div>
          <p class="anime-description">
            During a time of endless rain in Tokyo, a runaway boy meets a girl who can control the weather.
            Together, they discover the cost of miracles—and the power of love in a changing world.
          </p>
          <div class="action-buttons">
            <button class="watch-btn" type="button"><i class="fas fa-play"></i> WATCH NOW</button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button"><i class="fas fa-bookmark"></i></button>
            <button class="info-btn" aria-label="More Info" type="button"><i class="fas fa-info-circle"></i></button>
          </div>
        </div>
      </div>

      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="/src/assets/images/iwteyp.png" class="carousel-img" alt="I Want to Eat Your Pancreas">
        <div class="carousel-content">
          <img src="/src/assets/images/logo2.jpg" alt="I Want to Eat Your Pancreas" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Romance</span>
            <span class="badge">Drama</span>
            <span class="badge">Supernatural</span>
            <span class="badge">Slice-of-Life</span>
          </div>
          <p class="anime-description">
            An introverted boy stumbles upon a girl's diary and discovers she is living with a terminal illness.
            An unexpected friendship blossoms that will change both their lives forever.
          </p>
          <div class="action-buttons">
            <button class="watch-btn" type="button"><i class="fas fa-play"></i> WATCH NOW</button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button"><i class="fas fa-bookmark"></i></button>
            <button class="info-btn" aria-label="More Info" type="button"><i class="fas fa-info-circle"></i></button>
          </div>
        </div>
      </div>

    </div>

    <button class="carousel-control-prev" type="button" data-target="#animeCarousel" data-slide="prev">
      <i class="fas fa-chevron-left"></i>
      <span class="sr-only">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-target="#animeCarousel" data-slide="next">
      <i class="fas fa-chevron-right"></i>
      <span class="sr-only">Next</span>
    </button>
  </div>

  <main class="main-content">

<<<<<<< HEAD
<?php if (empty($allAnime)): ?>
    <section class="anime-section">
      <div style="color: white; padding: 20px; text-align: center; width: 100%;">
          <h3>API connection failed or is taking too long.</h3>
          <p>Try reloading. If it persists, the Jikan API may be blocking the request.</p>
      </div>
    </section>
<?php else: ?>
<?php foreach ($animeByGenre as $genreName => $animeList): ?>
    <section class="anime-section">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-list"></i> <?php echo htmlspecialchars($genreName); ?> Anime
        </h2>
        <a href="anime_hub.php?genre=<?php echo urlencode((string)($genreMap[$genreName] ?? '')); ?>&genre_name=<?php echo urlencode($genreName); ?>" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
      </div>

      <div class="slider-container">
        <?php $sliderId = 'slider-' . md5($genreName); ?>
        <button class="slider-nav prev" type="button" onclick="scrollDynamicSlider('<?php echo $sliderId; ?>', -1)" aria-label="Scroll left">
          <i class="fas fa-chevron-left"></i>
        </button>

        <div class="anime-grid" id="<?php echo $sliderId; ?>">
<?php foreach ($animeList as $anime) { ?>
          <a href="watch.php?id=<?php echo $anime['mal_id']; ?>" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="<?php echo $anime['images']['jpg']['image_url'] ?? ''; ?>" alt="<?php echo htmlspecialchars($anime['title']); ?>">
                <div class="card-overlay">
                  <button class="quick-play" type="button">
=======
    <section class="anime-section" id="most-watched">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-lightbulb"></i> Featured Light Performances
        </h2>
        <a href="video.html" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
      </div>

      <div class="slider-container">
        <button class="slider-nav prev" type="button" onclick="scrollSlider(-1)" aria-label="Scroll left">
          <i class="fas fa-chevron-left"></i>
        </button>

        <div class="anime-grid" id="cardSlider">

         <a href="watch_yourname.php" target="_blank" class="anime-card-link">
  <div class="anime-card featured">

    <div class="card-image">
      <img src="/src/assets/images/your-name card.jpg" alt="Your Name">

      <div class="card-overlay">
        <button class="quick-play">
          ▶
        </button>
      </div>

      <div class="episode-tag">Sub | Dub</div>
    </div>

    <div class="card-content">
      <h3 class="anime-title">Your Name</h3>
      <p class="anime-synopsis">
        Two strangers connected by fate begin a journey to uncover a forgotten connection.
      </p>

      <div class="card-meta">
        <span class="rating">⭐ 8.4</span>
        <span class="episodes">Movie</span>
      </div>
    </div>

  </div>
</a>

          <a href="watch_aot.php" target="_blank" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="/src/assets/images/attack-on-titan card.jpg" alt="Attack on Titan">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play Attack on Titan">
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Sub | Dub</div>
              </div>
              <div class="card-content">
<<<<<<< HEAD
                <h3 class="anime-title"><?php echo htmlspecialchars($anime['title']); ?></h3>
                <p class="anime-synopsis">
                  <?php echo htmlspecialchars(substr($anime['synopsis'] ?? '', 0, 100)); ?>...
                </p>
                <div class="card-meta">
                  <span class="rating">
                    ⭐ <?php echo htmlspecialchars($anime['score'] ?? 'N/A'); ?>
                  </span>
                  <span class="episodes">
                    <?php echo htmlspecialchars($anime['episodes'] ?? '?'); ?> Episodes
                  </span>
=======
                <h3 class="anime-title">Attack on Titan</h3>
                <p class="anime-synopsis">Humanity stands on the brink. Giant titans roam. One boy sparks the fight for survival.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 9.1</span>
                  <span class="episodes">87 Episodes</span>
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
                </div>
              </div>
            </div>
          </a>
<<<<<<< HEAD
<?php } ?>
        </div>

        <button class="slider-nav next" type="button" onclick="scrollDynamicSlider('<?php echo $sliderId; ?>', 1)" aria-label="Scroll right">
=======

          <a href="watch_demonslayer.php" target="_blank" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <!-- TODO: swap this out for the actual demon slayer card image when we get it -->
                <img src="https://images.squarespace-cdn.com/content/v1/571abd61e3214001fb3b9966/1613769110440-T2W236VNWNRGUSDEXS39/Demon+Slayer%3A+Mugen+Train+Movie+Edition+Novelized" alt="Demon Slayer">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play Demon Slayer">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Sub | Dub</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">Demon Slayer</h3>
                <p class="anime-synopsis">A boy seeks a cure for his sister and vengeance against the demon that killed his family.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 8.6</span>
                  <span class="episodes">26 Episodes</span>
                </div>
              </div>
            </div>
          </a>

          <a href="#" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="/src/assets/images/weathering-with-you.jpg" alt="Weathering with You">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play Weathering with You">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Sub | Dub</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">Weathering with You</h3>
                <p class="anime-synopsis">A runaway boy meets a girl who can manipulate the weather.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 7.9</span>
                  <span class="episodes">Movie</span>
                </div>
              </div>
            </div>
          </a>

          <a href="#" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="/src/assets/images/5 centimeters per second card.jpg" alt="5 Centimeters per Second">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play 5 Centimeters per Second">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Sub | Dub</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">5 Centimeters per Second</h3>
                <p class="anime-synopsis">A story of distance, time, and fading connection between two people.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 7.6</span>
                  <span class="episodes">Movie</span>
                </div>
              </div>
            </div>
          </a>

          <a href="#" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="/src/assets/images/The garden of words card.jpg" alt="The Garden of Words">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play The Garden of Words">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Sub | Dub</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">The Garden of Words</h3>
                <p class="anime-synopsis">A quiet bond forms between two strangers during rainy days.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 7.5</span>
                  <span class="episodes">Movie</span>
                </div>
              </div>
            </div>
          </a>

        </div>

        <button class="slider-nav next" type="button" onclick="scrollSlider(1)" aria-label="Scroll right">
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </section>
<<<<<<< HEAD
<?php endforeach; ?>
<?php endif; ?>
=======
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f

    <section class="anime-section" id="anime-journey">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-vr-cardboard"></i> Immersive Zones
        </h2>
        <a href="manga.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
      </div>

      <div class="slider-container">
        <button class="slider-nav prev" type="button" onclick="scrollSlider2(-1)" aria-label="Scroll left">
          <i class="fas fa-chevron-left"></i>
        </button>

        <div class="anime-grid" id="cardSlider2">
<<<<<<< HEAD
<?php foreach ($immersiveList as $anime): ?>
          <a href="<?php echo $anime['link']; ?>" target="_blank" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="<?php echo $anime['image']; ?>" alt="<?php echo $anime['title']; ?>">
                <div class="card-overlay">
                  <button class="quick-play" type="button">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Sub | Dub</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title"><?php echo $anime['title']; ?></h3>
                <p class="anime-synopsis"><?php echo $anime['synopsis']; ?></p>
                <div class="card-meta">
                  <span class="rating">
                    <i class="fas fa-star"></i> <?php echo $anime['rating']; ?>
                  </span>
                  <span class="episodes"><?php echo $anime['episodes']; ?></span>
=======

          <a href="anime_hub.php" target="_blank" class="anime-card-link">
            <div class="anime-card featured">
              <div class="card-image">
                <img src="/src/assets/images/5 centimeters per second card.jpg" alt="5 Centimeters per Second">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play 5 Centimeters per Second">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Movie</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">5 Centimeters per Second</h3>
                <p class="anime-synopsis">A bittersweet story about distance, time, and first love.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 8.1</span>
                  <span class="episodes">1 Movie</span>
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
                </div>
              </div>
            </div>
          </a>
<<<<<<< HEAD
<?php endforeach; ?>
=======

          <a href="video.html" target="_blank" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="/src/assets/images/Weathering With You  card.jpg" alt="Weathering With You">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play Weathering With You">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Movie</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">Weathering With You</h3>
                <p class="anime-synopsis">A runaway boy meets a girl with the power to clear the skies.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 8.3</span>
                  <span class="episodes">1 Movie</span>
                </div>
              </div>
            </div>
          </a>

          <a href="video.html" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="/src/assets/images/The garden of words card.jpg" alt="The Garden of Words">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Play The Garden of Words">
                    <i class="fas fa-play"></i>
                  </button>
                </div>
                <div class="episode-tag">Special</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">The Garden of Words</h3>
                <p class="anime-synopsis">Two lonely souls meet on rainy mornings and change each other.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 7.9</span>
                  <span class="episodes">1 Special</span>
                </div>
              </div>
            </div>
          </a>

          <a href="manga.php" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="/src/assets/images/your-name-vol-1-manga-1.jpg" alt="Your Name Manga">
                <div class="card-overlay">
                  <button class="quick-play" type="button" aria-label="Open Your Name Manga">
                    <i class="fas fa-book-open"></i>
                  </button>
                </div>
                <div class="episode-tag">Manga</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">Your Name (Manga)</h3>
                <p class="anime-synopsis">Read the original manga adaptation of the hit story.</p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 8.8</span>
                  <span class="episodes">3 Volumes</span>
                </div>
              </div>
            </div>
          </a>

>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
        </div>

        <button class="slider-nav next" type="button" onclick="scrollSlider2(1)" aria-label="Scroll right">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </section>

  </main>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../utils/control.js"></script>

  <script>
  (function() {

    // stick the navbar once you scroll past the hero
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 40);
      }, { passive: true });
    }

    // fade sections in as they come into view
    const sections = document.querySelectorAll('.anime-section');
    if ('IntersectionObserver' in window) {
      const sectionObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            sectionObs.unobserve(e.target);
          }
        });
      }, { threshold: 0.08 });
      sections.forEach(s => sectionObs.observe(s));
    } else {
      sections.forEach(s => s.classList.add('visible'));
    }

    // stagger cards in one by one
    const cards = document.querySelectorAll('.anime-card');
    if ('IntersectionObserver' in window) {
      const cardObs = new IntersectionObserver((entries) => {
        entries.forEach((e, i) => {
          if (e.isIntersecting) {
            setTimeout(() => e.target.classList.add('card-visible'), i * 60);
            cardObs.unobserve(e.target);
          }
        });
      }, { threshold: 0.05 });
      cards.forEach(c => cardObs.observe(c));
    } else {
      cards.forEach(c => c.classList.add('card-visible'));
    }

    // custom cursor dot
    const cursorDot = document.getElementById('cursorDot');
    let rafId;
    if (cursorDot) {
      document.addEventListener('mousemove', (e) => {
        cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          cursorDot.style.left = e.clientX + 'px';
          cursorDot.style.top  = e.clientY + 'px';
          document.documentElement.style.setProperty('--cx', e.clientX + 'px');
          document.documentElement.style.setProperty('--cy', e.clientY + 'px');
        });
      });
    }

    // mobile dropdown handling
    document.querySelectorAll('[data-dropdown-toggle]').forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        if (window.innerWidth <= 576) {
          e.preventDefault();
          const menu = this.closest('.nav-dropdown').querySelector('.dropdown-menu');
          if (menu) menu.classList.toggle('mobile-open');
        }
      });
    });

    // hamburger menu
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (mobileToggle && navMenu) {
      mobileToggle.addEventListener('click', () => {
        const open = navMenu.classList.toggle('active');
        mobileToggle.setAttribute('aria-expanded', open);
      });
    }

    // fallback slider functions in case control.js doesn't load
    if (typeof scrollSlider !== 'function') {
      window.scrollSlider = function(dir) {
        const grid = document.getElementById('cardSlider');
        if (grid) grid.scrollBy({ left: dir * 260, behavior: 'smooth' });
      };
    }

<<<<<<< HEAD
    if (typeof scrollDynamicSlider !== 'function') {
      window.scrollDynamicSlider = function(id, dir) {
        const grid = document.getElementById(id);
        if (grid) grid.scrollBy({ left: dir * 260, behavior: 'smooth' });
      };
    }

=======
>>>>>>> a0670c839e767ebb242c200d673457292b0a8a9f
    if (typeof scrollSlider2 !== 'function') {
      window.scrollSlider2 = function(dir) {
        const grid = document.getElementById('cardSlider2');
        if (grid) grid.scrollBy({ left: dir * 260, behavior: 'smooth' });
      };
    }

  })();
  </script>

</body>
</html>
