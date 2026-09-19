@extends('layouts.app')

@section('title', 'Dashboard Prestasi & Pelanggaran')

@section('content')
@php
    $quickActions = [
        ['label' => 'Prestasi Siswa', 'route' => 'admin.prestasi-pelanggaran.prestasi-siswa.index', 'icon' => 'award', 'tone' => 'success'],
        ['label' => 'Pelanggaran Siswa', 'route' => 'admin.prestasi-pelanggaran.pelanggaran-siswa.index', 'icon' => 'alert-triangle', 'tone' => 'danger'],
        ['label' => 'Prestasi Guru', 'route' => 'admin.prestasi-pelanggaran.prestasi-guru.index', 'icon' => 'medal', 'tone' => 'success'],
        ['label' => 'Pelanggaran Guru', 'route' => 'admin.prestasi-pelanggaran.pelanggaran-guru.index', 'icon' => 'gavel', 'tone' => 'danger'],
        ['label' => 'Hukuman', 'route' => 'admin.prestasi-pelanggaran.hukuman-siswa.index', 'icon' => 'scale', 'tone' => 'warning'],
        ['label' => 'Rekap Prestasi', 'route' => 'admin.prestasi-pelanggaran.rekap-prestasi-siswa.index', 'icon' => 'chart-bar', 'tone' => 'success'],
        ['label' => 'Rekap Pelanggaran', 'route' => 'admin.prestasi-pelanggaran.rekap-pelanggaran-siswa.index', 'icon' => 'chart-dots', 'tone' => 'danger'],
        ['label' => 'Katalog Prestasi', 'route' => 'admin.prestasi-pelanggaran.katalog-prestasi.index', 'icon' => 'list-check', 'tone' => 'neutral'],
        ['label' => 'Katalog Pelanggaran', 'route' => 'admin.prestasi-pelanggaran.katalog-pelanggaran.index', 'icon' => 'list-details', 'tone' => 'neutral'],
        ['label' => 'Import Prestasi', 'route' => 'admin.prestasi-pelanggaran.impor-prestasi', 'icon' => 'file-import', 'tone' => 'neutral'],
        ['label' => 'Import Pelanggaran', 'route' => 'admin.prestasi-pelanggaran.impor-pelanggaran', 'icon' => 'file-spreadsheet', 'tone' => 'neutral'],
    ];
@endphp

<div class="dashboard-page pp-dashboard">
    <h1 class="pp-dashboard__title">Dashboard Prestasi &amp; Pelanggaran</h1>

    <x-dashboard.launcher :actions="$quickActions" />

    <div class="dashboard-stats pp-dashboard__stats">
        @foreach($stats as $stat)
            <x-stat-card
                :label="$stat['label']"
                :value="$stat['value']"
                :accent="$stat['accent']"
                :icon-name="$stat['icon'] ?? null"
            />
        @endforeach
    </div>

    <section class="pp-dashboard__section" aria-labelledby="pp-rank-heading">
        <div class="pp-dashboard__section-head">
            <div>
                <h2 id="pp-rank-heading" class="pp-dashboard__section-title">Rekap Terbanyak</h2>
                <p class="pp-dashboard__section-desc">Top 5 siswa &amp; guru berdasarkan jumlah catatan (poin sebagai tie-break).</p>
            </div>
        </div>
        <div class="pp-dashboard__rank-grid">
            <x-prestasi-pelanggaran.top-ranking-panel
                title="Prestasi Siswa"
                icon="award"
                tone="success"
                :rows="$topPrestasiSiswa"
                empty="Belum ada data prestasi siswa." />
            <x-prestasi-pelanggaran.top-ranking-panel
                title="Pelanggaran Siswa"
                icon="gavel"
                tone="danger"
                :rows="$topPelanggaranSiswa"
                empty="Belum ada data pelanggaran siswa." />
            <x-prestasi-pelanggaran.top-ranking-panel
                title="Prestasi Guru"
                icon="award"
                tone="success"
                :rows="$topPrestasiGuru"
                empty="Belum ada data prestasi guru." />
            <x-prestasi-pelanggaran.top-ranking-panel
                title="Pelanggaran Guru"
                icon="gavel"
                tone="danger"
                :rows="$topPelanggaranGuru"
                empty="Belum ada data pelanggaran guru." />
        </div>
    </section>

    <section class="pp-dashboard__section" aria-labelledby="pp-feed-heading">
        <div class="pp-dashboard__section-head">
            <div>
                <h2 id="pp-feed-heading" class="pp-dashboard__section-title">Aktivitas Terbaru</h2>
                <p class="pp-dashboard__section-desc">Catatan terbaru per kategori — bukan ringkasan ulang dari kartu di atas.</p>
            </div>
        </div>
        <div class="pp-dashboard__feed-grid">
            <x-prestasi-pelanggaran.recent-feed
                title="Prestasi Siswa"
                tone="success"
                :href="route('admin.prestasi-pelanggaran.prestasi-siswa.index')"
                :rows="$recentPrestasiSiswa"
                empty="Belum ada prestasi siswa." />
            <x-prestasi-pelanggaran.recent-feed
                title="Pelanggaran Siswa"
                tone="danger"
                :href="route('admin.prestasi-pelanggaran.pelanggaran-siswa.index')"
                :rows="$recentPelanggaranSiswa"
                empty="Belum ada pelanggaran siswa." />
            <x-prestasi-pelanggaran.recent-feed
                title="Prestasi Guru"
                tone="success"
                :href="route('admin.prestasi-pelanggaran.prestasi-guru.index')"
                :rows="$recentPrestasiGuru"
                empty="Belum ada prestasi guru." />
            <x-prestasi-pelanggaran.recent-feed
                title="Pelanggaran Guru"
                tone="danger"
                :href="route('admin.prestasi-pelanggaran.pelanggaran-guru.index')"
                :rows="$recentPelanggaranGuru"
                empty="Belum ada pelanggaran guru." />
        </div>
    </section>
</div>
@endsection
