@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Data Siswa', 'route' => 'admin.manajemen-siswa.data-siswa.index', 'icon' => 'school', 'tone' => 'primary'],
            ['label' => 'Profil Siswa', 'route' => 'admin.manajemen-siswa.profil-siswa', 'icon' => 'user-circle', 'tone' => 'info'],
            ['label' => 'Orang Tua', 'route' => 'admin.manajemen-siswa.orang-tua.index', 'icon' => 'users', 'tone' => 'success'],
            ['label' => 'Pindah Kelas', 'route' => 'admin.manajemen-siswa.pindah-kelas', 'icon' => 'arrows-exchange', 'tone' => 'warning'],
            ['label' => 'Kartu Pelajar', 'route' => 'admin.manajemen-siswa.kartu-pelajar', 'icon' => 'id-badge-2', 'tone' => 'purple'],
            ['label' => 'Riwayat Akademik', 'route' => 'admin.manajemen-siswa.riwayat-akademik', 'icon' => 'history', 'tone' => 'info'],
            ['label' => 'Berkas Siswa', 'route' => 'admin.manajemen-siswa.berkas-siswa', 'icon' => 'folder', 'tone' => 'warning'],
            ['label' => 'Import/Export', 'route' => 'admin.manajemen-siswa.impor-ekspor', 'icon' => 'file-spreadsheet', 'tone' => 'neutral'],
        ]"
    />

    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Siswa</h2>
        <div class="dashboard-stats">
            @foreach($stats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
            @endforeach
        </div>
    </div>

    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ringkasan Orang Tua</h2>
        <div class="dashboard-stats dashboard-stats--compact">
            @foreach($orangTuaStats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
            @endforeach
        </div>
    </div>

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <div class="dashboard-grid dashboard-grid--sidebar">
        <div class="card dashboard-table-card">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Siswa Terbaru</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">5 siswa terakhir ditambahkan</p>
                </div>
                <a href="{{ route('admin.manajemen-siswa.data-siswa.index') }}" class="btn-secondary text-sm">Lihat Semua</a>
            </div>
            <div class="table-scroll">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">NIS</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Nama</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Kelas</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600 dark:text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSiswa as $siswa)
                            <tr class="border-b border-slate-100 dark:border-slate-800">
                                <td class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300">{{ $siswa->nis }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $siswa->name }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $siswa->kelas?->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium
                                        {{ $siswa->status === 'aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : ($siswa->status === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300') }}">
                                        {{ ucfirst($siswa->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada data siswa.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-4">
            <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Siswa per Kelas</h2>
            <div class="space-y-3">
                @php $totalSiswa = (int) ($stats[0]['value'] ?? 0); @endphp
                @forelse($kelasList as $kelas)
                    @php $count = $kelas->siswa_count ?? 0; @endphp
                    <div>
                        <div class="mb-1 flex justify-between gap-2 text-sm">
                            <span class="min-w-0 truncate text-slate-700 dark:text-slate-300">{{ $kelas->name }}</span>
                            <span class="shrink-0 font-medium text-slate-900 dark:text-white">{{ $count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-primary-600" style="width: {{ $totalSiswa > 0 ? min(100, ($count / $totalSiswa) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada kelas.</p>
                @endforelse
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('admin.manajemen-siswa.data-siswa.index') }}" class="btn-primary flex-1 text-center text-sm">Kelola Siswa</a>
                <a href="{{ route('admin.manajemen-siswa.orang-tua.index') }}" class="btn-secondary flex-1 text-center text-sm">Orang Tua</a>
            </div>
        </div>
    </div>
</div>
@endsection
