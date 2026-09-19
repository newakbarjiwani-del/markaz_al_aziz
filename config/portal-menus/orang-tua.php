<?php

return [
    ['label' => 'Beranda', 'route' => 'portal.ortu.dashboard', 'icon' => 'home'],

    ['label' => 'Data Anak', 'route' => 'portal.ortu.anak', 'icon' => 'users-group'],

    [
        'label' => 'Keuangan',
        'icon' => 'receipt-2',
        'children' => [
            ['label' => 'Tagihan', 'route' => 'portal.ortu.tagihan.index'],
            ['label' => 'Saldo Keuangan', 'route' => 'portal.ortu.pembayaran.index'],
            ['label' => 'Pindah Saldo', 'route' => 'portal.ortu.pindah-saldo'],
        ],
    ],

    [
        'label' => 'Cashless',
        'icon' => 'wallet',
        'children' => [
            ['label' => 'Saldo & Transaksi', 'route' => 'portal.ortu.dompet.index'],
            ['label' => 'PIN Cashless', 'route' => 'portal.ortu.pin-cashless.index'],
        ],
    ],

    [
        'label' => 'Absensi & Perizinan',
        'icon' => 'calendar-event',
        'children' => [
            ['label' => 'Riwayat Absensi', 'route' => 'portal.ortu.absensi.index'],
            ['label' => 'Rekap Presensi', 'route' => 'portal.ortu.rekap-presensi.index'],
            ['label' => 'Rekap Perizinan', 'route' => 'portal.ortu.rekap-perizinan.index'],
        ],
    ],

    ['label' => 'Perpustakaan', 'route' => 'portal.ortu.perpustakaan.index', 'icon' => 'books'],

    ['label' => 'Rapor', 'route' => 'portal.ortu.rapor.index', 'icon' => 'report-analytics'],

    ['label' => 'Booklet Sekolah', 'route' => 'portal.ortu.booklet.index', 'icon' => 'book'],

    ['label' => 'Tahfidz', 'route' => 'portal.ortu.tahfidz.index', 'icon' => 'book-2'],

    [
        'label' => 'Prestasi & Pelanggaran',
        'icon' => 'award',
        'children' => [
            ['label' => 'Prestasi Siswa', 'route' => 'portal.ortu.prestasi-siswa.index'],
        ],
    ],

    ['label' => 'Kartu Pelajar', 'route' => 'portal.ortu.kartu-pelajar', 'icon' => 'id-badge-2'],

    ['label' => 'Link Login', 'route' => 'portal.ortu.akses-token.index', 'icon' => 'key'],
];
