<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" id="meta-theme-color" content="#faf7f2">
<meta name="theme-color" content="#faf7f2" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1a100a" media="(prefers-color-scheme: dark)">
<meta name="msapplication-navbutton-color" id="meta-ms-nav-color" content="#faf7f2">
<meta name="apple-mobile-web-app-status-bar-style" id="meta-apple-status-bar" content="default">
<script>
    (function () {
        var META_THEME_COLORS = { light: '#faf7f2', dark: '#1a100a' };

        function applyMetaThemeColor(isDark) {
            var color = isDark ? META_THEME_COLORS.dark : META_THEME_COLORS.light;
            var themeColor = document.getElementById('meta-theme-color');
            if (themeColor) {
                themeColor.setAttribute('content', color);
            }
            var msNav = document.getElementById('meta-ms-nav-color');
            if (msNav) {
                msNav.setAttribute('content', color);
            }
            var appleStatus = document.getElementById('meta-apple-status-bar');
            if (appleStatus) {
                appleStatus.setAttribute('content', isDark ? 'black-translucent' : 'default');
            }
        }

        window.META_THEME_COLORS = META_THEME_COLORS;
        window.applyMetaThemeColor = applyMetaThemeColor;

        try {
            var html = document.documentElement;
            var savedTheme = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);
            if (isDark) {
                html.classList.add('dark');
                html.style.colorScheme = 'dark';
            } else {
                html.style.colorScheme = 'light';
            }
            applyMetaThemeColor(isDark);
            if (localStorage.getItem('sidebar-collapsed') === 'true'
                && window.matchMedia('(min-width: 1024px)').matches) {
                html.classList.add('sidebar-collapsed');
            }
        } catch (e) {}
    })();
</script>
<title>@hasSection('title')@yield('title') · @endif {{ config('app.name') }}</title>
<link rel="icon" href="{{ asset('logo.png') }}" type="image/png">
@if($portalPwa ?? false)
<link rel="manifest" href="/portal/manifest.webmanifest">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ config('pwa.short_name') }}">
<link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png">
@endif
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<style type="text/tailwindcss">
@custom-variant dark (&:where(.dark, .dark *));

@theme {
    --font-sans: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
    --color-primary-50: #faf6f2;
    --color-primary-100: #f0e6dc;
    --color-primary-200: #e0c4a8;
    --color-primary-300: #c9986e;
    --color-primary-400: #a86b38;
    --color-primary-500: #8c4600;
    --color-primary-600: #6e3c0a;
    --color-primary-700: #5c3317;
    --color-primary-800: #4a2912;
    --color-primary-900: #3a1f0e;
    --color-primary-950: #241308;
    --color-accent-50: #fbf8ef;
    --color-accent-100: #f5edd4;
    --color-accent-200: #ead9a8;
    --color-accent-300: #dcc476;
    --color-accent-400: #c9a84f;
    --color-accent-500: #b08d3e;
    --color-accent-600: #967528;
    --color-accent-700: #755c1f;
    --color-accent-800: #5c4819;
    --color-accent-900: #4a3a16;
    --color-accent-950: #2a210c;
}
</style>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=176">
@stack('styles')
