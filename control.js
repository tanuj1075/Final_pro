// Simple interactivity for the cute website
document.addEventListener('DOMContentLoaded', function () {
  // Mobile menu toggle
  const mobileMenuBtn = document.querySelector('.mobile-menu-toggle');
  const navMenu = document.querySelector('.nav-menu');

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', () => {
      navMenu.classList.toggle('active');
      mobileMenuBtn.innerHTML = navMenu.classList.contains('active')
        ? '<i class="fas fa-times"></i>'
        : '<i class="fas fa-bars"></i>';
    });
  }

  // Close mobile menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-container') && navMenu.classList.contains('active')) {
      navMenu.classList.remove('active');
      mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    }
  });

  // Anime card hover effects
  const animeCards = document.querySelectorAll('.anime-card');
  animeCards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-15px)';
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0)';
    });
  });

  // Play button animations
  const playButtons = document.querySelectorAll('.watch-btn, .quick-play');
  playButtons.forEach(btn => {
    btn.addEventListener('mouseenter', (e) => {
      e.target.style.transform = 'scale(1.1) rotate(15deg)';
    });

    btn.addEventListener('mouseleave', (e) => {
      e.target.style.transform = 'scale(1) rotate(0)';
    });
  });

  // Category card animations
  const categoryCards = document.querySelectorAll('.category-card');
  categoryCards.forEach((card, index) => {
    card.style.setProperty('--i', index);

    card.addEventListener('mouseenter', () => {
      const icon = card.querySelector('.category-icon');
      icon.style.transform = 'scale(1.2) rotate(15deg)';
    });

    card.addEventListener('mouseleave', () => {
      const icon = card.querySelector('.category-icon');
      icon.style.transform = 'scale(1) rotate(0)';
    });
  });

  // Remind button functionality
  const remindButtons = document.querySelectorAll('.remind-btn');
  remindButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      const originalText = this.innerHTML;
      const animeTitle = this.closest('.coming-card').querySelector('h3').textContent;

      this.innerHTML = '<i class="fas fa-check"></i> Added!';
      this.style.background = '#a2ffd6';
      this.style.color = '#2d8b64';

      // Show cute notification
      showNotification(`Added "${animeTitle}" to reminders! ✨`);

      setTimeout(() => {
        this.innerHTML = originalText;
        this.style.background = '';
        this.style.color = '';
      }, 2000);
    });
  });

  // Heart button toggle
  const heartBtn = document.querySelector('.heart-btn');
  if (heartBtn) {
    heartBtn.addEventListener('click', function () {
      const icon = this.querySelector('i');
      const isActive = icon.classList.contains('fas');

      if (isActive) {
        icon.classList.remove('fas');
        icon.classList.add('far');
        this.style.background = '';
        this.style.color = '';
        showNotification('Removed from favorites 💔');
      } else {
        icon.classList.remove('far');
        icon.classList.add('fas');
        this.style.background = '#ff6b9d';
        this.style.color = 'white';
        this.style.borderColor = '#ff6b9d';
        showNotification('Added to favorites! ❤️');
      }
    });
  }

  // Search button
  const searchBtn = document.querySelector('.action-btn[aria-label="Search"]');
  if (searchBtn) {
    searchBtn.addEventListener('click', function () {
      const searchInput = document.createElement('input');
      searchInput.type = 'text';
      searchInput.placeholder = 'Search for cute anime...';
      searchInput.className = 'search-input';

      // Position it nicely
      this.parentNode.appendChild(searchInput);
      searchInput.style.position = 'absolute';
      searchInput.style.top = '50px';
      searchInput.style.right = '0';
      searchInput.style.padding = '15px';
      searchInput.style.borderRadius = '25px';
      searchInput.style.border = '2px solid #ffb6e1';
      searchInput.style.outline = 'none';
      searchInput.style.width = '300px';
      searchInput.style.boxShadow = '0 8px 25px rgba(255, 182, 225, 0.3)';

      searchInput.focus();

      // Remove on blur
      searchInput.addEventListener('blur', () => {
        setTimeout(() => searchInput.remove(), 200);
      });
    });
  }

  // Add newsletter subscription
  const subscribeBtn = document.querySelector('.subscribe-btn');
  const newsletterInput = document.querySelector('.newsletter input');

  if (subscribeBtn && newsletterInput) {
    subscribeBtn.addEventListener('click', function () {
      const email = newsletterInput.value;
      if (email && email.includes('@')) {
        this.innerHTML = '<i class="fas fa-check"></i>';
        this.style.background = '#a2ffd6';

        newsletterInput.value = '';
        newsletterInput.placeholder = 'Thank you! ✨';

        showNotification('You\'ll receive cute anime updates! 🌸');

        setTimeout(() => {
          this.innerHTML = '<i class="fas fa-paper-plane"></i>';
          this.style.background = '';
          newsletterInput.placeholder = 'Your email address';
        }, 3000);
      } else {
        newsletterInput.style.borderColor = '#ff6b9d';
        newsletterInput.placeholder = 'Please enter a valid email';

        setTimeout(() => {
          newsletterInput.style.borderColor = '';
          newsletterInput.placeholder = 'Your email address';
        }, 2000);
      }
    });
  }

  // Notification function
  function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'cute-notification';
    notification.innerHTML = `
      <div class="notification-content">
        <i class="fas fa-star"></i>
        <span>${message}</span>
      </div>
    `;

    // Add styles
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.background = 'white';
    notification.style.padding = '15px 25px';
    notification.style.borderRadius = '15px';
    notification.style.boxShadow = '0 8px 25px rgba(255, 182, 225, 0.3)';
    notification.style.borderLeft = '5px solid #ffb6e1';
    notification.style.zIndex = '9999';
    notification.style.animation = 'slideInRight 0.3s ease-out';

    document.body.appendChild(notification);

    // Remove after 3 seconds
    setTimeout(() => {
      notification.style.animation = 'slideOutRight 0.3s ease-out';
      setTimeout(() => notification.remove(), 300);
    }, 3000);
  }

  // Add CSS for notifications
  const style = document.createElement('style');
  style.textContent = `
    @keyframes slideInRight {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
      from { transform: translateX(0); opacity: 1; }
      to { transform: translateX(100%); opacity: 0; }
    }
    
    .notification-content {
      display: flex;
      align-items: center;
      gap: 12px;
      color: #6b4d7d;
      font-weight: 600;
    }
    
    .notification-content i {
      color: #ffb6e1;
    }
  `;
  document.head.appendChild(style);

  // Scroll animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, observerOptions);

  // Observe elements for animation
  document.querySelectorAll('.anime-card, .category-card, .coming-card').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    observer.observe(el);
  });

  // Add floating emojis animation
  const emojis = ['🌸', '🐱', '✨', '🌟', '❤️', '⭐', '🐾', '🎀'];
  setInterval(() => {
    if (Math.random() > 0.7) {
      createFloatingEmoji();
    }
  }, 3000);

  function createFloatingEmoji() {
    const emoji = document.createElement('div');
    emoji.textContent = emojis[Math.floor(Math.random() * emojis.length)];
    emoji.style.position = 'fixed';
    emoji.style.left = Math.random() * 100 + 'vw';
    emoji.style.top = '100vh';
    emoji.style.fontSize = Math.random() * 20 + 20 + 'px';
    emoji.style.opacity = '0.7';
    emoji.style.zIndex = '-1';
    emoji.style.pointerEvents = 'none';
    emoji.style.animation = `floatUp ${Math.random() * 3 + 2}s ease-in forwards`;

    document.body.appendChild(emoji);

    setTimeout(() => emoji.remove(), 5000);
  }

  // Add floating animation
  const floatStyle = document.createElement('style');
  floatStyle.textContent = `
    @keyframes floatUp {
      0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.7;
      }
      100% {
        transform: translateY(-100vh) rotate(360deg);
        opacity: 0;
      }
    }
  `;
  document.head.appendChild(floatStyle);
});