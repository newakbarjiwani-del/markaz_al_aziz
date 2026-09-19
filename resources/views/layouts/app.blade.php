<!DOCTYPE html>
<html lang="id" class="h-full"@if($portalPwa ?? false) data-portal-pwa="1"@endif>
<head>
    @include('layouts.partials.head')
</head>
<body class="app-body h-full font-sans antialiased @auth has-mobile-bottom-nav @endauth">
    <div class="flex min-h-full">
        @include('layouts.partials.sidebar')
        <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-black/50 lg:hidden"></div>
        <div id="app-main" class="app-main flex min-h-screen min-w-0 flex-1 flex-col lg:pl-64">
            @include('layouts.partials.navbar')
            <main class="app-content w-full min-w-0 max-w-full flex-1 p-4 sm:p-6">
                @if(isset($title) && $title !== '')
                    <div class="mb-6">
                        <h1 class="page-title text-xl font-bold sm:text-2xl">{{ $title }}</h1>
                        @hasSection('breadcrumb')
                            <div class="page-subtitle mt-2 text-sm">@yield('breadcrumb')</div>
                        @endif
                    </div>
                @endif
                @yield('content')
            </main>
            @auth
                @include('layouts.partials.bottom-nav')
            @endauth
        </div>
    </div>
    <div id="toast-container" class="toast-container pointer-events-none fixed right-4 top-4 flex flex-col gap-2"></div>
    <x-lightbox />
    <x-app-dialog />
    @stack('modals')
    @include('layouts.partials.scripts')
</body>
</html>
