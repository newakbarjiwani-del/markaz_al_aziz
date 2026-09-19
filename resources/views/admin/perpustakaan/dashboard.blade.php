@extends('layouts.app')

@section('title', $title ?? '')

@section('content')
@php
    $isPortal = ! empty($isPortal);
@endphp
<div @class(['dashboard-page', 'portal-dashboard' => $isPortal])>
    @if($isPortal)
        @php
            $statMap = collect($stats)->keyBy('label');
        @endphp
        <x-portal.dashboard-hero
            :name="auth()->user()->name"
            subtitle="Portal Perpustakaan — kelola katalog dan sirkulasi buku"
            :tip="($statMap->get('Terlambat')['value'] ?? 0) > 0
                ? 'Ada '.$statMap->get('Terlambat')['value'].' peminjaman terlambat yang perlu ditindaklanjuti.'
                : 'Semua peminjaman berjalan lancar. Selamat bekerja!'"
            highlight-label="Sedang Dipinjam"
            :highlight-value="$statMap->get('Sedang Dipinjam')['value'] ?? 0"
            tone="green"
            :chips="[
                ['label' => 'Total Buku', 'value' => $statMap->get('Total Buku')['value'] ?? 0],
                ['label' => 'Tersedia', 'value' => $statMap->get('Tersedia')['value'] ?? 0],
                ['label' => 'Dipinjam', 'value' => $statMap->get('Sedang Dipinjam')['value'] ?? 0],
                ['label' => 'Terlambat', 'value' => $statMap->get('Terlambat')['value'] ?? 0],
            ]"
        />

        <x-portal.quick-actions
            title="Akses Cepat"
            description="Kelola peminjaman, pengembalian, dan katalog buku."
            :actions="[
                ['label' => 'Katalog', 'route' => 'portal.perpustakaan.katalog-buku.index', 'icon' => 'books', 'tone' => 'primary'],
                ['label' => 'Peminjaman', 'route' => 'portal.perpustakaan.peminjaman.index', 'icon' => 'book-upload', 'tone' => 'green'],
                ['label' => 'Pengembalian', 'route' => 'portal.perpustakaan.pengembalian-buku', 'icon' => 'book-download', 'tone' => 'blue'],
                ['label' => 'Cari Buku', 'route' => 'portal.perpustakaan.cari-buku', 'icon' => 'search', 'tone' => 'accent'],
            ]"
        />
    @else
        <x-dashboard.launcher
            class="mb-6"
            :actions="[
                ['label' => 'Katalog Buku', 'route' => 'admin.perpustakaan.katalog-buku.index', 'icon' => 'books', 'tone' => 'primary'],
                ['label' => 'Peminjaman', 'route' => 'admin.perpustakaan.peminjaman.index', 'icon' => 'book-upload', 'tone' => 'success'],
                ['label' => 'Pengembalian', 'route' => 'admin.perpustakaan.pengembalian-buku', 'icon' => 'book-download', 'tone' => 'info'],
                ['label' => 'History', 'route' => 'admin.perpustakaan.riwayat-peminjaman', 'icon' => 'history', 'tone' => 'warning'],
                ['label' => 'Denda', 'route' => 'admin.perpustakaan.denda-keterlambatan', 'icon' => 'cash', 'tone' => 'danger'],
                ['label' => 'Cari Buku', 'route' => 'admin.perpustakaan.cari-buku', 'icon' => 'search', 'tone' => 'purple'],
                ['label' => 'Import Buku', 'route' => 'admin.perpustakaan.impor-buku', 'icon' => 'file-import', 'tone' => 'neutral'],
                ['label' => 'Setting Denda', 'route' => 'admin.perpustakaan.setting-denda.index', 'icon' => 'settings', 'tone' => 'neutral'],
            ]"
        />
    @endif

    <div class="dashboard-stats">
        @foreach($stats as $stat)
            <x-stat-card :label="$stat['label']" :value="$stat['value']" :accent="$stat['accent']" />
        @endforeach
    </div>

    @include('admin.partials.dashboard-charts', ['charts' => $charts ?? []])

    <div class="card dashboard-table-card">
        <div class="border-b border-slate-200 p-4 dark:border-slate-800">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Peminjaman Terbaru</h2>
        </div>
        <div class="table-scroll">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
                        <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Peminjam</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600 dark:text-slate-400">Buku</th>
                        <th class="px-4 py-3 text-center font-medium text-slate-600 dark:text-slate-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPeminjaman as $row)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $row->borrowerDisplayLabel() }}</td>
                            <td class="px-4 py-3">{{ $row->buku?->judul ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">{{ ucfirst($row->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">Belum ada peminjaman.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
