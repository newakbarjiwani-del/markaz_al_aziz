@extends('layouts.app')

@section('title', '')

@section('content')
@php
    $siswaHadir = collect($attendanceStats)->firstWhere('label', 'Siswa Hadir')['value'] ?? 0;
@endphp

<div class="portal-dashboard dashboard-page">
    <x-portal.dashboard-hero
        :name="auth()->user()->name"
        subtitle="Dashboard Pimpinan — ringkasan absensi, keuangan, dan kantin"
        :tip="'Hari ini '.$siswaHadir.' siswa hadir. Pantau laporan lengkap melalui menu di bawah.'"
        highlight-label="Penerimaan SPP Bulan Ini"
        :highlight-value="collect($financeStats)->firstWhere('label', 'Penerimaan Bulan Ini')['value'] ?? '-'"
        highlight-hint="Tidak termasuk pembayaran yang dibatalkan."
        tone="primary"
        :chips="[
            ['label' => 'Siswa Hadir', 'value' => $siswaHadir],
            ['label' => 'Siswa Alpha', 'value' => collect($attendanceStats)->firstWhere('label', 'Siswa Alpha')['value'] ?? 0],
            ['label' => 'Guru Hadir', 'value' => collect($attendanceStats)->firstWhere('label', 'Guru Hadir')['value'] ?? 0],
            ['label' => 'Tunggakan SPP', 'value' => collect($financeStats)->firstWhere('label', 'Tunggakan SPP')['value'] ?? '-'],
        ]"
    />

    <div class="mb-6">
        <x-perizinan.rekap-card :summary="$perizinanSummary" title="Rekap Perizinan Pondok & Sekolah" :show-link="false" />
    </div>

    <x-portal.report-grid :reports="[
        ['label' => 'Laporan Absensi Siswa', 'description' => 'Detail kehadiran siswa per periode dan kelas.', 'route' => 'portal.pimpinan.laporan-absensi-siswa', 'icon' => 'clipboard-check', 'tone' => 'primary'],
        ['label' => 'Laporan Absensi Guru', 'description' => 'Rekap kehadiran guru per tanggal.', 'route' => 'portal.pimpinan.laporan-absensi-guru', 'icon' => 'chalkboard', 'tone' => 'green'],
        ['label' => 'Rekap Presensi Siswa', 'description' => 'Ringkasan hadir, alpha, dan izin per bulan.', 'route' => 'portal.pimpinan.rekap-presensi', 'icon' => 'report-analytics', 'tone' => 'blue'],
        ['label' => 'Laporan Keuangan', 'description' => 'Penerimaan SPP, tagihan, dan tren pembayaran.', 'route' => 'portal.pimpinan.laporan-keuangan', 'icon' => 'file-invoice', 'tone' => 'accent'],
        ['label' => 'Laporan Kantin', 'description' => 'Riwayat transaksi dompet kantin siswa.', 'route' => 'portal.pimpinan.laporan-kantin', 'icon' => 'tools-kitchen-2', 'tone' => 'purple'],
    ]" />
</div>
@endsection
