(function () {
  'use strict';
  var form = document.getElementById('khaiwal-publish-form');
  if (!form) { return; }
  var cfg = window.khaiwalPublish || {};
  var inputs = Array.prototype.slice.call(form.querySelectorAll('.khaiwal-result-input'));
  var counters = document.querySelectorAll('[data-publish-count]');
  var submitting = false;

  function changed(input) { return input.value !== '' && input.value !== input.getAttribute('data-original'); }
  function refresh() {
    var n = 0;
    inputs.forEach(function (input) {
      var isChanged = changed(input);
      input.classList.toggle('is-changed', isChanged);
      input.classList.toggle('is-invalid', input.value !== '' && !/^\d{2}$/.test(input.value));
      if (isChanged && /^\d{2}$/.test(input.value)) { n++; }
    });
    counters.forEach(function (el) {
      el.textContent = n ? (cfg.ready || '%d ready').replace('%d', n) : (cfg.none || 'Nothing to publish yet');
      el.classList.toggle('has-entries', n > 0);
    });
    return n;
  }
  function move(from, step, sameColumn) {
    var index = inputs.indexOf(from);
    var col = from.getAttribute('data-col');
    for (var i = index + step; i >= 0 && i < inputs.length; i += step) {
      if (!inputs[i].disabled && (!sameColumn || inputs[i].getAttribute('data-col') === col)) { inputs[i].focus(); return true; }
    }
    return false;
  }

  inputs.forEach(function (input) {
    input.addEventListener('focus', function () { input.select(); });
    input.addEventListener('input', function () {
      var clean = input.value.replace(/\D/g, '').slice(0, 2);
      if (clean !== input.value) { input.value = clean; }
      refresh();
      if (clean.length === 2) { move(input, 1, false); }
    });
    input.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        if (event.ctrlKey || event.metaKey) { submit(); } else { move(input, 1, false); }
      } else if (event.key === 'ArrowDown') { event.preventDefault(); move(input, 1, true); }
      else if (event.key === 'ArrowUp') { event.preventDefault(); move(input, -1, true); }
    });
  });

  function submit() {
    var bad = inputs.filter(function (input) { return input.value !== '' && !/^\d{2}$/.test(input.value); });
    if (bad.length) { bad[0].focus(); return; }
    submitting = true;
    var button = form.querySelector('[data-publish-button]');
    if (form.requestSubmit && button) { form.requestSubmit(button); } else { form.submit(); }
  }

  form.addEventListener('submit', function (event) {
    var trigger = event.submitter;
    if (trigger && trigger.hasAttribute('data-confirm-remove')) {
      if (!window.confirm(cfg.confirmRemove || 'Remove this result?')) { event.preventDefault(); return; }
    } else {
      var bad = inputs.filter(function (input) { return input.value !== '' && !/^\d{2}$/.test(input.value); });
      if (bad.length) { event.preventDefault(); bad[0].focus(); return; }
    }
    submitting = true;
  });

  window.addEventListener('beforeunload', function (event) {
    if (!submitting && refresh() > 0) { event.preventDefault(); event.returnValue = cfg.leave || ''; }
  });
  refresh();
  var firstEmpty = inputs.filter(function (input) { return !input.disabled && input.value === ''; })[0];
  if (firstEmpty && form.querySelector('.khaiwal-day-table')) { firstEmpty.focus(); }
}());
