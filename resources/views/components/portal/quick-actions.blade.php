@props([
    'title' => 'Akses Cepat',
    'description' => null,
    'actions' => [],
])

@if($actions !== [])
    <section class="portal-dashboard-section">
        @if($title || $description)
            <div class="portal-dashboard-section__head">
                <div>
                    @if($title)
                        <h2 class="portal-dashboard-section__title">{{ $title }}</h2>
                    @endif
                    @if($description)
                        <p class="portal-dashboard-section__subtitle">{{ $description }}</p>
                    @endif
                </div>
            </div>
        @endif

        <x-dashboard.launcher :actions="$actions" :aria-label="$title ?: 'Menu pintasan'" />
    </section>
@endif
