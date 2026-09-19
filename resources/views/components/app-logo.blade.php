@props([
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'xs' => 'app-logo--xs',
        'sm' => 'app-logo--sm',
        'md' => 'app-logo--md',
        'lg' => 'app-logo--lg',
        'xl' => 'app-logo--xl',
        default => $size,
    };
@endphp

<img src="{{ asset('logo.png') }}"
     alt="{{ config('app.name') }}"
     {{ $attributes->class(['app-logo', $sizeClass]) }}
     decoding="async">
