document.addEventListener('DOMContentLoaded', () => {
  const navMenu = document.querySelector('.nav-menu');
  const mobileMenuBtn = document.querySelector('.mobile-menu-toggle');

  if (mobileMenuBtn && navMenu) {
    mobileMenuBtn.addEventListener('click', () => {
      navMenu.classList.toggle('active');
      mobileMenuBtn.innerHTML = navMenu.classList.contains('active')
        ? '<i class="fas fa-times"></i>'
        : '<i class="fas fa-bars"></i>';
    });

    document.addEventListener('click', (event) => {
      if (!event.target.closest('.nav-container') && navMenu.classList.contains('active')) {
        navMenu.classList.remove('active');
        mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
      }
    });
  }

  const sectionMap = {
    New: '#animeCarousel',
    Popular: '#most-watched',
    Simulcast: '#anime-journey',
    Manga: 'manga.html'
  };

  document.querySelectorAll('.nav-link a').forEach((link) => {
    link.addEventListener('click', () => {
      document.querySelectorAll('.nav-link a').forEach((l) => l.classList.remove('active'));
      link.classList.add('active');
    });

    const label = link.textContent.trim();
    if (sectionMap[label]) {
      link.setAttribute('href', sectionMap[label]);
    }
  });

  const searchBtn = document.querySelector('.action-btn[aria-label="Search"]');
  if (searchBtn) {
    searchBtn.addEventListener('click', () => {
      let searchInput = document.querySelector('.search-input');
      if (searchInput) {
        searchInput.focus();
        return;
      }

      searchInput = document.createElement('input');
      searchInput.type = 'text';
      searchInput.className = 'search-input';
      searchInput.placeholder = 'Search anime by title...';
      searchInput.style.position = 'absolute';
      searchInput.style.top = '55px';
      searchInput.style.right = '0';
      searchInput.style.width = '280px';
      searchInput.style.maxWidth = '80vw';
      searchInput.style.padding = '10px 14px';
      searchInput.style.borderRadius = '999px';
      searchInput.style.border = '2px solid rgba(255,255,255,0.5)';
      searchInput.style.outline = 'none';
      searchInput.style.background = '#ffffff';
      searchInput.style.zIndex = '1001';

      const actions = searchBtn.closest('.action-icons') || searchBtn.parentElement;
      actions.style.position = 'relative';
      actions.appendChild(searchInput);
      searchInput.focus();

      searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        document.querySelectorAll('.anime-card').forEach((card) => {
          const title = card.querySelector('.anime-title')?.textContent.toLowerCase() || '';
          card.style.display = title.includes(query) ? '' : 'none';
        });
      });

      searchInput.addEventListener('blur', () => {
        setTimeout(() => {
          if (searchInput) {
            searchInput.remove();
            document.querySelectorAll('.anime-card').forEach((card) => {
              card.style.display = '';
            });
          }
        }, 150);
      });
    });
  }

  document.querySelectorAll('.quick-play').forEach((button) => {
    button.setAttribute('type', 'button');
    button.addEventListener('click', (event) => {
      event.preventDefault();
      const anchor = button.closest('a');
      if (anchor && anchor.getAttribute('href')) {
        window.open(anchor.getAttribute('href'), anchor.getAttribute('target') || '_self');
      } else {
        const animeTitle = button.closest('.anime-card')?.querySelector('.anime-title')?.textContent?.trim() || 'This title';
        showNotification(`${animeTitle}: episode page coming soon.`);
      }
    });
  });

  const carouselWatchTargets = ['w.html', 'video.html', 'w.html', 'video.html', 'video.html'];
  const carouselWatchTargets = ['watch1.html', 'watch2.html', 'watch1.html', 'watch2.html', 'video.html'];
  document.querySelectorAll('.watch-btn').forEach((button) => {
    button.setAttribute('type', 'button');
    button.addEventListener('click', () => {
      const currentItem = button.closest('.carousel-item');
      if (!currentItem) {
        showNotification('Watch page unavailable right now.');
        return;
      }

      const allItems = Array.from(document.querySelectorAll('#animeCarousel .carousel-item'));
      const index = allItems.indexOf(currentItem);
      const destination = carouselWatchTargets[index] || 'video.html';
      window.location.href = destination;
    });
  });

  const profileBtn = document.querySelector('.action-btn.user-btn');
  if (profileBtn) {
    profileBtn.addEventListener('click', () => {
      showNotification('Profile center will be available in the next update.');
    });
  }

  document.querySelectorAll('.bookmark-btn').forEach((button) => {
    button.setAttribute('type', 'button');
    button.addEventListener('click', () => {
      button.classList.toggle('is-active');
      const icon = button.querySelector('i');
      if (icon) {
        icon.classList.toggle('fas');
        icon.classList.toggle('far');
      }
      showNotification(button.classList.contains('is-active') ? 'Added to bookmarks.' : 'Removed from bookmarks.');
    });
  });

  document.querySelectorAll('.info-btn').forEach((button) => {
    button.setAttribute('type', 'button');
    button.addEventListener('click', () => {
      const card = button.closest('.carousel-content');
      const title = card?.querySelector('.anime-logo')?.getAttribute('alt') || 'This anime';
      showNotification(`${title}: details will be available soon.`);
    });
  });

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('in-view');
      }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.anime-card').forEach((el) => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(16px)';
    el.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
    observer.observe(el);
  });

  const style = document.createElement('style');
  style.textContent = '.anime-card.in-view{opacity:1!important;transform:translateY(0)!important;}';
  document.head.appendChild(style);

  function showNotification(message) {
    const note = document.createElement('div');
    note.textContent = message;
    note.style.position = 'fixed';
    note.style.right = '16px';
    note.style.top = '16px';
    note.style.padding = '10px 14px';
    note.style.borderRadius = '8px';
    note.style.background = '#1f2937';
    note.style.color = '#fff';
    note.style.zIndex = '9999';
    note.style.boxShadow = '0 8px 20px rgba(0,0,0,0.25)';
    document.body.appendChild(note);

    setTimeout(() => {
      note.style.opacity = '0';
      note.style.transition = 'opacity 0.25s ease';
      setTimeout(() => note.remove(), 250);
    }, 1800);
  }
});

function scrollSlider(direction) {
  const slider = document.getElementById('cardSlider');
  if (!slider) return;
  const amount = Math.max(300, Math.floor(slider.clientWidth * 0.8));
  slider.scrollBy({ left: direction * amount, behavior: 'smooth' });
}

function scrollSlider2(direction) {
  const slider = document.getElementById('cardSlider2');
  if (!slider) return;
  const amount = Math.max(300, Math.floor(slider.clientWidth * 0.8));
  slider.scrollBy({ left: direction * amount, behavior: 'smooth' });
}
