@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="dashboard-page space-y-6">
    <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-white">Dashboard Perizinan</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">Ringkasan perizinan keluar masuk harian, pondok, dan pulang libur</p>
    </div>

    @php
        $routePrefix = isset($isPortal) && $isPortal ? 'portal.perizinan' : (request()->is('admin/*') ? 'admin.perizinan' : 'portal.perizinan');
    @endphp

    <x-dashboard.launcher
        :actions="[
            ['label' => 'Izin Keluar Masuk', 'route' => $routePrefix.'.keluar-masuk.index', 'icon' => 'door-exit', 'tone' => 'success'],
            ['label' => 'Izin Pondok', 'route' => $routePrefix.'.keluar-masuk-pondok.index', 'icon' => 'building-community', 'tone' => 'info'],
            ['label' => 'Pulang Libur', 'route' => $routePrefix.'.pulang-libur.index', 'icon' => 'calendar-off', 'tone' => 'warning'],
            ['label' => 'Rekap & Laporan', 'route' => $routePrefix.'.rekap-laporan', 'icon' => 'clipboard-check', 'tone' => 'primary'],
        ]"
    />

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <x-stat-card label="Total Perizinan" :value="$totalCount" accent="primary" />
        <x-stat-card label="Sedang Izin / Aktif" :value="$aktifCount" accent="info" />
        <x-stat-card label="Kembali Tepat Waktu" :value="$kembaliCount" accent="emerald" />
        <x-stat-card label="Terlambat" :value="$terlambatCount" accent="rose" />
        <x-stat-card label="Menunggu Persetujuan" :value="$pendingCount" accent="amber" />
    </div>

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <!-- Recent Perizinan Table -->
    <div class="card dashboard-table-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Aktivitas Perizinan Terbaru</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400">10 data perizinan paling baru ditambahkan</p>
            </div>
            <a href="{{ $rekapUrl ?? route('portal.perizinan.rekap-laporan') }}" class="btn-secondary text-xs">Lihat Semua Perizinan</a>
        </div>
        <div class="table-scroll">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-left dark:border-slate-800 dark:bg-slate-900">
                        <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Siswa</th>
                        <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Jenis Perizinan</th>
                        <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Alasan</th>
                        <th class="px-4 py-3 font-medium text-slate-600 dark:text-slate-400">Waktu Izin</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-600 dark:text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPerizinan as $p)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/50 dark:border-slate-800 dark:hover:bg-slate-800/50">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">
                                <div>{{ $p->siswa?->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500">NIS {{ $p->siswa?->nis ?? '-' }} · {{ $p->siswa?->kelas?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                <span class="font-medium">{{ $p->jenis_label }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 max-w-xs truncate">{{ $p->alasan }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                <div><span class="text-slate-400">Mulai:</span> {{ $p->tgl_mulai ? $p->tgl_mulai->format('d/m/Y H:i') : '-' }}</div>
                                <div><span class="text-slate-400">Sampai:</span> {{ $p->tgl_sampai ? $p->tgl_sampai->format('d/m/Y H:i') : '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                {!! $p->status_badge !!}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Belum ada data perizinan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
