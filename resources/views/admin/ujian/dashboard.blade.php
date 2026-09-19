@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Daftar Ujian', 'route' => 'admin.ujian.ujian.index', 'icon' => 'clipboard-list', 'tone' => 'primary'],
        ]"
    />

    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Ujian Online</h2>
        <div class="dashboard-stats">
            @foreach($stats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
            @endforeach
        </div>
    </div>
</div>
@endsection
