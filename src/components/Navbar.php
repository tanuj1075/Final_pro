<?php
$displayName = $_SESSION['username'] ?? $_SESSION['admin_username'] ?? 'Guest';
?>
<!-- Enhanced Navigation Bar -->
<nav class="navbar" role="navigation" aria-label="Main navigation">
  <div class="nav-container">
    <!-- Logo Section -->
    <a href="ash.php" class="logo-section" aria-label="AckerStream home">
      <img src="../assets/images/bird.svg" alt="AckerStream Logo" class="logo-img">
      <span class="logo-text">Ackerstream</span>
    </a>

    <!-- Center Navigation Menu -->
    <div class="nav-menu" id="primary-nav-menu">
      <ul class="nav-links">
        <li class="nav-link"><a href="#most-watched" class="active">Stages</a></li>
        <li class="nav-link"><a href="#most-watched">Schedule</a></li>
        <li class="nav-link"><a href="#anime-journey">Installations</a></li>
        
        <!-- Categories Dropdown -->
        <li class="nav-dropdown">
          <a href="#" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="false" data-dropdown-toggle="true">
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
        <li class="nav-link"><a href="subscription.html">Subscription</a></li>
        <li class="nav-link"><a href="anime_hub.php">Anime Hub</a></li>
        
        <!-- News Dropdown -->
        <li class="nav-dropdown">
          <a href="#" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="false" data-dropdown-toggle="true">
            News <i class="fas fa-chevron-down"></i>
          </a>
          <div class="dropdown-menu">
            <a href="video.html" class="dropdown-item"><i class="fas fa-newspaper"></i> All News</a>
            <a href="video.html" class="dropdown-item"><i class="fas fa-trophy"></i> Anime Awards</a>
            <a href="watch1.html" class="dropdown-item"><i class="fas fa-calendar-alt"></i> Events & Experiences</a>
          </div>
        </li>
      </ul>
    </div>

    <!-- Right Navigation Actions -->
    <div class="nav-actions">
      <!-- Mobile Menu Toggle -->
      <button class="mobile-menu-toggle" aria-expanded="false" aria-controls="primary-nav-menu" aria-label="Open navigation menu" type="button">
        <i class="fas fa-bars"></i>
      </button>
      
      <!-- Premium Button -->
      <a href="subscription.html" class="premium-btn" aria-label="Open subscription plans">
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
        <button class="action-btn" aria-label="Search" type="button">
          <i class="fas fa-search"></i>
        </button>
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
