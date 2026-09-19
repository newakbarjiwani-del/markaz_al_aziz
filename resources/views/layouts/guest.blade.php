<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.head')
</head>
<body class="app-body min-h-full font-sans antialiased">
    <div class="fixed right-4 top-4 z-50">
        <button type="button" data-theme-toggle
                class="rounded-lg border border-slate-200 bg-white p-2.5 text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                title="Toggle tema">
            <span class="hidden dark:inline"><x-icon name="sun" size="md" /></span>
            <span class="dark:hidden"><x-icon name="moon" size="md" /></span>
        </button>
    </div>
    @yield('content')
    <div id="toast-container" class="toast-container pointer-events-none fixed right-4 top-20 flex flex-col gap-2"></div>
    <x-app-dialog />
    @include('layouts.partials.scripts')
</body>
</html>
