@props([
    'name',
    'size' => 'md',
])

@php
    $sizeClass = match ($size) {
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'md' => 'text-xl',
        'lg' => 'text-2xl',
        default => $size,
    };
@endphp

<i {{ $attributes->merge(['class' => "ti ti-{$name} {$sizeClass} leading-none"]) }} aria-hidden="true"></i>
