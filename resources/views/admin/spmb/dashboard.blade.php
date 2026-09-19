@extends('layouts.app')

@section('title', $title)

@section('content')

<div class="dashboard-page">
    <x-dashboard.launcher
        class="mb-6"
        :actions="[
            ['label' => 'Periode', 'route' => 'admin.spmb.periode.index', 'icon' => 'calendar', 'tone' => 'primary'],
            ['label' => 'Pendaftar', 'route' => 'admin.spmb.pendaftar.index', 'icon' => 'users', 'tone' => 'info'],
            ['label' => 'Pengumuman', 'route' => 'admin.spmb.pengumuman.index', 'icon' => 'megaphone', 'tone' => 'warning'],
            ['label' => 'Berita', 'route' => 'admin.spmb.berita.index', 'icon' => 'newspaper', 'tone' => 'success'],
            ['label' => 'Galeri', 'route' => 'admin.spmb.galeri.index', 'icon' => 'photo', 'tone' => 'purple'],
        ]"
    />

    @if($periodeOpen)
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-100">
            Periode terbuka: <strong>{{ $periodeOpen->name }}</strong>
            @if($periodeOpen->closes_at)
                · ditutup {{ $periodeOpen->closes_at->translatedFormat('d M Y H:i') }}
            @endif
        </div>
    @endif

    <div class="mb-6">
        <h2 class="mb-4 text-lg font-semibold text-slate-900 dark:text-white">Ringkasan SPMB</h2>
        <div class="dashboard-stats">
            @foreach($stats as $stat)
                <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-800">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pendaftar Terbaru</h2>
                <p class="text-sm text-slate-500">8 pendaftaran terakhir</p>
            </div>
            <a href="{{ route('admin.spmb.pendaftar.index') }}" class="btn-secondary text-sm">Lihat Semua</a>
        </div>
        <div class="table-scroll">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Nomor</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">Periode</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-600">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPendaftar as $row)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $row->nomor_pendaftaran }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.spmb.pendaftar.show', $row) }}" class="font-medium text-primary-700 hover:underline dark:text-primary-300">{{ $row->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->periode?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">{{ $row->statusLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada pendaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
