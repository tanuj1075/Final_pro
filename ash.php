<?php
// PROTECTED ANIME SITE - Accessible by approved user login OR admin login.
session_start();

$isUserLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if (!$isUserLoggedIn && !$isAdminLoggedIn) {
    header('Location: login.php');
    exit;
}

// Enforce separate user panel as the first landing page for normal users.
if ($isUserLoggedIn && !$isAdminLoggedIn && !isset($_GET['from_panel'])) {
    header('Location: user_panel.php');
    exit;
}

$displayName = $_SESSION['username'] ?? ($_SESSION['admin_username'] ?? 'Guest');

if (isset($_GET['logout'])) {
    session_destroy();
    if ($isAdminLoggedIn && !$isUserLoggedIn) {
        header('Location: index.php');
    } else {
        header('Location: login.php?logout=1');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lumina Festival 2026 - Enter the Light</title>
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link href="AT.css" rel="stylesheet">
</head>

<body>
  <!-- Enhanced Navigation Bar -->
  <nav class="navbar">
    <div class="nav-container">
      <!-- Logo Section -->
      <div class="logo-section">
        <img src="bird.svg" alt="AckerStream Logo" class="logo-img">
        <span class="logo-text">LUMINA FESTIVAL</span>
      </div>

      <!-- Center Navigation Menu -->
      <div class="nav-menu">
        <ul class="nav-links">
          <li class="nav-link"><a href="#luminaHero" class="active">Stages</a></li>
          <li class="nav-link"><a href="#most-watched">Schedule</a></li>
          <li class="nav-link"><a href="#anime-journey">Installations</a></li>
          
          <!-- Categories Dropdown -->
          <li class="nav-dropdown">
            <a href="#" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="false">
              Categories <i class="fas fa-chevron-down"></i>
            </a>
            <div class="dropdown-menu">
              <a href="#most-watched" class="dropdown-item"><i class="fas fa-heart"></i> Love</a>
              <a href="#anime-journey" class="dropdown-item"><i class="fas fa-robot"></i> Sci-Fi</a>
              <a href="#most-watched" class="dropdown-item"><i class="fas fa-fist-raised"></i> Action</a>
              <a href="#anime-journey" class="dropdown-item"><i class="fas fa-laugh"></i> Comedy</a>
              <a href="#anime-journey" class="dropdown-item"><i class="fas fa-ghost"></i> Horror</a>
              <a href="#most-watched" class="dropdown-item"><i class="fas fa-home"></i> Slice of Life</a>
            </div>
          </li>
          
          <li class="nav-link"><a href="manga.html">Manga</a></li>
          
          <!-- News Dropdown -->
          <li class="nav-dropdown">
            <a href="#" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="false">
              News <i class="fas fa-chevron-down"></i>
            </a>
            <div class="dropdown-menu">
              <a href="video.html" class="dropdown-item"><i class="fas fa-newspaper"></i> All News</a>
              <a href="video.html" class="dropdown-item"><i class="fas fa-trophy"></i> Anime Awards</a>
              <a href="w.html" class="dropdown-item"><i class="fas fa-calendar-alt"></i> Events & Experiences</a>
            </div>
          </li>
        </ul>
      </div>

      <!-- Right Navigation Actions -->
      <div class="nav-actions">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" style="display: none; background: none; border: none; color: white; font-size: 24px; cursor: pointer;">
          <i class="fas fa-bars"></i>
        </button>
        
        <!-- Premium Button -->
        <div class="premium-btn">
          <div class="premium-icon">
            <i class="fas fa-crown"></i>
          </div>
          <div class="premium-text">
            <span class="premium-try">BOOK NOW</span>
            <span class="premium-label">FESTIVAL PASS</span>
          </div>
        </div>
        
        <!-- Action Icons -->
        <div class="action-icons">
          <button class="action-btn" aria-label="Search" type="button">
            <i class="fas fa-search"></i>
          </button>
          <a href="#most-watched" class="action-btn" aria-label="Bookmarks" title="Jump to most watched">
            <i class="fas fa-bookmark"></i>
          </a>
          <button class="action-btn user-btn" aria-label="User Profile" type="button" title="Logged in as <?php echo htmlspecialchars($displayName); ?>">
            <i class="fas fa-user"></i>
          </button>
          <a href="?logout=1" class="action-btn" aria-label="Logout" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <section class="lumina-hero" id="luminaHero">
    <div class="lumina-beam beam-a"></div>
    <div class="lumina-beam beam-b"></div>
    <div class="lumina-beam beam-c"></div>
    <div class="lumina-noise"></div>
    <div class="lumina-content">
      <p class="lumina-kicker">LUMINA FESTIVAL 2026</p>
      <h1>ENTER THE LIGHT</h1>
      <p class="lumina-sub">A cinematic anime convention experience where light, sound, and story converge.</p>
      <a href="#most-watched" class="lumina-cta">Explore The Program</a>
    </div>
  </section>

  <!-- Enhanced Hero Carousel -->
  <div id="animeCarousel" class="carousel slide" data-ride="carousel">
    <!-- Indicators -->
    <ol class="carousel-indicators">
      <li data-target="#animeCarousel" data-slide-to="0" class="active"></li>
      <li data-target="#animeCarousel" data-slide-to="1"></li>
      <li data-target="#animeCarousel" data-slide-to="2"></li>
      <li data-target="#animeCarousel" data-slide-to="3"></li>
      <li data-target="#animeCarousel" data-slide-to="4"></li>
    </ol>

    <!-- Carousel Items -->
    <div class="carousel-inner">
      <!-- Slide 1 -->
      <div class="carousel-item active">
        <div class="carousel-overlay"></div>
        <img src="LordOfMysteries.jpg" class="carousel-img" alt="Lord of Mysteries" height="650" width="600">
        <div class="carousel-content">
          <img src="lord-logo.avg.avif" alt="Lord of Mysteries" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Action</span>
            <span class="badge">Slice of Life</span>
          </div>
          <p class="anime-description">
            At the Japanese-style LycoReco café, orders don't stop at delicious coffee and sweets.
            From delivery to childcare, reading stories or teaching...
          </p>
          <div class="action-buttons">
            <button class="watch-btn">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="attack-on-titan.jpg" class="carousel-img" alt="Attack on Titan">
        <div class="carousel-content">
          <img src="Attack-on-Titan-Logo.png" alt="Attack on Titan" class="anime-logo">
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
            <button class="watch-btn">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>

       <!-- Slide 3 -->
            <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="your-name.jpg" class="carousel-img" alt="Attack on Titan">
        <div class="carousel-content">
          <img src="logo.svg" alt="Attack on Titan" class="anime-logo">
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
            <button class="watch-btn">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Slide 4 -->
        <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="weathering-with-you.jpg" class="carousel-img" alt="Attack on Titan">
        <div class="carousel-content">
          <img src="logo3.webp" alt="Attack on Titan" class="anime-logo">
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
            <button class="watch-btn">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Slide 5 -->
       <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="iwteyp.png" class="carousel-img" alt="Attack on Titan">
        <div class="carousel-content">
          <img src="logo2.jpg" alt="Attack on Titan" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Romance</span>
            <span class="badge">Drama</span>
            <span class="badge">Supernatural</span>
            <span class="badge">Slice-of-Life</span>
          </div>
          <p class="anime-description">
             A quiet high school girl meets a boy who holds the key to her forgotten past.
            As memories return, a powerful story of fate, love, and sacrifice unfolds.
          </p>
          <div class="action-buttons">
            <button class="watch-btn">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- Navigation Arrows -->
    <button class="carousel-control-prev" type="button" data-target="#animeCarousel" data-slide="prev">
      <i class="fas fa-chevron-left"></i>
      <span class="sr-only">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-target="#animeCarousel" data-slide="next">
      <i class="fas fa-chevron-right"></i>
      <span class="sr-only">Next</span>
    </button>
  </div>

  <!-- Enhanced Anime Sliders -->
  <main class="main-content">
    <!-- Most Watched Section -->
    <section class="anime-section" id="most-watched">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-lightbulb"></i> Featured Light Performances
        </h2>
        <a href="video.html" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
      </div>
      
      <div class="slider-container">
        <button class="slider-nav prev" onclick="scrollSlider(-1)">
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="anime-grid" id="cardSlider">
            <!-- Anime Card 1 -->
           <a href="w.html" target="_blank" class="anime-card-link">
  <div class="anime-card">
    <div class="card-image">
      <img src="your-name card.jpg" alt="Your Name">

      <div class="card-overlay">
        <button class="quick-play" aria-label="Play Your Name">
          <i class="fas fa-play"></i>
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
        <span class="rating">
          <i class="fas fa-star"></i> 8.4
        </span>
        <span class="episodes">24 Episodes</span>
      </div>
    </div>
  </div>
</a>

            <!-- Anime Card 2 -->
      <a href="video.html" target="_blank" class="anime-card-link">
  <div class="anime-card">
    <div class="card-image">
      <img src="attack-on-titan card.jpg" alt="Your Name">

      <div class="card-overlay">
        <button class="quick-play" aria-label="Play Your Name">
          <i class="fas fa-play"></i>
        </button>
      </div>

      <div class="episode-tag">Sub | Dub</div>
    </div>

    <div class="card-content">
      <h3 class="anime-title">Attack-on-Titan</h3>
      <p class="anime-synopsis">
       Humanity stands on the brink. Giant titans roam. One boy sparks the fight for survival.
      </p>
      <div class="card-meta">
        <span class="rating">
          <i class="fas fa-star"></i> 9.1
        </span>
        <span class="episodes">24 Episodes</span>
      </div>
    </div>
  </div>
</a>


            <!-- Anime Card 3 -->
          <div class="anime-card">
            <div class="card-image">
              <img src="your-name card.jpg" alt="Your Name">
              <div class="card-overlay">
                <button class="quick-play" aria-label="Play Your Name">
                  <i class="fas fa-play"></i>
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
                <span class="rating">
                  <i class="fas fa-star"></i> 9.0
                </span>
                <span class="episodes">24 Episodes</span>
              </div>
            </div>
          </div>

            <!-- Anime Card 4 -->
          <div class="anime-card">
            <div class="card-image">
              <img src="your-name card.jpg" alt="Your Name">
              <div class="card-overlay">
                <button class="quick-play" aria-label="Play Your Name">
                  <i class="fas fa-play"></i>
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
                <span class="rating">
                  <i class="fas fa-star"></i> 9.0
                </span>
                <span class="episodes">24 Episodes</span>
              </div>
            </div>
          </div>

            <!-- Anime Card 5 -->
          <div class="anime-card">
            <div class="card-image">
              <img src="your-name card.jpg" alt="Your Name">
              <div class="card-overlay">
                <button class="quick-play" aria-label="Play Your Name">
                  <i class="fas fa-play"></i>
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
                <span class="rating">
                  <i class="fas fa-star"></i> 9.0
                </span>
                <span class="episodes">24 Episodes</span>
              </div>
            </div>
          </div>

            <!-- Anime Card 6 -->
          <div class="anime-card">
            <div class="card-image">
              <img src="your-name card.jpg" alt="Your Name">
              <div class="card-overlay">
                <button class="quick-play" aria-label="Play Your Name">
                  <i class="fas fa-play"></i>
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
                <span class="rating">
                  <i class="fas fa-star"></i> 9.0
                </span>
                <span class="episodes">24 Episodes</span>
              </div>
            </div>
          </div>
          <!-- Add remaining cards similarly -->
        </div>
        
        <button class="slider-nav next" onclick="scrollSlider(1)">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </section>

    <!-- Anime Journey Section -->
    <section class="anime-section" id="anime-journey">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-vr-cardboard"></i> Immersive Zones
        </h2>
        <a href="manga.html" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
      </div>
      
      <div class="slider-container">
        <button class="slider-nav prev" onclick="scrollSlider2(-1)">
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="anime-grid" id="cardSlider2">
          <a href="w.html" target="_blank" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="5 centimeters per second card.jpg" alt="5 Centimeters per Second">
                <div class="card-overlay">
                  <button class="quick-play" aria-label="Play 5 Centimeters per Second" type="button">
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
                </div>
              </div>
            </div>
          </a>

          <a href="video.html" target="_blank" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="Weathering With You  card.jpg" alt="Weathering With You">
                <div class="card-overlay">
                  <button class="quick-play" aria-label="Play Weathering With You" type="button">
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
                <img src="The garden of words card.jpg" alt="The Garden of Words">
                <div class="card-overlay">
                  <button class="quick-play" aria-label="Play The Garden of Words" type="button">
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

          <a href="manga.html" class="anime-card-link">
            <div class="anime-card">
              <div class="card-image">
                <img src="your-name-vol-1-manga-1.jpg" alt="Your Name Manga">
                <div class="card-overlay">
                  <button class="quick-play" aria-label="Open Your Name Manga" type="button">
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
        </div>
        
        <button class="slider-nav next" onclick="scrollSlider2(1)">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </section>
  </main>

  <!-- JavaScript -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script src="control.js"></script>
</body>
</html>
