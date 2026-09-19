<?php

return [
    ['label' => 'Beranda', 'route' => 'portal.siswa.dashboard', 'icon' => 'home'],

    ['label' => 'Profil Saya', 'route' => 'portal.siswa.profil', 'icon' => 'address-book'],

    ['label' => 'Kartu Pelajar', 'route' => 'portal.siswa.kartu-pelajar', 'icon' => 'id-badge-2'],

    [
        'label' => 'Keuangan',
        'icon' => 'receipt-2',
        'children' => [
            ['label' => 'Tagihan', 'route' => 'portal.siswa.tagihan.index'],
            ['label' => 'Saldo Keuangan', 'route' => 'portal.siswa.pembayaran.index'],
        ],
    ],

    [
        'label' => 'Cashless',
        'icon' => 'wallet',
        'children' => [
            ['label' => 'Saldo & Transaksi', 'route' => 'portal.siswa.dompet.index'],
        ],
    ],

    ['label' => 'Perpustakaan', 'route' => 'portal.siswa.perpustakaan.index', 'icon' => 'books'],

    ['label' => 'Rapor', 'route' => 'portal.siswa.rapor.index', 'icon' => 'report-analytics'],

    ['label' => 'Ujian Online', 'route' => 'portal.siswa.ujian.index', 'icon' => 'clipboard-list'],

    ['label' => 'Booklet Sekolah', 'route' => 'portal.siswa.booklet.index', 'icon' => 'book'],

    ['label' => 'Tahfidz', 'route' => 'portal.siswa.tahfidz.index', 'icon' => 'book-2'],

    [
        'label' => 'Prestasi & Pelanggaran',
        'icon' => 'award',
        'children' => [
            ['label' => 'Prestasi Saya', 'route' => 'portal.siswa.prestasi-siswa.index'],
            ['label' => 'Pelanggaran Saya', 'route' => 'portal.siswa.pelanggaran-siswa.index'],
        ],
    ],

    ['label' => 'Link Login', 'route' => 'portal.siswa.akses-token.index', 'icon' => 'key'],
];
