(function () {
    var LABELS = { dark: 'Mode Terang', light: 'Mode Gelap' };

    function applyTheme(theme) {
        var isDark = theme === 'dark';
        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        window.applyMetaThemeColor?.(isDark);
        updateToggleTooltip(theme);
        document.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: theme } }));
    }

    function getPreferredTheme() {
        var saved = localStorage.getItem('theme');
        if (saved === 'dark' || saved === 'light') {
            return saved;
        }
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function toggleTheme() {
        var next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
        applyTheme(next);
        localStorage.setItem('theme', next);
    }

    function updateToggleTooltip(theme) {
        var label = LABELS[theme] || 'Toggle tema';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('title', label);
            btn.setAttribute('aria-label', label);
        });
    }

    window.initTheme = function () {
        applyTheme(getPreferredTheme());

        if (document.documentElement.dataset.themeToggleBound) {
            return;
        }
        document.documentElement.dataset.themeToggleBound = '1';

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-theme-toggle]');
            if (btn) {
                btn.classList.add('is-toggling');
                toggleTheme();
                setTimeout(function () { btn.classList.remove('is-toggling'); }, 400);
            }
        });
    };
})();
