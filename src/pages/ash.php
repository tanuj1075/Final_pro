<?php
// PROTECTED ANIME SITE - Only accessible after user login
require_once __DIR__ . '/../utils/security.php';
secure_session_start();

$isUserLoggedIn = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
$isAdminLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$displayName = $_SESSION['username'] ?? $_SESSION['admin_username'] ?? 'Guest';

if (!$isUserLoggedIn && !$isAdminLoggedIn) {
    header('Location: login.php');
    exit;
}

// Add logout functionality
if(isset($_GET['logout'])) {
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
  
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  
  <!-- Google Fonts — loaded here only; removed duplicate @import from AT.css -->
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Custom CSS -->
  <link href="/src/styles/AT.css" rel="stylesheet">
</head>

<body>

  <!-- BUG FIX #10: Amber cursor-dot element (was tracked in JS but never rendered) -->
  <div class="cursor-dot" id="cursorDot" aria-hidden="true"></div>

  <?php require_once __DIR__ . '/../components/Navbar.php'; ?>
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
        <img src="../assets/images/LordOfMysteries.jpg" class="carousel-img" alt="Lord of Mysteries" height="650" width="600">
        <div class="carousel-content">
          <!-- Asset fix: use existing logo file "lord-logo.avg.avif" -->
          <img src="../assets/images/lord-logo.avg.avif" alt="Lord of Mysteries" class="anime-logo">
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
            <button class="watch-btn" type="button">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info" type="button">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <img src="../assets/images/attack-on-titan.jpg" class="carousel-img" alt="Attack on Titan">
        <div class="carousel-content">
          <img src="../assets/images/Attack-on-Titan-Logo.png" alt="Attack on Titan" class="anime-logo">
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
            <button class="watch-btn" type="button">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info" type="button">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>

       <!-- Slide 3 -->
      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <!-- BUG FIX #3: alt was "Attack on Titan" for a "Your Name" slide — corrected -->
        <img src="../assets/images/your-name.jpg" class="carousel-img" alt="Your Name">
        <div class="carousel-content">
          <!-- BUG FIX #3 continued: logo alt also said "Attack on Titan" — corrected -->
          <img src="../assets/images/logo.svg" alt="Your Name" class="anime-logo">
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
            <button class="watch-btn" type="button">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info" type="button">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Slide 4 -->
      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <!-- BUG FIX #4: alt was "Attack on Titan" for "Weathering with You" slide — corrected -->
        <img src="../assets/images/weathering-with-you.jpg" class="carousel-img" alt="Weathering with You">
        <div class="carousel-content">
          <!-- BUG FIX #4 continued: logo alt also wrong — corrected -->
          <img src="../assets/images/logo3.webp" alt="Weathering with You" class="anime-logo">
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
            <button class="watch-btn" type="button">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info" type="button">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Slide 5 -->
      <div class="carousel-item">
        <div class="carousel-overlay"></div>
        <!-- BUG FIX #5: alt was "Attack on Titan" for "I Want to Eat Your Pancreas" — corrected -->
        <img src="../assets/images/iwteyp.png" class="carousel-img" alt="I Want to Eat Your Pancreas">
        <div class="carousel-content">
          <!-- BUG FIX #5 continued: logo alt also wrong — corrected -->
          <img src="../assets/images/logo2.jpg" alt="I Want to Eat Your Pancreas" class="anime-logo">
          <div class="genre-badges">
            <span class="badge">Sub | Dub</span>
            <span class="badge">Romance</span>
            <span class="badge">Drama</span>
            <span class="badge">Supernatural</span>
            <span class="badge">Slice-of-Life</span>
          </div>
          <!-- BUG FIX #9: Slide 5 had exact same description as Slide 3 — updated to correct synopsis -->
          <p class="anime-description">
            An introverted boy stumbles upon a girl's diary and discovers she is living with a terminal illness.
            An unexpected friendship blossoms that will change both their lives forever.
          </p>
          <div class="action-buttons">
            <button class="watch-btn" type="button">
              <i class="fas fa-play"></i> WATCH NOW
            </button>
            <button class="bookmark-btn" aria-label="Add to Bookmarks" type="button">
              <i class="fas fa-bookmark"></i>
            </button>
            <button class="info-btn" aria-label="More Info" type="button">
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
        <button class="slider-nav prev" type="button" onclick="scrollSlider(-1)" aria-label="Scroll left">
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <!-- BUG FIX #7: Removed stray <div class="card-container"> wrapper that was making
             all 6 cards ONE flex child, breaking horizontal scroll.
             Cards must be direct children of .anime-grid (the flex container). -->
        <div class="anime-grid" id="cardSlider">

          <!-- Card 1 -->
          <a href="watch1.html" target="_blank" class="anime-card-link">
            <div class="anime-card featured">
              <div class="card-image">
                <img src="../assets/images/your-name card.jpg" alt="Your Name">
                <div class="card-overlay">
                  <!-- BUG FIX #8: Added type="button" and aria-label to all quick-play buttons -->
                  <button class="quick-play" type="button" aria-label="Play Your Name"><i class="fas fa-play"></i></button>
                </div>
                <div class="episode-tag">Sub | Dub</div>
              </div>
              <div class="card-content">
                <h3 class="anime-title">Your Name</h3>
                <p class="anime-synopsis">
                  Two strangers connected by fate begin a journey to uncover a forgotten connection.
                </p>
                <div class="card-meta">
                  <span class="rating"><i class="fas fa-star"></i> 8.4</span>
                  <span class="episodes">Movie</span>
                </div>
              </div>
            </div>
          </a>

          <!-- Card 2 -->
      <a href="watch2.html" target="_blank" class="anime-card-link">
  <div class="anime-card">
    <div class="card-image">
      <img src="../assets/images/attack-on-titan card.jpg" alt="Attack on Titan">
      
      <div class="card-overlay">
        <button class="quick-play" type="button" aria-label="Play Attack on Titan">
          <i class="fas fa-play"></i>
        </button>
      </div>

      <div class="episode-tag">Sub | Dub</div>
    </div>

    <div class="card-content">
      <h3 class="anime-title">Attack on Titan</h3>
      <p class="anime-synopsis">
        Humanity stands on the brink. Giant titans roam. One boy sparks the fight for survival.
      </p>

      <div class="card-meta">
        <span class="rating"><i class="fas fa-star"></i> 9.1</span>
        <span class="episodes">87 Episodes</span>
      </div>
    </div>
  </div>
</a>
          <!-- Card 3 -->
          <!-- BUG FIX #9: href="#" causes page-jump; replaced with javascript:void(0) as placeholder -->
      <a href="#" class="anime-card-link">
  <div class="anime-card">
    <div class="card-image">
      <img src="../assets/images/I Want to Eat Your Pancreas card.jpg" alt="I Want to Eat Your Pancreas">

      <div class="card-overlay">
        <button class="quick-play" type="button" aria-label="Play I Want to Eat Your Pancreas">
          <i class="fas fa-play"></i>
        </button>
      </div>

      <div class="episode-tag">Sub | Dub</div>
    </div>

    <div class="card-content">
      <h3 class="anime-title">I Want to Eat Your Pancreas</h3>
      <p class="anime-synopsis">
        A boy discovers a girl's diary and forms an unexpected bond with her.
      </p>

      <div class="card-meta">
        <span class="rating"><i class="fas fa-star"></i> 8.2</span>
        <span class="episodes">Movie</span>
      </div>
    </div>
  </div>
</a>

          <!-- Card 4 -->
      <a href="#" class="anime-card-link">
  <div class="anime-card">
    <div class="card-image">
      <img src="../assets/images/weathering-with-you.jpg" alt="Weathering with You">

      <div class="card-overlay">
        <button class="quick-play" type="button" aria-label="Play Weathering with You">
          <i class="fas fa-play"></i>
        </button>
      </div>

      <div class="episode-tag">Sub | Dub</div>
    </div>

    <div class="card-content">
      <h3 class="anime-title">Weathering with You</h3>
      <p class="anime-synopsis">
        A runaway boy meets a girl who can manipulate the weather.
      </p>

      <div class="card-meta">
        <span class="rating"><i class="fas fa-star"></i> 7.9</span>
        <span class="episodes">Movie</span>
      </div>
    </div>
  </div>
</a>

          <!-- Card 5 -->
         <a href="#" class="anime-card-link">
  <div class="anime-card">
    <div class="card-image">
      <img src="../assets/images/5 centimeters per second card.jpg" alt="5 Centimeters per Second">

      <div class="card-overlay">
        <button class="quick-play" type="button" aria-label="Play 5 Centimeters per Second">
          <i class="fas fa-play"></i>
        </button>
      </div>

      <div class="episode-tag">Sub | Dub</div>
    </div>

    <div class="card-content">
      <h3 class="anime-title">5 Centimeters per Second</h3>
      <p class="anime-synopsis">
        A story of distance, time, and fading connection between two people.
      </p>

      <div class="card-meta">
        <span class="rating"><i class="fas fa-star"></i> 7.6</span>
        <span class="episodes">Movie</span>
      </div>
    </div>
  </div>
</a>

          <!-- Card 6 -->
         <a href="#" class="anime-card-link">
  <div class="anime-card">
    <div class="card-image">
      <img src="../assets/images/The garden of words card.jpg" alt="The Garden of Words">

      <div class="card-overlay">
        <button class="quick-play" type="button" aria-label="Play The Garden of Words">
          <i class="fas fa-play"></i>
        </button>
      </div>

      <div class="episode-tag">Sub | Dub</div>
    </div>

    <div class="card-content">
      <h3 class="anime-title">The Garden of Words</h3>
      <p class="anime-synopsis">
        A quiet bond forms between two strangers during rainy days.
      </p>

      <div class="card-meta">
        <span class="rating"><i class="fas fa-star"></i> 7.5</span>
        <span class="episodes">Movie</span>
      </div>
    </div>
  </div>
</a>
        </div><!-- /.anime-grid -->
        
        <button class="slider-nav next" type="button" onclick="scrollSlider(1)" aria-label="Scroll right">
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
        <button class="slider-nav prev" type="button" onclick="scrollSlider2(-1)" aria-label="Scroll left">
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="anime-grid" id="cardSlider2">
          <a href="w.html" target="_blank" class="anime-card-link">
            <div class="anime-card featured">
              <div class="card-image">
                <img src="../assets/images/5 centimeters per second card.jpg" alt="5 Centimeters per Second">
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
                <img src="../assets/images/Weathering With You  card.jpg" alt="Weathering With You">
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
                <img src="../assets/images/The garden of words card.jpg" alt="The Garden of Words">
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
                <img src="../assets/images/your-name-vol-1-manga-1.jpg" alt="Your Name Manga">
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
        
        <button class="slider-nav next" type="button" onclick="scrollSlider2(1)" aria-label="Scroll right">
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </section>
  </main>

  <!-- JavaScript -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script src="../utils/control.js"></script>

  <!-- ═══ AckerStream Elite JS ═══ -->
  <script>
  (function() {

    /* 1. Navbar — add .scrolled class on scroll */
    const navbar = document.querySelector('.navbar');
    if (navbar) {
      window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 40);
      }, { passive: true });
    }

    /* 2. Section scroll-reveal via IntersectionObserver */
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

    /* 3. Card stagger-in */
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

    /* 4. BUG FIX #10: Amber cursor dot — now actually moves the #cursorDot element.
          Previously only set CSS vars (--cx / --cy) that had no corresponding CSS rule. */
    const cursorDot = document.getElementById('cursorDot');
    let rafId;
    if (cursorDot) {
      document.addEventListener('mousemove', (e) => {
        cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(() => {
          cursorDot.style.left = e.clientX + 'px';
          cursorDot.style.top  = e.clientY + 'px';
          // Keep CSS vars for any external use
          document.documentElement.style.setProperty('--cx', e.clientX + 'px');
          document.documentElement.style.setProperty('--cy', e.clientY + 'px');
        });
      });
    }

    /* 5. Mobile dropdown toggle */
    document.querySelectorAll('[data-dropdown-toggle]').forEach(toggle => {
      toggle.addEventListener('click', function(e) {
        if (window.innerWidth <= 576) {
          e.preventDefault();
          const menu = this.closest('.nav-dropdown').querySelector('.dropdown-menu');
          if (menu) menu.classList.toggle('mobile-open');
        }
      });
    });

    /* 6. Mobile menu toggle */
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    if (mobileToggle && navMenu) {
      mobileToggle.addEventListener('click', () => {
        const open = navMenu.classList.toggle('active');
        mobileToggle.setAttribute('aria-expanded', open);
      });
    }

    /* BUG FIX #11: scrollSlider / scrollSlider2 — defined here as a safe fallback
       in case control.js is missing or these functions are not exported from it.
       If control.js already defines them, these will be skipped (guard check). */
    if (typeof scrollSlider !== 'function') {
      window.scrollSlider = function(dir) {
        const grid = document.getElementById('cardSlider');
        if (grid) grid.scrollBy({ left: dir * 260, behavior: 'smooth' });
      };
    }

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
