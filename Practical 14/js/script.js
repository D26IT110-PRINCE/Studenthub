/*
  Practical 6 - StudentHub combined functionality
  Includes:
  - Practical 4: theme persistence, notification controls, FAQ behavior, homepage slider, hamburger menu
  - Practical 5: registration form validation
  - Practical 6: fetch external JSON data with searchable, filterable, sortable, paginated views
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

  // Hamburger menu toggle
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
  if (form) {
    // Populate CSRF token from meta tag or generate client-side fallback
    const csrfInput = document.getElementById('csrf-token');
    if (csrfInput) {
      // Try to get CSRF token from meta tag (set by PHP) or use a session-based approach
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      if (csrfMeta) {
        csrfInput.value = csrfMeta.getAttribute('content');
      } else {
        // Generate a client-side token (server will validate via session)
        csrfInput.value = generateClientCsrfToken();
      }
    }

    // Handle URL parameters for server-side feedback
    handleServerFeedback();

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

    // Submission allows valid forms to proceed to the server while keeping instant feedback for errors.
    form.addEventListener('submit', (event) => {
      const fieldsAreValid = Object.keys(fields).every(validateField);
      const genderIsValid = validateGender();

      if (!fieldsAreValid || !genderIsValid) {
        event.preventDefault();
        formStatus.textContent = 'Please correct the highlighted fields and try again.';
        formStatus.className = 'form-status is-error';
        const firstInvalid = form.querySelector('[aria-invalid="true"], input[name="gender"]');
        if (firstInvalid) {
          firstInvalid.focus();
        }
      } else {
        formStatus.textContent = 'Registration details are valid. Submitting...';
        formStatus.className = 'form-status is-success';
        // Form will now naturally submit to the action URL (process_registration.php)
      }
    });
  }

  // Generate a client-side CSRF token (matches PHP session-based validation)
  function generateClientCsrfToken() {
    // Simple token generation for demo - in production this should come from server
    let token = sessionStorage.getItem('client_csrf_token');
    if (!token) {
      const array = new Uint8Array(32);
      crypto.getRandomValues(array);
      token = Array.from(array, byte => byte.toString(16).padStart(2, '0')).join('');
      sessionStorage.setItem('client_csrf_token', token);
    }
    return token;
  }

  // Handle server-side validation feedback from URL parameters
  function handleServerFeedback() {
    const urlParams = new URLSearchParams(window.location.search);
    const formStatus = document.getElementById('form-status');

    if (urlParams.has('success')) {
      formStatus.textContent = 'Registration successful! Welcome to StudentHub.';
      formStatus.className = 'form-status is-success';

      // Add View Registrations button
      const viewButton = document.createElement('a');
      viewButton.href = 'view_registrations.php';
      viewButton.className = 'btn btn-secondary view-registrations-btn';
      viewButton.textContent = 'View All Registrations';
      viewButton.style.marginLeft = '1rem';
      formStatus.appendChild(viewButton);

      // Clear the URL parameter
      window.history.replaceState({}, document.title, window.location.pathname);
    } else if (urlParams.has('errors')) {
      const errors = decodeURIComponent(urlParams.get('errors')).split('|');
      formStatus.textContent = errors.join(' ');
      formStatus.className = 'form-status is-error';
      // Clear the URL parameter
      window.history.replaceState({}, document.title, window.location.pathname);
    } else if (urlParams.has('error')) {
      const errorMap = {
        'invalid_method': 'Invalid request method. Please use the registration form.',
        'csrf_invalid': 'Security token invalid. Please refresh the page and try again.'
      };
      const errorKey = urlParams.get('error');
      formStatus.textContent = errorMap[errorKey] || 'An error occurred. Please try again.';
      formStatus.className = 'form-status is-error';
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }

  /* ============================================================
     PRACTICAL 6: Fetch external JSON data with search, filter, sort, pagination
     ============================================================ */

  const PAGE_SIZE = 5;

  const datasetConfigs = {
    events: {
      file: 'data/events.json',
      label: 'event',
      pluralLabel: 'events',
      searchFields: ['title'],
      filterField: 'category',
      sorters: {
        'date-asc': (first, second) => compareDates(first.date, second.date),
        'date-desc': (first, second) => compareDates(second.date, first.date),
        'title-asc': (first, second) => compareText(first.title, second.title),
        'title-desc': (first, second) => compareText(second.title, first.title)
      },
      render: renderEvent
    },
    students: {
      file: 'data/students.json',
      label: 'student',
      pluralLabel: 'students',
      searchFields: ['name', 'email'],
      filterField: 'course',
      sorters: {
        'name-asc': (first, second) => compareText(first.name, second.name),
        'name-desc': (first, second) => compareText(second.name, first.name),
        'enrollment-asc': (first, second) => compareDates(first.enrollmentDate, second.enrollmentDate),
        'enrollment-desc': (first, second) => compareDates(second.enrollmentDate, first.enrollmentDate)
      },
      render: renderStudent
    },
    faqs: {
      file: 'data/faqs.json',
      label: 'FAQ',
      pluralLabel: 'FAQs',
      searchFields: ['question', 'answer'],
      filterField: 'category',
      sorters: {
        'question-asc': (first, second) => compareText(first.question, second.question),
        'question-desc': (first, second) => compareText(second.question, first.question),
        'updated-asc': (first, second) => compareDates(first.updatedAt, second.updatedAt),
        'updated-desc': (first, second) => compareDates(second.updatedAt, first.updatedAt)
      },
      render: renderFaq
    }
  };

  function compareText(first, second) {
    return String(first ?? '').localeCompare(String(second ?? ''), undefined, {
      sensitivity: 'base',
      numeric: true
    });
  }

  function compareDates(first, second) {
    const firstDate = Date.parse(first);
    const secondDate = Date.parse(second);

    if (Number.isNaN(firstDate) && Number.isNaN(secondDate)) return 0;
    if (Number.isNaN(firstDate)) return 1;
    if (Number.isNaN(secondDate)) return -1;
    return firstDate - secondDate;
  }

  function formatDate(value) {
    if (!value) return 'Not specified';

    const date = new Date(`${value}T00:00:00Z`);
    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat('en-GB', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      timeZone: 'UTC'
    }).format(date);
  }

  function escapeInitials(name) {
    return String(name)
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map(part => part.charAt(0).toUpperCase())
      .join('');
  }

  function createElement(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined) element.textContent = text;
    return element;
  }

  function addDetailRow(container, label, value) {
    const labelElement = createElement('dt', 'record-label', label);
    const valueElement = createElement('dd', 'record-value', value);
    container.append(labelElement, valueElement);
  }

  function renderEvent(record) {
    const card = createElement('article', 'event-card');
    card.dataset.recordId = record.id;

    const title = createElement('h3');
    title.textContent = record.title || 'Untitled event';
    card.append(title);

    const details = createElement('dl', 'record-details');
    addDetailRow(details, 'Date', formatDate(record.date));
    addDetailRow(details, 'Location', record.location || 'Not specified');
    addDetailRow(details, 'Category', record.category || 'General');
    card.append(details);

    const badge = createElement('span', 'category-badge', record.category || 'General');
    card.append(badge);
    return card;
  }

  function renderStudent(record) {
    const card = createElement('article', 'student-card');
    card.dataset.recordId = record.id;

    const header = createElement('div', 'student-card-header');
    const avatar = createElement('span', 'student-avatar', escapeInitials(record.name));
    avatar.setAttribute('aria-hidden', 'true');
    const heading = createElement('h3');
    heading.textContent = record.name || 'Unnamed student';
    header.append(avatar, heading);
    card.append(header);

    const details = createElement('dl', 'record-details');
    addDetailRow(details, 'Email', record.email || 'Not specified');
    addDetailRow(details, 'Course', record.course || 'Not specified');
    addDetailRow(details, 'Year', record.year || 'Not specified');
    addDetailRow(details, 'City', record.city || 'Not specified');
    addDetailRow(details, 'Enrollment date', formatDate(record.enrollmentDate));
    card.append(details);
    return card;
  }

  function renderFaq(record) {
    const item = document.createElement('details');
    item.className = 'faq-item';
    item.dataset.recordId = record.id;

    const summary = createElement('summary');
    const question = createElement('span', 'faq-question', record.question || 'Untitled question');
    const badge = createElement('span', 'category-badge', record.category || 'General');
    summary.append(question, badge);

    const answer = createElement('p', 'faq-answer', record.answer || 'No answer is available.');
    const updated = createElement('p', 'faq-updated', `Last updated: ${formatDate(record.updatedAt)}`);
    item.append(summary, answer, updated);
    return item;
  }

  function setResultsState(section, message, type = '') {
    const status = section.querySelector('[data-status]');
    if (status) {
      status.textContent = message;
      status.className = `data-status${type ? ` is-${type}` : ''}`;
    }
  }

  function showLoading(section, config) {
    const results = section.querySelector('[data-results]');
    const loading = createElement('div', 'data-message loading-state');
    const spinner = createElement('span', 'spinner');
    spinner.setAttribute('aria-hidden', 'true');
    loading.append(spinner, createElement('span', '', `Loading ${config.pluralLabel}...`));
    results?.replaceChildren(loading);
  }

  function showError(section, config, error) {
    console.error(`Unable to load ${config.file}:`, error);
    const results = section.querySelector('[data-results]');
    const message = createElement('div', 'data-message error-state');
    message.setAttribute('role', 'alert');
    message.append(
      createElement('strong', '', `Unable to load ${config.pluralLabel}.`),
      createElement('p', '', 'Check that the JSON file exists and try again.')
    );
    results?.replaceChildren(message);
    setResultsState(section, `Failed to load ${config.pluralLabel}`, 'error');
  }

  function populateFilterOptions(section, config, records) {
    const filter = section.querySelector('[data-filter]');
    if (!filter || !config.filterField) return;

    const selectedValue = filter.value;
    const values = [...new Set(
      records
        .map(record => record[config.filterField])
        .filter(value => value !== undefined && value !== null && String(value).trim() !== '')
        .map(value => String(value).trim())
    )].sort((first, second) => compareText(first, second));

    const filterLabel = config.filterField === 'course' ? 'All courses' : 'All categories';
    const allOption = createElement('option', '', filterLabel);
    allOption.value = '';
    filter.replaceChildren(allOption);
    values.forEach(value => {
      const option = createElement('option', '', value);
      option.value = value;
      filter.append(option);
    });

    if (values.includes(selectedValue)) filter.value = selectedValue;
  }

  function getFilteredRecords(records, config, controls) {
    const query = controls.search.value.trim().toLocaleLowerCase();
    const filterValue = controls.filter.value;
    const sorter = config.sorters[controls.sort.value];

    const filtered = records.filter(record => {
      const matchesSearch = !query || config.searchFields.some(field =>
        String(record[field] ?? '').toLocaleLowerCase().includes(query)
      );
      const matchesFilter = !filterValue || String(record[config.filterField] ?? '') === filterValue;
      return matchesSearch && matchesFilter;
    });

    return sorter ? [...filtered].sort(sorter) : filtered;
  }

  function renderRecords(section, config, records, currentPage) {
    const results = section.querySelector('[data-results]');
    const totalPages = Math.max(1, Math.ceil(records.length / PAGE_SIZE));
    const safePage = Math.min(currentPage, totalPages);
    const start = (safePage - 1) * PAGE_SIZE;
    const pageRecords = records.slice(start, start + PAGE_SIZE);

    results.replaceChildren();
    pageRecords.forEach(record => results.append(config.render(record)));

    const countLabel = records.length === 1 ? config.label : config.pluralLabel;
    setResultsState(
      section,
      `${records.length} ${countLabel} found`,
      records.length ? '' : 'empty'
    );

    if (!records.length) {
      const empty = createElement('div', 'data-message empty-state');
      empty.append(
        createElement('strong', '', `No ${config.pluralLabel} found.`),
        createElement('p', '', 'Try changing your search or filter.')
      );
      results.append(empty);
    }

    return totalPages;
  }

  function getVisiblePages(totalPages, currentPage) {
    if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1);

    const pages = new Set([1, totalPages, currentPage - 1, currentPage, currentPage + 1]);
    const visiblePages = [...pages]
      .filter(page => page >= 1 && page <= totalPages)
      .sort((first, second) => first - second);
    const result = [];

    visiblePages.forEach((page, index) => {
      if (index && page - visiblePages[index - 1] > 1) result.push('ellipsis');
      result.push(page);
    });
    return result;
  }

  function createPageButton(page, currentPage, onClick) {
    const button = createElement('button', 'page-button', String(page));
    button.type = 'button';
    button.dataset.page = String(page);
    button.setAttribute('aria-label', `Go to page ${page}`);
    if (page === currentPage) {
      button.setAttribute('aria-current', 'page');
      button.disabled = true;
    }
    button.addEventListener('click', () => onClick(page));
    return button;
  }

  function renderPagination(section, currentPage, totalPages, onChangePage) {
    const pagination = section.querySelector('[data-pagination]');
    if (!pagination) return;

    pagination.replaceChildren();

    const previous = createElement('button', 'pagination-arrow', 'Previous');
    previous.type = 'button';
    previous.disabled = currentPage <= 1;
    previous.setAttribute('aria-label', 'Go to previous page');
    previous.addEventListener('click', () => onChangePage(currentPage - 1));

    const summary = createElement('span', 'pagination-summary', `Page ${currentPage} of ${totalPages}`);
    summary.setAttribute('aria-live', 'polite');

    const next = createElement('button', 'pagination-arrow', 'Next');
    next.type = 'button';
    next.disabled = currentPage >= totalPages;
    next.setAttribute('aria-label', 'Go to next page');
    next.addEventListener('click', () => onChangePage(currentPage + 1));

    pagination.append(previous);
    getVisiblePages(totalPages, currentPage).forEach(page => {
      if (page === 'ellipsis') {
        const ellipsis = createElement('span', 'pagination-ellipsis', '…');
        ellipsis.setAttribute('aria-hidden', 'true');
        pagination.append(ellipsis);
      } else {
        pagination.append(createPageButton(page, currentPage, onChangePage));
      }
    });
    pagination.append(summary, next);
  }

  function updateView(section, config, state, controls) {
    const filtered = getFilteredRecords(state.records, config, controls);
    state.totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
    state.currentPage = Math.min(state.currentPage, state.totalPages);
    const totalPages = renderRecords(section, config, filtered, state.currentPage);
    renderPagination(section, state.currentPage, totalPages, nextPage => {
      state.currentPage = nextPage;
      updateView(section, config, state, controls);
    });
  }

  function initializeDataset(section) {
    const config = datasetConfigs[section.dataset.dataset];
    if (!config) return;

    const controls = {
      search: section.querySelector('[data-search]'),
      filter: section.querySelector('[data-filter]'),
      sort: section.querySelector('[data-sort]')
    };
    const state = {
      records: [],
      currentPage: 1,
      totalPages: 1
    };

    showLoading(section, config);

    fetch(config.file)
      .then(response => {
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        return response.json();
      })
      .then(records => {
        if (!Array.isArray(records)) throw new TypeError('JSON data must be an array');
        state.records = records;
        populateFilterOptions(section, config, records);
        updateView(section, config, state, controls);
      })
      .catch(error => showError(section, config, error));

    controls.search?.addEventListener('input', () => {
      state.currentPage = 1;
      updateView(section, config, state, controls);
    });
    controls.filter?.addEventListener('change', () => {
      state.currentPage = 1;
      updateView(section, config, state, controls);
    });
    controls.sort?.addEventListener('change', () => {
      state.currentPage = 1;
      updateView(section, config, state, controls);
    });
  }

  document.querySelectorAll('[data-dataset]').forEach(initializeDataset);
});