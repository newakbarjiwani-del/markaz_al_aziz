@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Halaqoh', 'route' => 'admin.tahfidz.halaqoh.index', 'icon' => 'users', 'tone' => 'primary'],
            ['label' => 'Jadwal', 'route' => 'admin.tahfidz.jadwal.index', 'icon' => 'calendar-event', 'tone' => 'info'],
            ['label' => 'Rekap', 'route' => 'admin.tahfidz.rekap.index', 'icon' => 'clipboard-list', 'tone' => 'success'],
            ['label' => 'Kirim WA', 'route' => 'admin.tahfidz.kirim-wa.index', 'icon' => 'brand-whatsapp', 'tone' => 'warning'],
        ]"
    />

    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Tahfidz</h2>
        <div class="dashboard-stats">
            @foreach($stats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
            @endforeach
        </div>
    </div>
</div>
@endsection
