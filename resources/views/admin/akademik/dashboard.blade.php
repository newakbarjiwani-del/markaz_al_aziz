@extends('layouts.app')

@section('title', $title)

@section('content')

<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Mata Pelajaran', 'route' => 'admin.akademik.mata-pelajaran.index', 'icon' => 'book', 'tone' => 'primary'],
            ['label' => 'Kurikulum', 'route' => 'admin.akademik.kurikulum.index', 'icon' => 'library', 'tone' => 'info'],
            ['label' => 'Jadwal', 'route' => 'admin.akademik.jadwal-pelajaran.index', 'icon' => 'calendar-event', 'tone' => 'purple'],
            ['label' => 'Kalender', 'route' => 'admin.akademik.kalender.index', 'icon' => 'calendar', 'tone' => 'warning'],
            ['label' => 'Nilai', 'route' => 'admin.akademik.nilai.index', 'icon' => 'clipboard-list', 'tone' => 'accent'],
            ['label' => 'Rapor', 'route' => 'admin.akademik.rapor.index', 'icon' => 'file-text', 'tone' => 'success'],
            ['label' => 'Laporan', 'route' => 'admin.akademik.laporan-nilai.index', 'icon' => 'chart-bar', 'tone' => 'neutral'],
        ]"
    />

    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Akademik</h2>
        <div class="dashboard-stats">
            @foreach($stats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
            @endforeach
        </div>
    </div>
</div>
@endsection
