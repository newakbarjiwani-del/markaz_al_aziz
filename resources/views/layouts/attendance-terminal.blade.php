<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('layouts.partials.head')
    <link rel="stylesheet" href="{{ asset('css/attendance-terminal.css') }}?v=3">
</head>
<body class="attendance-terminal min-h-full font-sans antialiased">
    <x-app-dialog />
    <div id="toast-container" class="toast-container pointer-events-none fixed right-4 top-4 flex flex-col gap-2"></div>
    @yield('content')
    @include('layouts.partials.scripts')
    @stack('scripts')
</body>
</html>
