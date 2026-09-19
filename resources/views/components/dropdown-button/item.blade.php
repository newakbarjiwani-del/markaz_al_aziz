@props([
    'href' => null,
    'icon' => null,
    'tone' => 'default',
    'disabled' => false,
    'type' => 'button',
])

@php
    $toneClass = $tone !== 'default' ? "dropdown-button__item--{$tone}" : '';
    $tag = filled($href) ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($tag === 'a')
        href="{{ $href }}"
    @else
        type="{{ $type }}"
        @disabled($disabled)
    @endif
    {{ $attributes->class(['dropdown-button__item', $toneClass]) }}
    role="menuitem">
    @if($icon)
        <x-icon :name="$icon" size="sm" />
    @endif
    <span>{{ $slot }}</span>
</{{ $tag }}>
