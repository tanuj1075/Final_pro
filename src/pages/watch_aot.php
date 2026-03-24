<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Attack on Titan - Watch Now | AckerStream</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Watch Attack on Titan - Children inherit a world built on lies, and must decide whether freedom is worth becoming the monster history needs.">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/AT.css">
  <style>
    :root {
      --primary: #ff4c60;
      --primary-dark: #ff334a;
      --secondary: #6c63ff;
      --dark: #0d0d0d;
      --dark-light: #1a1a1a;
      --light: #ffffff;
      --gray: #b3b3b3;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      --transition: all 0.3s ease;
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
    
    /* Hero Section */
    .fullscreen-video {
      position: relative;
      height: 100vh;
      min-height: 700px;
      width: 100%;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 0;
    }
    
    .fullscreen-video img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 1;
      filter: brightness(0.5);
      animation: zoomEffect 30s infinite alternate ease-in-out;
    }
    
    @keyframes zoomEffect {
      0% { transform: scale(1); }
      100% { transform: scale(1.1); }
    }
    
    .video-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to bottom, 
        rgba(13, 13, 13, 0.8) 0%, 
        rgba(13, 13, 13, 0.4) 50%,
        rgba(13, 13, 13, 0.8) 100%);
      z-index: 2;
    }
    
    .video-text {
      position: relative;
      z-index: 3;
      text-align: center;
      max-width: 900px;
      padding: 30px;
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
      font-size: 1.3rem;
      margin-bottom: 30px;
      color: var(--gray);
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .movie-meta {
      display: flex;
      justify-content: center;
      gap: 20px;
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
    
    .video-screen video {
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
  </style>
</head>
<body>

<?php include __DIR__ . '/../components/Navbar.php'; ?>

<!-- Landing Section -->
<section class="fullscreen-video" id="introBanner">
  <div class="video-overlay"></div>
  <img src="https://images.unsplash.com/photo-1528164344705-47542687000d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2069&q=80" alt="Attack on Titan Banner">
  <div class="rating-badge">
    <i class="fas fa-star"></i> 8.4/10
  </div>
  <div class="video-text">
    <h1>Attack on Titan</h1>
    <p>Children inherit a world built on lies, and must decide whether freedom is worth becoming the monster history needs</p>
    
    <div class="movie-meta">
      <span class="meta-item"><i class="fas fa-calendar"></i> 2013–2023</span>
      <span class="meta-item"><i class="fas fa-clock"></i> 4 Seasons</span>
      <span class="meta-item"><i class="fas fa-tag"></i> Action, Dark Fantasy</span>
      <span class="meta-item"><i class="fas fa-film"></i> TV Series</span>
    </div>
    
    <div class="btn-group">
      <button class="btn-watch" id="watchOverviewBtn" type="button">
        <i class="fas fa-play"></i> Watch Overview
      </button>
      <a class="btn-watch" href="manga.php" style="color: inherit; text-decoration: none;">
        <i class="fas fa-star"></i> Manga
      </a>
    </div>
  </div>
</section>

<!-- Video Player Section -->
<section class="video-screen" id="watchScreen">
  <div class="video-controls">
    <button class="close-video" id="closeVideoBtn">
      <i class="fas fa-times"></i> Close Video
    </button>
    <h3>Attack on Titan - Official Trailer</h3>
  </div>
  <video id="aotTrailer" controls>
    <source src="../assets/videos/your-name.mp4" type="video/mp4" />
    Your browser does not support the video tag.
  </video>
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
  <p>In a world where humanity live inside cities surrounded by enormous walls due to the Titans, gigantic humanoid beings who eat humans seemingly without reason, the story follows Eren Yeager and his friends who join the military to fight the Titans after their hometown is invaded.</p>
  <p>Attack on Titan has become a global phenomenon, known for its complex plot, moral ambiguity, and intense action sequences. Created by Hajime Isayama, it has redefined the dark fantasy genre in anime.</p>
</section>

<section class="tab-content" id="related">
    <h2><i class="fas fa-link"></i> Related Series</h2>
    <p>If you enjoyed the dark themes and epic scale of Attack on Titan, check out these series:</p>
    <ul>
        <li><strong>Vinland Saga</strong> - An epic historical drama.</li>
        <li><strong>Fullmetal Alchemist: Brotherhood</strong> - A deep story of sacrifice and truth.</li>
        <li><strong>Code Geass</strong> - Rebellion and tactical genius.</li>
    </ul>
</section>

<section class="tab-content" id="details">
    <h2><i class="fas fa-database"></i> Series Info</h2>
    <p>Original Manga: Hajime Isayama</p>
    <p>Studio: Wit Studio (S1-3), MAPPA (S4)</p>
    <p>Genre: Action, Suspense, Dark Fantasy</p>
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
            <li><a href="ash.php">Home</a></li>
            <li><a href="anime_hub.php">Movies</a></li>
            <li><a href="manga.php">Manga</a></li>
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
            trailerVideo.play().catch(e => console.log("Autoplay blocked"));
        });

        closeVideoBtn.addEventListener('click', () => {
            watchScreen.style.display = 'none';
            introBanner.style.display = 'flex';
            document.body.style.overflow = 'auto';
            trailerVideo.pause();
            trailerVideo.currentTime = 0;
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
    })();
</script>
</body>
</html>
