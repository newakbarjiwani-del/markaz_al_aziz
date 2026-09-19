<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" id="meta-theme-color" content="#f2f9f5">
<meta name="theme-color" content="#f2f9f5" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#09140e" media="(prefers-color-scheme: dark)">
<meta name="msapplication-navbutton-color" id="meta-ms-nav-color" content="#f2f9f5">
<meta name="apple-mobile-web-app-status-bar-style" id="meta-apple-status-bar" content="default">
<script>
    (function () {
        var META_THEME_COLORS = { light: '#f2f9f5', dark: '#09140e' };

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
<title>@hasSection('title')@yield('title') · @endif ITTIHAD APP</title>
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
    --color-primary-50: #eefbf5;
    --color-primary-100: #d3f4e4;
    --color-primary-200: #a7e8ca;
    --color-primary-300: #6ed8a7;
    --color-primary-400: #28bc79;
    --color-primary-500: #189e61;
    --color-primary-600: #12834f;
    --color-primary-700: #077844;
    --color-primary-800: #055e36;
    --color-primary-900: #03492a;
    --color-primary-950: #012917;
    --color-accent-50: #fffde6;
    --color-accent-100: #fffbbf;
    --color-accent-200: #fff680;
    --color-accent-300: #fdf042;
    --color-accent-400: #f7e81b;
    --color-accent-500: #f3ea0e;
    --color-accent-600: #d4cb07;
    --color-accent-700: #a49d03;
    --color-accent-800: #777207;
    --color-accent-900: #5a570c;
    --color-accent-950: #343204;
}
</style>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v=175">
@stack('styles')
