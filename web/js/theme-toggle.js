(function() {
  var STORAGE_KEY = 'nku-theme';

  function applyTheme(theme) {
    var root = document.documentElement;
    theme = theme || 'light';
    if (theme === 'dark') {
      root.setAttribute('data-theme', 'dark');
    } else {
      root.removeAttribute('data-theme');
    }
    localStorage.setItem(STORAGE_KEY, theme);
    var checkbox = document.getElementById('theme-switch');
    if (checkbox) checkbox.checked = theme === 'dark';
  }

  function init() {
    var saved = localStorage.getItem(STORAGE_KEY) || 'light';
    applyTheme(saved);

    var checkbox = document.getElementById('theme-switch');
    if (checkbox) {
      checkbox.addEventListener('change', function() {
        applyTheme(this.checked ? 'dark' : 'light');
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
