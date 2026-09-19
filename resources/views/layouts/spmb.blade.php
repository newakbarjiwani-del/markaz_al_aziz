<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.head')
    <style>
        .spmb-shell { --spmb-hero: color-mix(in srgb, var(--color-primary-700) 88%, #012917); }
        .spmb-nav { border-bottom: 1px solid color-mix(in srgb, var(--color-primary-200) 70%, transparent); background: color-mix(in srgb, white 92%, var(--color-primary-50)); }
        .dark .spmb-nav { background: color-mix(in srgb, var(--surface-page) 92%, #03492a); border-bottom-color: color-mix(in srgb, var(--color-primary-800) 60%, transparent); }
        .spmb-nav__link { color: var(--text-primary); }
        .spmb-nav__link.is-active, .spmb-nav__link:hover { color: var(--color-primary-700); }
        .dark .spmb-nav__link.is-active, .dark .spmb-nav__link:hover { color: var(--color-primary-300); }
        .spmb-hero {
            background:
                radial-gradient(ellipse 80% 60% at 15% 20%, color-mix(in srgb, var(--color-primary-400) 35%, transparent), transparent 55%),
                linear-gradient(145deg, var(--color-primary-900), var(--color-primary-700) 55%, #055e36);
            color: #eef9f3;
        }
        .spmb-hero__brand { font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 800; letter-spacing: -0.03em; line-height: 1.05; }
        .spmb-section { padding-block: clamp(2.5rem, 5vw, 4rem); }
        .spmb-footer { border-top: 1px solid color-mix(in srgb, var(--color-primary-200) 60%, transparent); }
    </style>
</head>
<body class="spmb-shell app-body min-h-full font-sans antialiased">
    @php
        $nav = $spmbNav ?? '';
    @endphp
    <header class="spmb-nav sticky top-0 z-40 backdrop-blur">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('spmb.home') }}" class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                <img src="{{ asset('logo.png') }}" alt="" class="h-9 w-9 object-contain">
                <span>ITTIHAD · SPMB</span>
            </a>
            <nav class="flex flex-wrap items-center gap-1 text-sm font-medium sm:gap-3">
                <a href="{{ route('spmb.home') }}" class="spmb-nav__link rounded-md px-2 py-1 {{ $nav === 'home' ? 'is-active' : '' }}">Beranda</a>
                <a href="{{ route('spmb.pengumuman.index') }}" class="spmb-nav__link rounded-md px-2 py-1 {{ $nav === 'pengumuman' ? 'is-active' : '' }}">Pengumuman</a>
                <a href="{{ route('spmb.berita.index') }}" class="spmb-nav__link rounded-md px-2 py-1 {{ $nav === 'berita' ? 'is-active' : '' }}">Berita</a>
                <a href="{{ route('spmb.galeri.index') }}" class="spmb-nav__link rounded-md px-2 py-1 {{ $nav === 'galeri' ? 'is-active' : '' }}">Galeri</a>
                <a href="{{ route('spmb.daftar') }}" class="btn-primary ml-1 text-sm {{ $nav === 'daftar' ? 'ring-2 ring-offset-2 ring-primary-400' : '' }}">Daftar</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="spmb-footer mt-auto">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-slate-600 dark:text-slate-400 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} Yayasan Ittihad Pekanbaru — Seleksi Penerimaan Murid Baru</p>
            <a href="{{ route('login') }}" class="hover:text-primary-700 dark:hover:text-primary-300">Masuk admin</a>
        </div>
    </footer>

    <div id="toast-container" class="toast-container pointer-events-none fixed right-4 top-20 flex flex-col gap-2"></div>
    <x-app-dialog />
    @include('layouts.partials.scripts')
    @stack('scripts')
</body>
</html>
