/* Practical 6: fetch external JSON data and provide searchable, filterable,
   sortable, and paginated views. */
(() => {
  'use strict';

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

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dataset]').forEach(initializeDataset);
  });
})();
