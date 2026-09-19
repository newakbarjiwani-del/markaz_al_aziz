@props([
    'name',
    'subtitle' => null,
    'badge' => null,
    'tip' => null,
    'highlightLabel' => null,
    'highlightValue' => null,
    'highlightHint' => null,
    'chips' => [],
    'tone' => 'primary',
])

@php
    use App\Support\PortalGreeting;
@endphp

<div @class([
    'portal-dashboard-hero card mb-6 p-5',
    'portal-dashboard-hero--' . $tone,
])>
    <div class="portal-dashboard-hero__header flex items-start gap-4">
        <div class="portal-dashboard-hero__icon-wrap shrink-0" aria-hidden="true">
            <x-icon :name="PortalGreeting::icon()" size="lg" />
        </div>

        <div class="min-w-0 flex-1">
            <p class="portal-dashboard-hero__salutation">{{ PortalGreeting::salutation() }}</p>
            <h2 class="portal-dashboard-hero__name">{{ $name }}</h2>
            @if($subtitle)
                <p class="portal-dashboard-hero__subtitle">{{ $subtitle }}</p>
            @endif
            <p class="portal-dashboard-hero__clock" data-portal-clock aria-live="polite"></p>
        </div>

        @if($badge)
            <span class="badge badge-success shrink-0">{{ $badge }}</span>
        @else
            <div class="portal-dashboard-hero__logo-wrap shrink-0">
                <x-app-logo size="sm" />
            </div>
        @endif
    </div>

    @if($tip)
        <div class="portal-dashboard-hero__tip alert alert-info mt-4">
            <x-icon name="info-circle" size="sm" class="shrink-0" />
            <span>{{ $tip }}</span>
        </div>
    @endif

    @if($highlightLabel && $highlightValue !== null)
        <div class="portal-dashboard-hero__highlight mt-4">
            <p class="portal-dashboard-hero__highlight-label">{{ $highlightLabel }}</p>
            <p class="portal-dashboard-hero__highlight-value">{{ $highlightValue }}</p>
            @if($highlightHint)
                <p class="portal-dashboard-hero__highlight-hint mt-1 text-xs opacity-80">{{ $highlightHint }}</p>
            @endif
        </div>
    @endif

    @if($chips !== [])
        <div class="portal-dashboard-hero__chips mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach($chips as $chip)
                <div class="portal-dashboard-hero__chip">
                    <p class="portal-dashboard-hero__chip-label">{{ $chip['label'] }}</p>
                    <p class="portal-dashboard-hero__chip-value">{{ $chip['value'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    {{ $slot }}
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/portal-dashboard.js') }}?v=2"></script>
    @endpush
@endonce
