<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.head')
</head>
<body class="app-body min-h-full font-sans antialiased">
    @php
        $errorIcon = trim($__env->yieldContent('icon')) ?: 'alert-circle';
        $errorTone = trim($__env->yieldContent('tone')) ?: 'neutral';
    @endphp

    <div class="fixed right-4 top-4 z-50">
        <button type="button" data-theme-toggle
                class="rounded-lg border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                title="Toggle tema"
                aria-label="Toggle tema">
            <span class="hidden dark:inline"><x-icon name="sun" size="md" /></span>
            <span class="dark:hidden"><x-icon name="moon" size="md" /></span>
        </button>
    </div>

    <main class="error-page" role="main">
        <div class="error-card card border-primary-100 shadow-xl shadow-primary-900/5 dark:border-primary-900 dark:shadow-black/30">
            <div class="error-card-header">
                <x-app-logo size="lg" class="error-logo mx-auto" />
            </div>

            <div class="error-icon-wrap error-icon-wrap--{{ $errorTone }}" aria-hidden="true">
                <x-icon name="{{ $errorIcon }}" size="lg" />
            </div>

            <p class="error-code">@yield('code')</p>
            <h1 class="error-title">@yield('heading')</h1>
            <p class="error-message">@yield('message')</p>

            @hasSection('hint')
                <p class="error-hint">@yield('hint')</p>
            @endif

            <div class="error-actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a href="{{ url('/') }}" class="btn-primary">
                        <x-icon name="home" size="sm" class="mr-1" />
                        Ke Beranda
                    </a>
                    <a href="{{ route('login') }}" class="btn-secondary">
                        <x-icon name="login" size="sm" class="mr-1" />
                        Login
                    </a>
                @endif
            </div>
        </div>
    </main>

    <script src="{{ asset('js/theme.js') }}?v=4"></script>
</body>
</html>
