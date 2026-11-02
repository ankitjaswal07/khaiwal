/* Progressive enhancement: content, links and charts remain usable without JS. */
(function () {
  'use strict';
  var language = 'en';
  var saved;
  try { saved = localStorage.getItem('khaiwal-language'); } catch (e) { saved = null; }
  if (saved === 'hi') language = 'hi';

  function localDate(date, options) {
    try { return new Intl.DateTimeFormat(language === 'hi' ? 'hi-IN' : 'en-IN', Object.assign({ timeZone: 'Asia/Kolkata' }, options)).format(date); }
    catch (e) { return ''; }
  }
  function updateDates() {
    var now = new Date();
    document.querySelectorAll('[data-ist-clock]').forEach(function (el) {
      el.textContent = localDate(now, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
    });
    document.querySelectorAll('[data-ist-date]').forEach(function (el) {
      el.textContent = localDate(now, { day: '2-digit', month: 'short', year: 'numeric' });
    });
    document.querySelectorAll('[data-month-label]').forEach(function (el) {
      var value = el.getAttribute('data-month-label');
      if (/^\d{4}-\d{2}$/.test(value)) el.textContent = localDate(new Date(value + '-15T12:00:00Z'), { month: 'long', year: 'numeric' });
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

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var journal = document.querySelector('.journal-swiper');
  if (journal && typeof window.Swiper === 'function') {
    new window.Swiper(journal, {
      slidesPerView: 1.12, spaceBetween: 14, speed: reduced ? 0 : 450,
      watchOverflow: true, keyboard: { enabled: true, onlyInViewport: true },
      navigation: { nextEl: '.slider-next', prevEl: '.slider-prev' },
      pagination: { el: '.journal-pagination', clickable: true },
      a11y: { enabled: true },
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
