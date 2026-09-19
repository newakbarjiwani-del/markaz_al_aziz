@props(['modules' => []])

@if($modules !== [])
    <section class="admin-dashboard-modules">
        <div class="admin-dashboard-section-head">
            <div>
                <h2 class="admin-dashboard-section-title">Modul Akademik</h2>
                <p class="admin-dashboard-section-subtitle">Akses cepat ke seluruh layanan administrasi sekolah.</p>
            </div>
        </div>

        <x-dashboard.launcher
            :actions="collect($modules)->map(fn ($module) => [
                'label' => $module['label'],
                'route' => $module['route'],
                'icon' => $module['icon'],
                'tone' => $module['tone'] ?? 'primary',
            ])->all()"
            aria-label="Modul akademik"
        />
    </section>
@endif
