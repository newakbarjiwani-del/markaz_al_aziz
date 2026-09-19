<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.head')
</head>
<body class="app-body min-h-full font-sans antialiased">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
            <a href="{{ route('alumni.tracer') }}" class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                <img src="{{ asset('logo.png') }}" alt="" class="h-9 w-9 object-contain">
                <span>Tracer Study Alumni</span>
            </a>
            <a href="{{ route('login') }}" class="text-sm text-primary-700 hover:underline dark:text-primary-300">Login</a>
        </div>
    </header>
    <main class="min-h-[70vh]">
        @yield('content')
    </main>
    <footer class="border-t border-slate-200 py-6 text-center text-xs text-slate-500 dark:border-slate-800">
        Yayasan Ittihad Pekanbaru
    </footer>
    @include('layouts.partials.scripts')
</body>
</html>
