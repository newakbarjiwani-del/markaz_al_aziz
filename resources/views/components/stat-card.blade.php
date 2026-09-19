@props(['label', 'value' => 0, 'icon' => null, 'iconName' => null, 'accent' => 'primary', 'suffix' => '', 'currency' => false, 'hint' => null])

<div @class([
    'stat-card',
    'stat-card-' . $accent,
    'stat-card--currency' => $currency,
])>
    <div class="flex items-start justify-between">
        <div>
            <p class="stat-card__label text-sm">{{ $label }}</p>
            <p class="stat-card__value mt-2 break-words text-2xl font-bold sm:text-3xl">
                {{ is_numeric($value) ? number_format($value) : $value }}{{ $suffix }}
            </p>
            @if($hint)
                <p class="stat-card__hint mt-1 text-xs opacity-80">{{ $hint }}</p>
            @endif
        </div>
        @if($iconName)
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/40 text-current dark:bg-black/20">
                <x-icon :name="$iconName" size="md" />
            </div>
        @elseif($icon)
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/40 text-current dark:bg-black/20">
                {!! $icon !!}
            </div>
        @endif
    </div>
</div>
