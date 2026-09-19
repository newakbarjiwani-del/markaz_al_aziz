const STORAGE_KEY = 'theme';

export function initTheme() {
    const saved = localStorage.getItem(STORAGE_KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = saved || (prefersDark ? 'dark' : 'light');
    applyTheme(theme);

    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
            applyTheme(next);
            localStorage.setItem(STORAGE_KEY, next);
        });
    });
}

function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
}
