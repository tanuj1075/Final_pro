<?php
require_once __DIR__ . '/../utils/security.php';
secure_session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="description" content="Stream the best anime online. Watch Demon Slayer, Jujutsu Kaisen, and more in high quality.">
  <title>Demon Slayer · Kimetsu no Yaiba | AckerStream</title>
  <!-- Font Awesome & Google Fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/src/styles/AT.css">
  <script type="module" src="https://cdn.jsdelivr.net/npm/player.style/sutro/+esm"></script>
  <style>
    :root {
      --primary: #e50914;
      --primary-dark: #b81b24;
      --secondary: #6c63ff;
      --dark-bg: #0a0a0f;
      --dark-card: #14141c;
      --text-light: #ffffff;
      --text-muted: #b3b3b3;
      --transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--dark-bg);
      color: var(--text-light);
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    .hero-section {
      position: relative;
      height: 100vh;
      min-height: 680px;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 1;
      filter: brightness(0.45) contrast(1.1);
      animation: slowZoom 28s infinite alternate ease-in-out;
    }

    @keyframes slowZoom {
      0% { transform: scale(1); }
      100% { transform: scale(1.08); }
    }

    .hero-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.8) 100%);
      z-index: 2;
    }

    .hero-content {
      position: relative;
      z-index: 5;
      text-align: center;
      max-width: 880px;
      padding: 20px;
      animation: fadeUp 0.9s ease-out;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(35px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .hero-title {
      font-size: 4.5rem;
      font-weight: 800;
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: linear-gradient(to right, #fff, #ff9a8b, var(--primary));
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 18px;
    }

    .hero-desc {
      font-size: 1.2rem;
      color: #e0e0e0;
      margin-bottom: 24px;
    }

    .btn-group {
      display: flex;
      justify-content: center;
      gap: 20px;
    }

    .btn-play {
      background: linear-gradient(100deg, var(--primary), #ff3b4a);
      border: none;
      padding: 14px 40px;
      border-radius: 50px;
      color: white;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 12px;
      box-shadow: 0 12px 28px rgba(229, 9, 20, 0.4);
    }

    .video-modal {
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
      background: #0a0a0a;
      border-radius: 28px;
      overflow: hidden;
    }

    .video-header {
      background: #0f0f12;
      padding: 16px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #2a2a2f;
    }

    .close-video {
      background: var(--primary);
      border: none;
      padding: 8px 24px;
      border-radius: 40px;
      color: white;
      cursor: pointer;
    }

    .tab-menu {
      background: var(--dark-card);
      border-bottom: 1px solid rgba(255,255,255,0.08);
      position: sticky;
      top: 70px;
      z-index: 99;
    }

    .tab-container {
      max-width: 1300px;
      margin: 0 auto;
      display: flex;
      justify-content: center;
    }

    .tab-btn {
      padding: 18px 36px;
      color: var(--text-muted);
      cursor: pointer;
      background: transparent;
      border: none;
      border-bottom: 3px solid transparent;
      font-family: inherit;
    }

    .tab-btn.active {
      color: var(--primary);
      border-bottom: 3px solid var(--primary);
    }

    .tab-panel {
      max-width: 1100px;
      margin: 0 auto;
      padding: 50px 20px;
      display: none;
    }

    .section-head { font-size: 2rem; margin-bottom: 20px; border-left: 5px solid var(--primary); padding-left: 15px; }

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
      box-shadow: 0 5px 20px rgba(229,9,20,0.5);
    }
  </style>
</head>
<body>

<?php include __DIR__ . '/../components/Navbar.php'; ?>

<section class="hero-section" id="heroSection">
  <img class="hero-bg" src="https://images.pexels.com/photos/1282727/pexels-photo-1282727.jpeg?auto=compress&cs=tinysrgb&w=1600" alt="Demon Slayer background">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1 class="hero-title">Demon Slayer: Kimetsu no Yaiba</h1>
    <p class="hero-desc">A boy becomes a demon slayer after his family is slaughtered, seeking a cure for his demon-turned sister.</p>
    <div class="btn-group">
      <button class="btn-play" id="playHeroBtn"><i class="fas fa-play"></i> Watch Now</button>
    </div>
  </div>
</section>

<div class="video-modal" id="videoModal">
  <div class="video-container">
    <div class="video-header">
      <h3><i class="fas fa-film"></i> Demon Slayer - Episode 1</h3>
      <button class="close-video" id="closeVideoBtn"><i class="fas fa-times"></i> Close</button>
    </div>
    <media-theme-sutro id="sutroPlayer" style="width:100%">
      <video
        slot="media"
        src="https://stream.mux.com/fXNzVtmtWuyz00xnSrJg4OJH6PyNo6D02UzmgeKGkP5YQ/low.mp4"
        playsinline
        crossorigin="anonymous"
      ></video>
    </media-theme-sutro>
  </div>
</div>

<div class="tab-menu">
  <div class="tab-container">
    <button class="tab-btn active" data-tab="overview"><i class="fas fa-scroll"></i> Overview</button>
    <button class="tab-btn" data-tab="details"><i class="fas fa-info-circle"></i> Info</button>
  </div>
</div>

<div class="tab-panel" id="overview-panel" style="display:block;">
  <h2 class="section-head">Synopsis</h2>
  <p>Tanjiro Kamado, a kind-hearted boy who sells charcoal for a living, finds his family slaughtered by a demon. To make matters worse, his younger sister Nezuko, the sole survivor, has been transformed into a demon herself. Though devastated by this grim reality, Tanjiro resolves to become a “demon slayer” so that he can turn his sister back into a human, and kill the demon that massacred his family.</p>
</div>

<div class="tab-panel" id="details-panel">
    <h2 class="section-head">Details</h2>
    <p>Original Creator: Koyoharu Gotouge</p>
    <p>Studio: Ufotable</p>
    <p>Aired: 2019 - Present</p>
</div>

<footer>
    <div style="text-align:center; padding: 40px; background: #050507;">
        <p>© 2026 AckerStream — Demon Slayer Edition</p>
    </div>
</footer>

<div class="scroll-top" id="scrollTopBtn"><i class="fas fa-arrow-up"></i></div>

<script>
    (function() {
        const videoModal = document.getElementById('videoModal');
        const playBtn = document.getElementById('playHeroBtn');
        const closeBtn = document.getElementById('closeVideoBtn');
        const heroSection = document.getElementById('heroSection');
        const sutroPlayer = document.getElementById('sutroPlayer');

        playBtn.addEventListener('click', () => {
            heroSection.style.display = 'none';
            videoModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            const v = document.querySelector('#sutroPlayer video');
            if(v) v.play();
        });

        closeBtn.addEventListener('click', () => {
            videoModal.style.display = 'none';
            heroSection.style.display = 'flex';
            document.body.style.overflow = 'auto';
            const v = document.querySelector('#sutroPlayer video');
            if(v) v.pause();
        });

        const tabs = document.querySelectorAll('.tab-btn');
        const panels = {
            overview: document.getElementById('overview-panel'),
            details: document.getElementById('details-panel')
        };

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.getAttribute('data-tab');
                Object.values(panels).forEach(p => p.style.display = 'none');
                panels[target].style.display = 'block';
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
            });
        });

        const scrollBtn = document.getElementById('scrollTopBtn');
        window.addEventListener('scroll', () => {
            scrollBtn.style.display = window.scrollY > 400 ? 'flex' : 'none';
        });
        scrollBtn.addEventListener('click', () => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    })();
</script>
</body>
</html>
