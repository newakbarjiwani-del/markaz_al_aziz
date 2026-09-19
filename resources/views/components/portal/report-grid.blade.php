@props([
    'reports' => [],
    'title' => 'Laporan',
    'subtitle' => 'Akses cepat laporan pimpinan.',
])

@if($reports !== [])
    <section class="portal-dashboard-section">
        <x-portal.panel-head
            :title="$title"
            :subtitle="$subtitle"
            :bordered="false"
        />

        <x-dashboard.launcher
            :actions="collect($reports)->map(fn ($report) => [
                'label' => $report['label'],
                'route' => $report['route'],
                'icon' => $report['icon'],
                'tone' => $report['tone'] ?? 'primary',
            ])->all()"
            :aria-label="$title"
        />
    </section>
@endif
