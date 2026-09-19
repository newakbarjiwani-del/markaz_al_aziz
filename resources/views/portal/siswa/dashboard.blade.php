@extends('layouts.app')

@section('title', '')

@section('content')
<div class="portal-dashboard dashboard-page">
    <x-portal.dashboard-hero
        :name="$siswa->name"
        :subtitle="'NIS '.$siswa->nis.' · '.($siswa->kelas?->name ?? 'Belum ada kelas')"
        :badge="ucfirst($siswa->status)"
        :tip="$stats['tagihan_belum_lunas'] > 0
            ? 'Anda memiliki '.$stats['tagihan_belum_lunas'].' tagihan belum lunas. Segera selesaikan pembayaran ya.'
            : 'Semua tagihan Anda sudah lunas. Terima kasih!'"
        highlight-label="Pembayaran Bulan Ini"
        :highlight-value="'Rp '.number_format($stats['pembayaran_bulan_ini'], 0, ',', '.')"
        :chips="[
            ['label' => 'Kehadiran Bulan Ini', 'value' => $stats['hadir_bulan_ini'].' hari'],
            ['label' => 'Saldo Cashless', 'value' => 'Rp '.number_format($stats['saldo_cashless'], 0, ',', '.')],
            ['label' => 'Tagihan Aktif', 'value' => $stats['tagihan_belum_lunas']],
        ]"
    />

    <x-portal.quick-actions
        title="Akses Cepat"
        description="Kelola keuangan, absensi, dan dompet digital Anda."
        :actions="[
            ['label' => 'Tagihan', 'route' => 'portal.siswa.tagihan.index', 'icon' => 'receipt-2', 'tone' => 'accent'],
            ['label' => 'Absensi', 'route' => 'portal.siswa.absensi.index', 'icon' => 'calendar-event', 'tone' => 'green'],
            ['label' => 'Saldo Cashless', 'route' => 'portal.siswa.dompet.index', 'icon' => 'wallet', 'tone' => 'blue'],
            ['label' => 'Kartu Pelajar', 'route' => 'portal.siswa.kartu-pelajar', 'icon' => 'id-badge-2', 'tone' => 'primary'],
        ]"
    />

    <x-portal.recent-tagihan-table :tagihan="$recentTagihan">
        <x-portal.panel-head
            title="Tagihan Terbaru"
            subtitle="Riwayat tagihan SPP Anda"
            :action-url="route('portal.siswa.tagihan.index')"
            action-label="Lihat Semua"
        />
    </x-portal.recent-tagihan-table>
</div>
@endsection
