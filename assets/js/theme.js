/* Progressive enhancement: content, links and charts remain usable without JS. */
(function () {
  'use strict';
  var language = 'en';
  var serverClockOffset = 0;
  var saved;
  try { saved = localStorage.getItem('khaiwal-language'); } catch (e) { saved = null; }
  if (saved === 'hi') language = 'hi';

  function localDate(date, options) {
    try { return new Intl.DateTimeFormat(language === 'hi' ? 'hi-IN' : 'en-IN', Object.assign({ timeZone: 'Asia/Kolkata' }, options)).format(date); }
    catch (e) { return ''; }
  }
  function updateDates() {
    var now = new Date(Date.now() + serverClockOffset);
    document.querySelectorAll('[data-ist-clock]').forEach(function (el) {
      el.textContent = localDate(now, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
    });
    document.querySelectorAll('[data-ist-date]').forEach(function (el) {
      el.textContent = localDate(now, { day: '2-digit', month: 'short', year: 'numeric' });
    });
    document.querySelectorAll('[data-dashboard-current-date], [data-dashboard-recent-date]').forEach(function (el) {
      var value = el.getAttribute('datetime');
      if (/^\d{4}-\d{2}-\d{2}$/.test(value)) el.textContent = localDate(new Date(value + 'T12:00:00Z'), { day: 'numeric', month: 'short' });
    });
    document.querySelectorAll('[data-month-label]').forEach(function (el) {
      var value = el.getAttribute('data-month-label');
      if (/^\d{4}-\d{2}$/.test(value)) el.textContent = localDate(new Date(value + '-15T12:00:00Z'), { month: 'long', year: 'numeric' });
    });
    document.querySelectorAll('[data-month-option]').forEach(function (el) {
      var value = el.getAttribute('data-month-option');
      if (/^\d{4}-\d{2}$/.test(value)) el.textContent = localDate(new Date(value + '-15T12:00:00Z'), { month: 'long' });
    });
    document.querySelectorAll('[data-result-date]').forEach(function (el) {
      var value = el.getAttribute('data-result-date');
      if (/^\d{4}-\d{2}-\d{2}$/.test(value)) el.textContent = localDate(new Date(value + 'T12:00:00Z'), { day: '2-digit', month: 'short' });
    });
  }
  function setLanguage(value) {
    language = value === 'hi' ? 'hi' : 'en';
    document.documentElement.lang = language === 'hi' ? 'hi' : 'en-IN';
    document.querySelectorAll('[data-en][data-hi]').forEach(function (el) { el.textContent = el.getAttribute('data-' + language); });
    document.querySelectorAll('[data-placeholder-en][data-placeholder-hi]').forEach(function (el) { el.setAttribute('placeholder', el.getAttribute('data-placeholder-' + language)); });
    document.querySelectorAll('[data-aria-en][data-aria-hi]').forEach(function (el) { el.setAttribute('aria-label', el.getAttribute('data-aria-' + language)); });
    document.querySelectorAll('[data-language]').forEach(function (button) { button.setAttribute('aria-pressed', button.getAttribute('data-language') === language ? 'true' : 'false'); });
    try { localStorage.setItem('khaiwal-language', language); } catch (e) { /* Storage can be disabled. */ }
    updateDates();
  }
  document.querySelectorAll('[data-language]').forEach(function (button) { button.addEventListener('click', function () { setLanguage(button.getAttribute('data-language')); }); });
  setLanguage(language);
  window.setInterval(updateDates, 60000);

  var grid = document.querySelector('[data-game-grid]');
  if (grid) {
    var cards = Array.from(grid.querySelectorAll('[data-game-card]'));
    var search = document.querySelector('[data-game-search]');
    var filters = Array.from(document.querySelectorAll('[data-filter]'));
    var empty = document.querySelector('.empty-search');
    var active = 'all';
    function applyFilters() {
      var term = search ? search.value.trim().toLocaleLowerCase() : '';
      var count = 0;
      cards.forEach(function (card) {
        var matchesText = card.getAttribute('data-name').toLocaleLowerCase().indexOf(term) !== -1;
        var matchesFilter = active === 'all' || (active === 'popular' && card.getAttribute('data-popular') === 'yes') || card.getAttribute('data-status') === active;
        card.hidden = !(matchesText && matchesFilter);
        if (!card.hidden) count++;
      });
      if (empty) empty.hidden = count !== 0;
    }
    if (search) search.addEventListener('input', applyFilters);
    filters.forEach(function (button) { button.addEventListener('click', function () {
      active = button.getAttribute('data-filter');
      filters.forEach(function (item) { var selected = item === button; item.classList.toggle('is-selected', selected); item.setAttribute('aria-pressed', String(selected)); });
      applyFilters();
    }); });
  }

  // Refresh only this site's published WordPress data. No external feed is scraped.
  var dashboard = document.querySelector('[data-dashboard]');
  if (dashboard) {
    var dashboardOffset = Number(dashboard.getAttribute('data-server-ts')) * 1000 - Date.now();
    serverClockOffset = dashboardOffset;
    updateDates();
    var nextTime = Number(dashboard.getAttribute('data-next-ts')) || 0;
    var requestedAtBoundary = false;
    var refreshing = false;
    var select = function (name) { return dashboard.querySelector('[data-dashboard-' + name + ']'); };
    function text(el, value) { if (el && el.textContent !== value) el.textContent = value; }
    function translated(el, en, hi) {
      if (!el) return;
      el.setAttribute('data-en', en); el.setAttribute('data-hi', hi);
      text(el, language === 'hi' ? hi : en);
    }
    function eventName(el, event, en, hi) {
      if (!el) return;
      if (event) { el.removeAttribute('data-en'); el.removeAttribute('data-hi'); text(el, event.name); }
      else translated(el, en, hi);
    }
    function eventDate(el, date) {
      if (!el) return;
      el.setAttribute('datetime', date || '');
      text(el, date ? localDate(new Date(date + 'T12:00:00Z'), { day: 'numeric', month: 'short' }) : '—');
    }
    function eventLink(el, event) {
      if (!el || !event || !event.url) return;
      try { var url = new URL(event.url, window.location.href); if (url.origin === window.location.origin) el.href = url.href; } catch (e) { /* Keep the server-rendered link. */ }
    }
    function tickCountdown() {
      var el = select('countdown');
      if (!el) return;
      if (!nextTime) { text(el, '—'); return; }
      var remaining = Math.max(0, Math.ceil((nextTime * 1000 - (Date.now() + dashboardOffset)) / 1000));
      var part = function (value) { return String(value).padStart(2, '0'); };
      text(el, part(Math.floor(remaining / 3600)) + ':' + part(Math.floor(remaining % 3600 / 60)) + ':' + part(remaining % 60));
      if (!remaining && !requestedAtBoundary && !refreshing) { requestedAtBoundary = true; refreshDashboard(); }
    }
    function updateEvent(prefix, event) {
      var isRecent = prefix === 'recent';
      eventName(select(prefix + '-name'), event, isRecent ? 'No recent result' : 'No scheduled game', isRecent ? 'पिछला रिज़ल्ट उपलब्ध नहीं' : 'कोई निर्धारित गेम नहीं');
      text(select(prefix + '-hi'), event ? (prefix === 'current' ? '/ ' : '') + event.hindi : '');
      if (prefix !== 'recent') text(select(prefix + '-time'), event && event.time ? event.time + ' IST' : '—');
      if (prefix !== 'next') {
        text(select(prefix + '-result'), event && event.result ? event.result : '—');
        eventDate(select(prefix + '-date'), event && event.date);
      }
      eventLink(select(prefix + '-link'), event);
    }
    function updateCards(state) {
      document.querySelectorAll('[data-game-card][data-game-slug]').forEach(function (card) {
        var game = state.cards && state.cards[card.getAttribute('data-game-slug')];
        if (!game) return;
        var published = /^\d{2}$/.test(game.today || '');
        text(card.querySelector('.previous-number'), game.previous || '—');
        var current = card.querySelector('.current-number');
        text(current, published ? game.today : '— —');
        if (current) current.classList.toggle('is-waiting', !published);
        var status = card.querySelector('.result-status');
        if (status) {
          status.classList.toggle('is-declared', published);
          translated(status.querySelector('[data-en][data-hi]'), published ? 'Result declared' : 'Awaiting result', published ? 'रिज़ल्ट घोषित' : 'रिज़ल्ट की प्रतीक्षा');
        }
        card.setAttribute('data-status', published ? 'declared' : 'awaiting');
      });
      document.querySelectorAll('.full-result-table tbody tr').forEach(function (row) {
        var cell = row.querySelector('[data-chart-date]');
        if (cell) row.classList.toggle('is-today', cell.getAttribute('data-chart-date') === state.today);
      });
      if (!state.chart) return;
      document.querySelectorAll('[data-chart-cell]').forEach(function (cell) {
        var values = state.chart[cell.getAttribute('data-game-slug')] || {};
        var result = values[cell.getAttribute('data-chart-date')] || '';
        var published = /^\d{2}$/.test(result);
        text(cell, published ? result : '—');
        cell.classList.toggle('has-result', published);
        cell.classList.toggle('is-unavailable', !published);
      });
    }
    function refreshUnavailable() {
      var note = select('refresh-note');
      if (note) translated(note.querySelector('[data-en][data-hi]'), 'Refresh unavailable; reload to check.', 'अपडेट उपलब्ध नहीं; जाँचने के लिए पेज फिर खोलें।');
    }
    function refreshDashboard() {
      if (refreshing || document.hidden) return;
      if (!window.fetch || !window.URL) { refreshUnavailable(); return; }
      var url;
      try { url = new URL(dashboard.getAttribute('data-dashboard-url'), window.location.href); } catch (e) { refreshUnavailable(); return; }
      // Keep preview and production requests on the same origin.
      if (url.origin !== window.location.origin) { refreshUnavailable(); return; }
      var chart = document.querySelector('[data-chart-month]');
      if (chart) url.searchParams.set('month', chart.getAttribute('data-chart-month'));
      refreshing = true;
      fetch(url.href, { credentials: 'same-origin', cache: 'no-store' }).then(function (response) {
        if (!response.ok) throw new Error('Dashboard unavailable');
        return response.json();
      }).then(function (state) {
        if (!state || typeof state.server_ts !== 'number') throw new Error('Invalid dashboard response');
        dashboardOffset = state.server_ts * 1000 - Date.now();
        serverClockOffset = dashboardOffset;
        updateDates();
        updateEvent('current', state.current); updateEvent('recent', state.recent); updateEvent('next', state.next);
        var status = select('status');
        var published = !!(state.current && /^\d{2}$/.test(state.current.result || ''));
        if (status) { status.classList.toggle('is-published', published); translated(status.querySelector('[data-en][data-hi]'), published ? 'Result published' : 'Awaiting result', published ? 'रिज़ल्ट प्रकाशित' : 'रिज़ल्ट की प्रतीक्षा'); }
        var newNextTime = state.next ? Number(state.next.timestamp) : 0;
        if (newNextTime !== nextTime) requestedAtBoundary = false;
        nextTime = newNextTime;
        updateCards(state); tickCountdown();
        var note = select('refresh-note');
        if (note) translated(note.querySelector('[data-en][data-hi]'), 'Updates appear when published', 'प्रकाशित होने पर अपडेट दिखेंगे');
      }).catch(refreshUnavailable).finally(function () { refreshing = false; });
    }
    tickCountdown();
    window.setInterval(tickCountdown, 1000);
    window.setInterval(refreshDashboard, 60000);
    document.addEventListener('visibilitychange', function () { if (!document.hidden) refreshDashboard(); });
    refreshDashboard();
  }

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var journal = document.querySelector('.journal-swiper');
  if (journal && typeof window.Swiper === 'function') {
    new window.Swiper(journal, {
      slidesPerView: 1.12, spaceBetween: 14, speed: reduced ? 0 : 450,
      watchOverflow: true, keyboard: { enabled: true, onlyInViewport: true },
      navigation: { nextEl: '.slider-next', prevEl: '.slider-prev' },
      pagination: { el: '.journal-pagination', clickable: true },
      a11y: { enabled: true, slideRole: 'article' },
      breakpoints: { 600: { slidesPerView: 2.1, spaceBetween: 18 }, 900: { slidesPerView: 3, spaceBetween: 22 } }
    });
  }

  // AOS owns section reveals. GSAP owns only the hero heading and background.
  if (!reduced && window.AOS && typeof window.AOS.init === 'function') {
    document.querySelectorAll('[data-reveal]').forEach(function (el) { el.setAttribute('data-aos', 'fade-up'); });
    window.AOS.init({ once: true, duration: 600, offset: 35, easing: 'ease-out', disable: function () { return window.matchMedia('(prefers-reduced-motion: reduce)').matches; } });
  }
  if (!reduced && window.gsap && window.ScrollTrigger) {
    window.gsap.registerPlugin(window.ScrollTrigger);
    var heading = document.querySelector('.hero-heading');
    if (heading) window.gsap.from(heading, { y: 20, opacity: 0, duration: 0.75, ease: 'power2.out', clearProps: 'all' });
    if (window.matchMedia('(min-width: 821px)').matches) {
      var image = document.querySelector('.hero-image');
      if (image) window.gsap.to(image, { yPercent: 8, ease: 'none', scrollTrigger: { trigger: '.hero', start: 'top top', end: 'bottom top', scrub: true } });
    }
  }
})();
