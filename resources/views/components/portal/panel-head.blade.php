@props([
    'title',
    'subtitle' => null,
    'actionUrl' => null,
    'actionLabel' => null,
    'bordered' => true,
])

<div @class([
    'portal-dashboard-panel__head',
    'portal-dashboard-panel__head--plain' => ! $bordered,
])>
    <div>
        <h2 class="portal-dashboard-panel__title">{{ $title }}</h2>
        @if($subtitle)
            <p class="portal-dashboard-panel__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="btn-secondary text-sm">{{ $actionLabel }}</a>
    @endif
</div>
