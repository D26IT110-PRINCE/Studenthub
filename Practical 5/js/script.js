/*
  Practical 5 - StudentHub registration validation
  This file validates user input on blur, while typing, and when the form is
  submitted. It never sends data to a server; successful submission is only a
  frontend demonstration for this practical.

  Also includes Practical 4 functionality: theme persistence, notification controls,
  FAQ behavior, homepage slider, and hamburger menu toggle.
*/

document.addEventListener('DOMContentLoaded', () => {
  /* ============================================================
     PRACTICAL 4: Theme persistence, notifications, FAQ, slider, hamburger
     ============================================================ */
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

  /* ============================================================
     PRACTICAL 5: Registration form validation
     ============================================================ */

  // Cache form controls once so validation functions can reuse them efficiently.
  const form = document.getElementById('registration-form');
  // Only initialize validation if the form exists on this page
  if (!form) {
    return;
  }

  const fields = {
    name: document.getElementById('name'),
    email: document.getElementById('email'),
    mobile: document.getElementById('mobile'),
    password: document.getElementById('password'),
    confirmPassword: document.getElementById('confirm-password'),
    course: document.getElementById('course'),
    year: document.getElementById('year'),
    terms: document.getElementById('terms')
  };
  const strengthBar = document.getElementById('strength-bar');
  const strengthText = document.getElementById('password-strength');
  const formStatus = document.getElementById('form-status');

  // Reusable regular expressions for the assignment's name, email, and mobile rules.
  const patterns = {
    name: /^[A-Za-z]+(?:[ '\-][A-Za-z]+)*$/,
    email: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,
    mobile: /^(?:\+91[\s-]?)?[6-9]\d{9}$/
  };

  // Places an accessible error message beside a field and marks it invalid.
  function showError(field, message) {
    const error = document.getElementById(`${field.id}-error`);
    if (error) {
      error.textContent = message;
    }
    field.setAttribute('aria-invalid', 'true');
    field.classList.add('is-invalid');
  }

  // Clears the field-level message after the control meets its validation rule.
  function clearError(field) {
    const error = document.getElementById(`${field.id}-error`);
    if (error) {
      error.textContent = '';
    }
    field.removeAttribute('aria-invalid');
    field.classList.remove('is-invalid');
  }

  // Calculates a 0-4 password score and returns its human-readable label.
  function getPasswordStrength(value) {
    let score = 0;
    if (value.length >= 8) {
      score++;
    }
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) {
      score++;
    }
    if (/\d/.test(value)) {
      score++;
    }
    if (/[^A-Za-z0-9]/.test(value)) {
      score++;
    }
    return {
      score,
      label: ['Very weak', 'Weak', 'Fair', 'Good', 'Strong'][score]
    };
  }

  // Updates the visual meter independently of whether the password is valid.
  function updateStrengthMeter() {
    const { score, label } = getPasswordStrength(fields.password.value);
    strengthBar.style.width = `${score * 25}%`;
    strengthBar.dataset.level = score;
    if (fields.password.value) {
      strengthText.textContent = `Password strength: ${label}`;
    } else {
      strengthText.textContent = 'Password strength: not set';
    }
  }

  // Validates one control and returns true only when its value is acceptable.
  function validateField(fieldName) {
    const field = fields[fieldName];
    const value = field.value.trim();

    if (fieldName === 'name') {
      if (value.length < 2 || !patterns.name.test(value)) {
        showError(field, 'Enter a valid full name using letters and spaces only.');
        return false;
      }
    } else if (fieldName === 'email') {
      if (!patterns.email.test(value)) {
        showError(field, 'Enter a valid email address, for example name@example.com.');
        return false;
      }
    } else if (fieldName === 'mobile') {
      if (!patterns.mobile.test(value)) {
        showError(field, 'Enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.');
        return false;
      }
    } else if (fieldName === 'password') {
      const strength = getPasswordStrength(value);
      if (strength.score < 4) {
        showError(field, 'Use at least 8 characters with upper- and lowercase letters, a number, and a symbol.');
        return false;
      }
    } else if (fieldName === 'confirmPassword') {
      if (!value || value !== fields.password.value) {
        showError(field, 'Passwords do not match.');
        return false;
      }
    } else if (fieldName === 'course' || fieldName === 'year') {
      if (!value) {
        showError(field, `Please select your ${fieldName}.`);
        return false;
      }
    } else if (fieldName === 'terms') {
      if (!field.checked) {
        showError(field, 'You must accept the terms and conditions.');
        return false;
      }
    }
    clearError(field);
    return true;
  }

  // Gender is a radio group, so it uses one group message instead of an input ID.
  function validateGender() {
    const selected = form.querySelector('input[name="gender"]:checked');
    const error = document.getElementById('gender-error');
    if (!selected) {
      error.textContent = 'Please select your gender.';
      return false;
    }
    error.textContent = '';
    return true;
  }

  // Validate while users interact, then re-check confirmation after password changes.
  Object.entries(fields).forEach(([name, field]) => {
    const eventName = field.type === 'checkbox' ? 'change' : 'input';
    field.addEventListener(eventName, () => {
      if (name === 'password') {
        updateStrengthMeter();
      }
      validateField(name);
      if (name === 'password' && fields.confirmPassword.value) {
        validateField('confirmPassword');
      }
    });
    field.addEventListener('blur', () => {
      validateField(name);
    });
  });

  form.querySelectorAll('input[name="gender"]').forEach((radio) => {
    radio.addEventListener('change', validateGender);
  });

  // Submission prevents the demo form from reloading and reports one clear outcome.
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const fieldsAreValid = Object.keys(fields).every(validateField);
    const genderIsValid = validateGender();
    if (fieldsAreValid && genderIsValid) {
      formStatus.textContent = 'Registration details are valid. You can now submit them to a server in a later practical.';
      formStatus.className = 'form-status is-success';
    } else {
      formStatus.textContent = 'Please correct the highlighted fields and try again.';
      formStatus.className = 'form-status is-error';
      const firstInvalid = form.querySelector('[aria-invalid="true"], input[name="gender"]');
      if (firstInvalid) {
        firstInvalid.focus();
      }
    }
  });
});