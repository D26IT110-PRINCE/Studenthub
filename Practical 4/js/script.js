/* Practical 4: theme persistence, notification controls, FAQ behavior, and homepage slider. */
document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;

  // Restore the saved preference before visitors interact with the page.
  if (localStorage.getItem('studenthub-theme') === 'dark') {
    body.classList.add('dark-theme');
  }

  // Theme buttons appear throughout the portal and save their selection in localStorage.
  const updateThemeButtons = () => {
    document.querySelectorAll('.theme-toggle').forEach((button) => {
      const dark = body.classList.contains('dark-theme');
      button.textContent = dark ? 'Use Light Theme' : 'Change Theme';
      button.setAttribute('aria-pressed', String(dark));
    });
  };

  updateThemeButtons();

  document.querySelectorAll('.theme-toggle').forEach((button) => {
    button.addEventListener('click', () => {
      body.classList.toggle('dark-theme');
      const isDark = body.classList.contains('dark-theme');
      localStorage.setItem('studenthub-theme', isDark ? 'dark' : 'light');
      updateThemeButtons();
    });
  });

  // Change Heading toggles one emoji suffix: add it on one click, remove it on the next.
  document.querySelectorAll('.heading-button').forEach((button) => {
    button.addEventListener('click', () => {
      const heading = button.closest('.live-notification')?.querySelector('h2');
      if (!heading) {
        return;
      }
      if (heading.textContent.includes('🎉')) {
        heading.textContent = heading.textContent.replace(/\s*🎉/g, '');
      } else {
        heading.textContent = `${heading.textContent} 🎉`;
      }
    });
  });

  // The close button affects only its own notification banner.
  document.querySelectorAll('.notification-close').forEach((button) => {
    button.addEventListener('click', () => {
      const notification = button.closest('.live-notification');
      if (notification) {
        notification.hidden = true;
      }
    });
  });

  // Native details/summary remains keyboard friendly; this adds one-open-item behavior.
  document.querySelectorAll('.faq-item').forEach((item) => {
    item.addEventListener('toggle', () => {
      if (item.open) {
        document.querySelectorAll('.faq-item[open]').forEach((other) => {
          if (other !== item) {
            other.open = false;
          }
        });
      }
    });
  });

  // Future slider images can be added by placing another object in this array.
  const slides = [
    [
      'assets/studenthub-admin-building.png',
      'StudentHub administration building on a sunny campus day',
      'The StudentHub administration building—your campus connection point.'
    ],
    [
      'assets/studenthub-innovation-lab.png',
      'Students collaborating in a modern innovation laboratory',
      'Student innovators collaborate, experiment, and build together.'
    ],
    [
      'assets/studenthub-cultural-event.png',
      'Students enjoying a cultural event in the campus courtyard',
      'Campus events create connections beyond the classroom.'
    ]
  ];

  const image = document.getElementById('slider-image');
  const caption = document.getElementById('slider-caption');
  const status = document.getElementById('slider-status');
  let currentSlide = 0;

  // Render the selected image and announce its new position.
  const showSlide = (index) => {
    if (!image || !caption || !status) {
      return;
    }
    currentSlide = (index + slides.length) % slides.length;
    const [src, alt, text] = slides[currentSlide];
    image.src = src;
    image.alt = alt;
    caption.textContent = text;
    status.textContent = `Image ${currentSlide + 1} of ${slides.length}`;
  };

  document.querySelectorAll('[data-slider-action]').forEach((button) => {
    button.addEventListener('click', () => {
      const direction = button.dataset.sliderAction === 'next' ? 1 : -1;
      showSlide(currentSlide + direction);
    });
  });

  // Hamburger menu toggle for Practical 4
  const navToggle = document.querySelector('.nav-toggle');
  const siteNav = document.getElementById('site-nav');

  if (navToggle && siteNav) {
    navToggle.addEventListener('click', () => {
      const open = siteNav.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(open));
      navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && siteNav.classList.contains('is-open')) {
        siteNav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Open menu');
        navToggle.focus();
      }
    });

    document.addEventListener('click', (e) => {
      const isOpen = siteNav.classList.contains('is-open');
      const clickedOutsideNav = !siteNav.contains(e.target);
      const clickedOutsideToggle = !navToggle.contains(e.target);

      if (isOpen && clickedOutsideNav && clickedOutsideToggle) {
        siteNav.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Open menu');
      }
    });
  }
});