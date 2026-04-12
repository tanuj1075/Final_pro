<?php
$displayName = $_SESSION['username'] ?? $_SESSION['admin_username'] ?? 'Guest';
?>
<!-- Enhanced Navigation Bar -->
<nav class="navbar" role="navigation" aria-label="Main navigation">
  <div class="nav-container">
    <!-- Logo Section -->
    <a href="/ash.php" class="logo-section" aria-label="AckerStream home">
      <img src="/src/assets/images/bird.svg" alt="AckerStream Logo" class="logo-img">
      <span class="logo-text">Ackerstream</span>
    </a>

    <!-- Center Navigation Menu -->
    <div class="nav-menu" id="primary-nav-menu">
      <ul class="nav-links">
        <li class="nav-link"><a href="/ash.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'ash.php' ? 'active' : ''; ?>">Home</a></li>
        <li class="nav-link"><a href="/ash.php?type=top">Top Anime</a></li>
        
        <!-- Categories Dropdown (Dynamic Links) -->
        <li class="nav-dropdown">
          <a href="#" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="false" data-dropdown-toggle="true">
            Genres <i class="fas fa-chevron-down"></i>
          </a>
          <div class="dropdown-menu">
            <a href="/anime_hub.php?genre=1" class="dropdown-item"><i class="fas fa-fist-raised"></i> Action</a>
            <a href="/anime_hub.php?genre=22" class="dropdown-item"><i class="fas fa-heart"></i> Romance</a>
            <a href="/anime_hub.php?genre=14" class="dropdown-item"><i class="fas fa-ghost"></i> Horror</a>
            <a href="/anime_hub.php?genre=10" class="dropdown-item"><i class="fas fa-magic"></i> Fantasy</a>
            <a href="/anime_hub.php?genre=24" class="dropdown-item"><i class="fas fa-robot"></i> Sci-Fi</a>
          </div>
        </li>
        
        <li class="nav-link"><a href="/anime_hub.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'anime_hub.php' ? 'active' : ''; ?>">Anime Hub</a></li>
        <li class="nav-link"><a href="/manga.php">Manga</a></li>
      </ul>
    </div>

    <!-- Right Navigation Actions -->
    <div class="nav-actions">
      <!-- Mobile Menu Toggle -->
      <button class="mobile-menu-toggle" aria-expanded="false" aria-controls="primary-nav-menu" aria-label="Open navigation menu" type="button">
        <i class="fas fa-bars"></i>
      </button>
      
      <!-- Premium Button -->
      <a href="/subscription" class="premium-btn" aria-label="Open subscription plans">
        <div class="premium-icon">
          <i class="fas fa-crown"></i>
        </div>
        <div class="premium-text">
          <span class="premium-try">BOOK NOW</span>
          <span class="premium-label">FESTIVAL PASS</span>
        </div>
      </a>
      
      <!-- Action Icons -->
      <div class="action-icons">
        <form action="/anime_hub.php" method="get" style="display:inline-flex; align-items:center; gap:6px;">
          <input
            type="search"
            name="q"
            placeholder="Search anime..."
            aria-label="Search anime"
            style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 6px 10px; border-radius: 999px; width: 160px;"
          >
          <button class="action-btn" aria-label="Search" type="submit" title="Search anime">
            <i class="fas fa-search"></i>
          </button>
        </form>
        <a href="#most-watched" class="action-btn" aria-label="Bookmarks" title="Jump to most watched">
          <i class="fas fa-bookmark"></i>
        </a>
        <button class="action-btn user-btn" aria-label="User Profile" type="button" title="User Profile">
          <i class="fas fa-user"></i>
        </button>
        <a href="?logout=1" class="action-btn" aria-label="Logout" title="Logout">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </div>
  </div>
</nav>
