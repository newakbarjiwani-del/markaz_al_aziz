@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Data Guru', 'route' => 'admin.manajemen-guru.data-guru.index', 'icon' => 'chalkboard', 'tone' => 'primary'],
            ['label' => 'Profil Guru', 'route' => 'admin.manajemen-guru.profil-guru', 'icon' => 'user-circle', 'tone' => 'info'],
            ['label' => 'Kartu Guru', 'route' => 'admin.manajemen-guru.kartu-guru', 'icon' => 'id-badge-2', 'tone' => 'success'],
            ['label' => 'Riwayat Mengajar', 'route' => 'admin.manajemen-guru.riwayat-mengajar.index', 'icon' => 'book', 'tone' => 'warning'],
            ['label' => 'Import/Export', 'route' => 'admin.manajemen-guru.impor-ekspor', 'icon' => 'file-spreadsheet', 'tone' => 'neutral'],
        ]"
    />

    <div class="dashboard-stats dashboard-stats--compact">
        @foreach($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
        @endforeach
    </div>

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <div class="card dashboard-table-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Guru Terbaru</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">5 guru terakhir</p>
            </div>
            <a href="{{ route('admin.manajemen-guru.data-guru.index') }}" class="btn-secondary text-sm">Kelola Guru</a>
        </div>
        <div class="table-scroll">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                        <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">NIP</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Jabatan</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-600 dark:text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentGuru as $guru)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-3 font-mono">{{ $guru->nip }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $guru->name }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $guru->jabatan ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $guru->status === 'aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' }}">
                                    {{ ucfirst($guru->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada data guru.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
