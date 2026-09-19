@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Data Alumni', 'route' => 'admin.alumni.alumni.index', 'icon' => 'users', 'tone' => 'primary'],
            ['label' => 'Tracer Study', 'route' => 'admin.alumni.tracer.index', 'icon' => 'clipboard-list', 'tone' => 'info'],
        ]"
    />

    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Alumni</h2>
        <div class="dashboard-stats">
            @foreach($stats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
            @endforeach
        </div>
    </div>
</div>
@endsection
