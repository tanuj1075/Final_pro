<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
  <meta name="description" content="Watch Your Name - a cinematic masterpiece about fate, dreams, and timeless love. Stream the trailer, explore details, and discover related anime.">
  <title>Your Name · Timeless Romance | AckerStream</title>
  <!-- Font Awesome & Google Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/AT.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --primary: #ff4c60;
      --primary-dark: #e63e50;
      --secondary: #6c63ff;
      --dark: #0a0a0f;
      --dark-light: #111117;
      --card-bg: rgba(20, 20, 30, 0.7);
      --light: #ffffff;
      --gray: #c0c0c8;
      --shadow-xl: 0 25px 40px -12px rgba(0, 0, 0, 0.5);
      --transition: all 0.35s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--dark);
      color: var(--light);
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    /* Override Navbar if needed to fit this page's theme */
    .nav-container {
      background: rgba(10, 10, 15, 0.85) !important;
      backdrop-filter: blur(14px) !important;
    }

    /* Hero Section */
    .fullscreen-video {
      position: relative;
      height: 100vh;
      min-height: 680px;
      width: 100%;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 0; /* Adjusted for shared navbar */
    }

    .hero-backdrop {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 1;
      filter: brightness(0.45) contrast(1.05);
      animation: slowZoom 28s infinite alternate ease-in-out;
      transform-origin: center;
    }

    @keyframes slowZoom {
      0% { transform: scale(1); }
      100% { transform: scale(1.08); }
    }

    .video-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: radial-gradient(circle at center, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.75) 100%);
      z-index: 2;
    }

    .video-text {
      position: relative;
      z-index: 5;
      text-align: center;
      max-width: 880px;
      padding: 20px;
      animation: floatUp 1s ease-out;
    }

    @keyframes floatUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .video-text h1 {
      font-size: 4.8rem;
      font-weight: 800;
      font-family: 'Montserrat', sans-serif;
      background: linear-gradient(to right, #ffffff, #ffb7c5, var(--primary));
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 16px;
      letter-spacing: -1px;
      text-shadow: 0 5px 20px rgba(0,0,0,0.3);
    }

    .tagline {
      font-size: 1.25rem;
      color: rgba(255,255,240,0.9);
      margin-bottom: 28px;
      font-weight: 400;
      backdrop-filter: blur(4px);
    }

    .movie-meta {
      display: flex;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
      margin-bottom: 36px;
    }

    .meta-item {
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(12px);
      padding: 8px 20px;
      border-radius: 60px;
      font-size: 0.9rem;
      font-weight: 500;
      border: 1px solid rgba(255,255,255,0.15);
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .meta-item i {
      color: var(--primary);
    }

    .btn-group {
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .btn-watch {
      background: linear-gradient(95deg, var(--primary), #ff2b47);
      border: none;
      padding: 14px 38px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 1rem;
      color: white;
      cursor: pointer;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 15px 30px rgba(255, 76, 96, 0.35);
      text-decoration: none;
    }

    .btn-watch i {
      font-size: 1.1rem;
    }

    .btn-watch:hover {
      transform: translateY(-5px);
      box-shadow: 0 22px 35px rgba(255, 76, 96, 0.5);
      background: linear-gradient(95deg, var(--primary-dark), #ff1a3a);
    }

    .btn-outline {
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,0.3);
      box-shadow: none;
    }

    .btn-outline:hover {
      background: rgba(255, 76, 96, 0.2);
      border-color: var(--primary);
      transform: translateY(-3px);
    }

    .rating-badge {
      position: absolute;
      top: 100px; /* Adjusted for navbar */
      right: 32px;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(8px);
      padding: 8px 18px;
      border-radius: 40px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 700;
      z-index: 10;
      border: 1px solid rgba(255,215,0,0.3);
      font-size: 0.9rem;
    }

    .rating-badge i {
      color: #ffcc44;
    }

    /* Video Player Modal */
    .video-screen {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100vh;
      background: #000000dd;
      backdrop-filter: blur(20px);
      z-index: 2000;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }

    .video-container {
      width: 90%;
      max-width: 1200px;
      background: #000;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: 0 30px 60px rgba(0,0,0,0.6);
    }

    .video-controls-bar {
      background: #0a0a0a;
      padding: 14px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .close-video {
      background: var(--primary);
      border: none;
      padding: 8px 20px;
      border-radius: 40px;
      color: white;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      transition: 0.2s;
    }

    .close-video:hover {
      background: #ff1f3a;
      transform: scale(0.97);
    }

    .video-container video {
      width: 100%;
      max-height: 70vh;
      background: black;
      outline: none;
    }

    /* Tab Navigation */
    .tab-menu {
      background: var(--dark-light);
      border-bottom: 1px solid rgba(255,255,255,0.05);
      position: sticky;
      top: 70px;
      z-index: 200;
      backdrop-filter: blur(4px);
    }

    .tab-menu ul {
      display: flex;
      justify-content: center;
      gap: 8px;
      max-width: 1200px;
      margin: 0 auto;
      list-style: none;
    }

    .tab-item {
      padding: 18px 36px;
      cursor: pointer;
      font-weight: 600;
      color: var(--gray);
      transition: all 0.25s;
      border-bottom: 3px solid transparent;
      font-size: 1rem;
      letter-spacing: 0.3px;
    }

    .tab-item i {
      margin-right: 8px;
    }

    .tab-item:hover {
      color: white;
      background: rgba(255,255,255,0.05);
    }

    .tab-item.active {
      color: var(--light);
      border-bottom: 3px solid var(--primary);
      background: linear-gradient(0deg, rgba(255,76,96,0.08), transparent);
    }

    /* Tab Content */
    .tab-content {
      max-width: 1300px;
      margin: 0 auto;
      padding: 50px 32px;
      display: none;
      animation: fadeSlide 0.45s ease;
    }

    #overview { display: block; }

    @keyframes fadeSlide {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .section-title {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 5px solid var(--primary);
      padding-left: 20px;
    }

    .section-title i {
      color: var(--primary);
      font-size: 1.8rem;
    }

    .desc-text {
      font-size: 1.08rem;
      line-height: 1.7;
      color: #d1d1dc;
      margin-bottom: 24px;
      max-width: 85%;
    }

    /* Related Grid */
    .related-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 28px;
      margin-top: 20px;
    }

    .related-card {
      background: rgba(25, 25, 35, 0.7);
      backdrop-filter: blur(4px);
      border-radius: 24px;
      padding: 22px;
      transition: var(--transition);
      border: 1px solid rgba(255,255,255,0.05);
    }

    .related-card:hover {
      transform: translateY(-8px);
      background: rgba(45, 45, 60, 0.8);
      border-color: rgba(255,76,96,0.3);
    }

    .related-card strong {
      font-size: 1.3rem;
      color: white;
      display: block;
      margin-bottom: 12px;
    }

    .related-card p {
      color: #b0b0c0;
      font-size: 0.9rem;
      line-height: 1.5;
    }

    /* Details double column */
    .detail-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 48px;
      margin-top: 20px;
    }

    .detail-left {
      flex: 2;
      min-width: 260px;
    }

    .detail-right {
      flex: 1.2;
      background: rgba(20, 20, 30, 0.5);
      border-radius: 32px;
      padding: 28px;
      backdrop-filter: blur(4px);
      border: 1px solid rgba(255,255,255,0.05);
    }

    .info-list {
      list-style: none;
    }

    .info-list li {
      padding: 12px 0;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .info-list li strong {
      width: 140px;
      color: var(--light);
      font-weight: 600;
    }

    .info-list li span {
      color: #cbcbd8;
    }

    .external-link {
      color: var(--primary);
      text-decoration: none;
      font-weight: 500;
    }

    /* Footer */
    footer {
      background: #050507;
      padding: 50px 0 20px;
      margin-top: 40px;
      border-top: 1px solid rgba(255,255,255,0.05);
    }

    .footer-grid {
      max-width: 1300px;
      margin: 0 auto;
      padding: 0 32px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 40px;
    }

    .footer-section h3 {
      font-size: 1.3rem;
      margin-bottom: 20px;
    }

    .footer-links {
      list-style: none;
    }

    .footer-links li {
      margin-bottom: 12px;
    }

    .footer-links a {
      color: #aaa;
      text-decoration: none;
      transition: 0.2s;
    }

    .footer-links a:hover {
      color: var(--primary);
      padding-left: 5px;
    }

    .social-icons {
      display: flex;
      gap: 16px;
      margin-top: 20px;
    }

    .social-icons a {
      width: 38px;
      height: 38px;
      background: rgba(255,255,255,0.08);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: white;
      transition: 0.2s;
    }

    .social-icons a:hover {
      background: var(--primary);
      transform: translateY(-3px);
    }

    .copyright {
      text-align: center;
      padding: 28px 20px 0;
      color: #7a7a8a;
      font-size: 0.85rem;
      margin-top: 40px;
      border-top: 1px solid rgba(255,255,255,0.05);
    }

    /* Scroll top */
    .scroll-top {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 50px;
      height: 50px;
      background: var(--primary);
      border-radius: 50%;
      display: none;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      z-index: 99;
      box-shadow: 0 5px 20px rgba(255,76,96,0.5);
      transition: all 0.2s;
    }

    .scroll-top:hover {
      transform: scale(1.08);
      background: var(--primary-dark);
    }

    @media (max-width: 768px) {
      .video-text h1 { font-size: 2.8rem; }
      .tagline { font-size: 1rem; }
      .tab-item { padding: 14px 20px; font-size: 0.85rem; }
      .desc-text { max-width: 100%; }
      .section-title { font-size: 1.8rem; }
      .tab-content { padding: 32px 20px; }
      .detail-right { margin-top: 20px; }
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/../components/Navbar.php'; ?>

<!-- Hero Section -->
<section class="fullscreen-video" id="introBanner">
  <img class="hero-backdrop" src="https://images.unsplash.com/photo-1506703719100-f0b3c5c4c5b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="Celestial sky anime landscape" loading="eager">
  <div class="video-overlay"></div>
  <div class="rating-badge">
    <i class="fas fa-star"></i> 8.4/10 <i class="fas fa-imdb" style="margin-left: 6px;"></i>
  </div>
  <div class="video-text">
    <h1>Your Name</h1>
    <div class="tagline"><i class="fas fa-feather-alt"></i> Dreams connect. Fate rewrites.</div>
    <div class="movie-meta">
      <span class="meta-item"><i class="fas fa-calendar-alt"></i> 2016</span>
      <span class="meta-item"><i class="fas fa-clock"></i> 1h 52m</span>
      <span class="meta-item"><i class="fas fa-heart"></i> Romance / Drama</span>
      <span class="meta-item"><i class="fas fa-trophy"></i> Award Winner</span>
    </div>
    <div class="btn-group">
      <button class="btn-watch" id="watchTrailerBtn"><i class="fas fa-play"></i> Watch Trailer</button>
      <a href="manga.php" class="btn-watch btn-outline" id="mangaExploreBtn"><i class="fas fa-book-open"></i> Manga Edition</a>
    </div>
  </div>
</section>

<!-- Video Player Overlay -->
<div class="video-screen" id="watchScreen">
  <div class="video-container">
    <div class="video-controls-bar">
      <h3><i class="fas fa-film"></i> Your Name · Official Trailer</h3>
      <button class="close-video" id="closeVideoBtn"><i class="fas fa-times"></i> Close</button>
    </div>
    <video id="trailerVideo" controls preload="metadata">
      <source src="https://res.cloudinary.com/djfx1zxkv/video/upload/v1773906634/your-name_ixam43.mp4" type="video/mp4">
      Your browser does not support the video tag.
    </video>
  </div>
</div>

<!-- Tabs Navigation -->
<nav class="tab-menu">
  <ul>
    <li class="tab-item active" data-tab="overview"><i class="fas fa-scroll"></i> Overview</li>
    <li class="tab-item" data-tab="related"><i class="fas fa-star-of-life"></i> Related</li>
    <li class="tab-item" data-tab="details"><i class="fas fa-circle-info"></i> Details</li>
  </ul>
</nav>

<!-- Tab: Overview -->
<section class="tab-content" id="overview">
  <h2 class="section-title"><i class="fas fa-film"></i> Story Overview</h2>
  <p class="desc-text">Mitsuha, a high school girl in rural Itomori, yearns for a life in bustling Tokyo. Taki, a boy living in Tokyo, juggles part-time work and dreams of architecture. When they suddenly begin swapping bodies, they leave messages and slowly unravel a bond that transcends time. But as a mystical comet draws near, they discover a tragedy that threatens to erase their connection forever. <strong>Your Name (Kimi no Na wa)</strong> is a breathtaking journey of fate, memory, and the red thread of destiny.</p>
  <p class="desc-text">Makoto Shinkai’s masterpiece broke box-office records worldwide, becoming a cultural phenomenon with its stunning animation, emotional depth, and unforgettable soundtrack by RADWIMPS.</p>
</section>

<!-- Tab: Related Anime -->
<section class="tab-content" id="related">
  <h2 class="section-title"><i class="fas fa-compass"></i> You Might Also Like</h2>
  <div class="related-grid">
    <div class="related-card"><strong>🌦️ Weathering with You</strong><p>From the same director — a runaway boy meets a girl who can control the weather in rain-soaked Tokyo.</p></div>
    <div class="related-card"><strong>🌸 5 Centimeters per Second</strong><p>A poetic exploration of distance and longing across years, another Shinkai classic.</p></div>
    <div class="related-card"><strong>☔ The Garden of Words</strong><p>A quiet, intimate story of two souls meeting on rainy mornings, steeped in visual poetry.</p></div>
    <div class="related-card"><strong>🤝 A Silent Voice</strong><p>A powerful redemption drama about bullying, forgiveness, and finding connection.</p></div>
    <div class="related-card"><strong>🍃 Suzume</strong><p>Makoto Shinkai's journey across Japan to close mystical doors — emotional and epic.</p></div>
  </div>
</section>

<!-- Tab: Info -->
<section class="tab-content" id="details">
  <h2 class="section-title"><i class="fas fa-database"></i> Detailed Information</h2>
  <div class="detail-grid">
    <div class="detail-left">
      <p style="font-weight: 500; margin-bottom: 16px;">Mitsuha and Taki's story weaves through time, identity, and the subtle threads that bind us. Winner of the Japan Academy Prize for Animation of the Year, it remains one of the highest-grossing anime films globally.</p>
      <p>Featuring the voices of Ryunosuke Kamiki, Mone Kamishiraishi, and music by RADWIMPS — a cultural touchstone that redefined modern anime cinema.</p>
    </div>
    <div class="detail-right">
      <ul class="info-list">
        <li><strong>Original Title:</strong> <span>君の名は。 (Kimi no Na wa.)</span></li>
        <li><strong>Director:</strong> <span><a href="https://en.wikipedia.org/wiki/Makoto_Shinkai" target="_blank" class="external-link">Makoto Shinkai <i class="fas fa-external-link-alt"></i></a></span></li>
        <li><strong>Studio:</strong> <span><a href="https://www.cwfilms.jp/en/" target="_blank" class="external-link">CoMix Wave Films</a></span></li>
        <li><strong>Genre:</strong> <span>Romance, Supernatural, Drama</span></li>
        <li><strong>Release:</strong> <span>August 26, 2016 (Japan)</span></li>
        <li><strong>Runtime:</strong> <span>112 minutes</span></li>
        <li><strong>Box Office:</strong> <span>$382M+ (Worldwide)</span></li>
        <li><strong>Rating:</strong> <span>⭐ 8.4 IMDb | 98% Rotten Tomatoes</span></li>
      </ul>
    </div>
  </div>
</section>

<footer>
  <div class="footer-grid">
    <div class="footer-section">
      <h3><i class="fas fa-tv"></i> AckerStream</h3>
      <p style="color:#aaa;">Stream the finest anime films & series. Your gateway to emotional storytelling.</p>
      <div class="social-icons">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-discord"></i></a>
      </div>
    </div>
    <div class="footer-section"><h3>Explore</h3><ul class="footer-links"><li><a href="ash.php">Home</a></li><li><a href="anime_hub.php">Movies</a></li><li><a href="manga.php">Manga</a></li></ul></div>
    <div class="footer-section"><h3>Support</h3><ul class="footer-links"><li><a href="#">FAQ</a></li><li><a href="#">Privacy</a></li><li><a href="#">Contact</a></li></ul></div>
  </div>
  <div class="copyright">
    <p>© 2026 AckerStream — Fan tribute. "Your Name" & © CoMix Wave Films. Educational showcase.</p>
  </div>
</footer>

<div class="scroll-top" id="scrollTopBtn"><i class="fas fa-arrow-up"></i></div>

<script>
  (function() {
    const watchScreen = document.getElementById('watchScreen');
    const watchBtn = document.getElementById('watchTrailerBtn');
    const closeVideoBtn = document.getElementById('closeVideoBtn');
    const trailerVideo = document.getElementById('trailerVideo');
    const scrollBtn = document.getElementById('scrollTopBtn');
    const introBanner = document.getElementById('introBanner');

    function openTrailer() {
      if (introBanner) introBanner.style.display = 'none';
      watchScreen.style.display = 'flex';
      document.body.style.overflow = 'hidden';
      trailerVideo.play().catch(e => console.log("Autoplay blocked"));
    }

    function closeVideoPlayer() {
      watchScreen.style.display = 'none';
      if (introBanner) introBanner.style.display = 'flex';
      document.body.style.overflow = 'auto';
      trailerVideo.pause();
      trailerVideo.currentTime = 0;
    }

    if (watchBtn) watchBtn.addEventListener('click', openTrailer);
    if (closeVideoBtn) closeVideoBtn.addEventListener('click', closeVideoPlayer);
    watchScreen.addEventListener('click', (e) => { if (e.target === watchScreen) closeVideoPlayer(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && watchScreen.style.display === 'flex') closeVideoPlayer(); });

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
            contents[target].style.display = 'block';
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelector('.tab-menu').scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    window.addEventListener('scroll', () => {
        scrollBtn.style.display = window.scrollY > 400 ? 'flex' : 'none';
    });
    scrollBtn.addEventListener('click', () => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  })();
</script>
</body>
</html>
