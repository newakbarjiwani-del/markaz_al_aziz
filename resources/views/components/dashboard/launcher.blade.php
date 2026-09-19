@props([
    'actions' => [],
    'ariaLabel' => 'Menu pintasan',
])

@php
    $toneAliases = [
        'green' => 'success',
        'primary' => 'primary',
        'blue' => 'info',
        'info' => 'info',
        'accent' => 'warning',
        'warning' => 'warning',
        'danger' => 'danger',
        'success' => 'success',
        'purple' => 'purple',
        'neutral' => 'neutral',
    ];
@endphp

@if($actions !== [])
    <section {{ $attributes->class(['dashboard-launcher-card', 'card']) }} aria-label="{{ $ariaLabel }}">
        <nav class="dashboard-launcher">
            @foreach($actions as $action)
                @php
                    $href = $action['url']
                        ?? route($action['route'], $action['params'] ?? []);
                    $rawTone = $action['tone'] ?? 'primary';
                    $tone = $toneAliases[$rawTone] ?? 'primary';
                @endphp
                <a href="{{ $href }}"
                   @class([
                       'dashboard-launcher__tile',
                       'dashboard-launcher__tile--success' => $tone === 'success',
                       'dashboard-launcher__tile--danger' => $tone === 'danger',
                       'dashboard-launcher__tile--warning' => $tone === 'warning',
                       'dashboard-launcher__tile--info' => $tone === 'info',
                       'dashboard-launcher__tile--purple' => $tone === 'purple',
                   ])>
                    <span class="dashboard-launcher__tile-icon" aria-hidden="true">
                        <x-icon :name="$action['icon']" class="dashboard-launcher__tile-glyph" />
                    </span>
                    <span class="dashboard-launcher__tile-label">{{ $action['label'] }}</span>
                </a>
            @endforeach
        </nav>
    </section>
@endif
