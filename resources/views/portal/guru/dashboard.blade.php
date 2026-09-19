@extends('layouts.app')

@section('title', '')

@section('content')
<div class="portal-dashboard dashboard-page">
    <x-portal.dashboard-hero
        :name="$guru->name"
        :subtitle="($guru->jabatan ?? 'Guru').' · NIP '.($guru->nip ?? '-')"
        :tip="$stats['kelas_mengajar'] > 0
            ? 'Hari ini '.$stats['hadir_siswa_hari_ini'].' siswa hadir dari jadwal absen yang Anda ampu.'
            : 'Belum ada jadwal absen aktif untuk Anda. Hubungi admin untuk mengatur jadwal dan kelas.'"
        highlight-label="Kehadiran Saya Bulan Ini"
        :highlight-value="$stats['absensi_saya_bulan_ini'].' hari'"
        tone="blue"
        :chips="[
            ['label' => 'Kelas / Penugasan', 'value' => $stats['kelas_mengajar']],
            ['label' => 'Siswa Hadir Hari Ini', 'value' => $stats['hadir_siswa_hari_ini']],
            ['label' => 'Kehadiran Bulan Ini', 'value' => $stats['absensi_saya_bulan_ini'].' hari'],
            ['label' => 'Status', 'value' => ucfirst($guru->status ?? 'aktif')],
        ]"
    />

    <x-portal.quick-actions
        title="Akses Cepat"
        description="Absensi siswa, rekap kelas, dan profil guru."
        :actions="[
            ['label' => 'Absensi Siswa', 'route' => 'portal.guru.absensi-siswa.index', 'icon' => 'camera', 'tone' => 'green'],
            ['label' => 'Rekap Siswa', 'route' => 'portal.guru.rekap-siswa.index', 'icon' => 'clipboard-list', 'tone' => 'blue'],
            ['label' => 'Absensi Saya', 'route' => 'portal.guru.absensi.index', 'icon' => 'user-check', 'tone' => 'primary'],
            ['label' => 'Kartu Guru', 'route' => 'portal.guru.kartu-guru', 'icon' => 'id-badge-2', 'tone' => 'accent'],
        ]"
    />

    @if($classes !== [])
    <div class="card portal-dashboard-panel mb-6">
        <x-portal.panel-head
            title="Kelas & Penugasan Absensi"
            subtitle="Kelas yang terhubung ke jadwal absen Anda (bisa lebih dari satu kelas per jadwal)."
        />
        <div class="portal-dashboard-panel__body flex flex-wrap gap-2">
            @foreach($classes as $class)
                <span class="badge badge-green">{{ $class }}</span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mt-6">
        <x-perizinan.rekap-card :summary="$perizinanSummary" title="Rekap Perizinan Siswa" :show-link="false" />
    </div>
</div>
@endsection
