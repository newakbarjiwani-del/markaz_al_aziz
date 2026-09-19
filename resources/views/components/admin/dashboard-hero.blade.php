@props([
    'greeting',
    'greetingIcon',
    'userName',
    'highlights' => [],
])

<div class="admin-dashboard-hero card">
    <div class="admin-dashboard-hero__content">
        <div class="admin-dashboard-hero__intro">
            <div class="admin-dashboard-hero__icon-wrap" aria-hidden="true">
                <x-icon :name="$greetingIcon" size="lg" />
            </div>
            <div class="min-w-0">
                <p class="admin-dashboard-hero__greeting">{{ $greeting }}</p>
                <h1 class="admin-dashboard-hero__title">{{ $userName }}</h1>
                <p class="admin-dashboard-hero__subtitle">
                    Pusat kendali akademik {{ config('app.name') }} — pantau siswa, keuangan, absensi, dan operasional sekolah.
                </p>
                <p class="admin-dashboard-hero__date" data-admin-dashboard-date aria-live="polite"></p>
            </div>
        </div>

        @if($highlights !== [])
            <div class="admin-dashboard-hero__highlights">
                @foreach($highlights as $item)
                    <div @class(['admin-dashboard-hero__highlight', 'admin-dashboard-hero__highlight--'.$item['tone']])>
                        <p class="admin-dashboard-hero__highlight-label">{{ $item['label'] }}</p>
                        <p class="admin-dashboard-hero__highlight-value">{{ $item['value'] }}</p>
                        @if(!empty($item['hint']))
                            <p class="admin-dashboard-hero__highlight-hint mt-1 text-xs opacity-70">{{ $item['hint'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('js/admin-dashboard.js') }}?v=1"></script>
    @endpush
@endonce
