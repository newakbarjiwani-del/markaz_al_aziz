<?php

return [
    'admin' => [
        ['label' => 'Beranda', 'route' => 'admin.dashboard', 'icon' => 'home'],
        ['label' => 'Siswa', 'route' => 'admin.manajemen-siswa.dashboard', 'icon' => 'school', 'active' => 'admin.manajemen-siswa.*'],
        ['label' => 'Keuangan', 'route' => 'admin.keuangan.dashboard', 'icon' => 'coin', 'active' => 'admin.keuangan.*'],
        ['label' => 'Absensi', 'route' => 'admin.absensi.dashboard', 'icon' => 'calendar-check', 'active' => 'admin.absensi.*'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],

    'bendahara' => [
        ['label' => 'Keuangan', 'route' => 'admin.keuangan.dashboard', 'icon' => 'coin', 'active' => 'admin.keuangan.*'],
        ['label' => 'Absensi', 'route' => 'admin.absensi.dashboard', 'icon' => 'calendar-check', 'active' => 'admin.absensi.*'],
        ['label' => 'Profil', 'route' => 'profile.show', 'icon' => 'user'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],

    'cashless' => [
        ['label' => 'Cashless', 'route' => 'admin.dompet-digital.dashboard', 'icon' => 'wallet', 'active' => 'admin.dompet-digital.*'],
        ['label' => 'Saldo', 'route' => 'admin.dompet-digital.saldo-cashless.index', 'icon' => 'coin', 'active' => 'admin.dompet-digital.saldo-cashless*'],
        ['label' => 'Profil', 'route' => 'profile.show', 'icon' => 'user'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],

    'orang_tua' => [
        ['label' => 'Beranda', 'route' => 'portal.ortu.dashboard', 'icon' => 'home'],
        ['label' => 'Anak', 'route' => 'portal.ortu.anak', 'icon' => 'users'],
        ['label' => 'Tagihan', 'route' => 'portal.ortu.tagihan.index', 'icon' => 'receipt-2', 'active' => 'portal.ortu.tagihan.*'],
        ['label' => 'Keuangan', 'route' => 'portal.ortu.pembayaran.index', 'icon' => 'coin', 'active' => ['portal.ortu.pembayaran.*', 'portal.ortu.pindah-saldo*']],
        ['label' => 'Cashless', 'route' => 'portal.ortu.dompet.index', 'icon' => 'wallet', 'active' => ['portal.ortu.dompet.*', 'portal.ortu.pin-cashless*']],
    ],

    'guru' => [
        ['label' => 'Beranda', 'route' => 'portal.guru.dashboard', 'icon' => 'home'],
        ['label' => 'Absensi', 'route' => 'portal.guru.absensi.index', 'icon' => 'calendar-check', 'active' => 'portal.guru.absensi.*'],
        ['label' => 'Absensi Siswa', 'route' => 'portal.guru.absensi-siswa.index', 'icon' => 'camera', 'active' => ['portal.guru.absensi-siswa.*', 'portal.guru.rekap-siswa.*']],
        ['label' => 'Profil', 'route' => 'portal.guru.profil', 'icon' => 'user'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],

    'siswa' => [
        ['label' => 'Beranda', 'route' => 'portal.siswa.dashboard', 'icon' => 'home'],
        ['label' => 'Tagihan', 'route' => 'portal.siswa.tagihan.index', 'icon' => 'coin', 'active' => ['portal.siswa.tagihan.*', 'portal.siswa.pembayaran.*']],
        ['label' => 'Absensi', 'route' => 'portal.siswa.absensi.index', 'icon' => 'calendar-check', 'active' => 'portal.siswa.absensi.*'],
        ['label' => 'Cashless', 'route' => 'portal.siswa.dompet.index', 'icon' => 'wallet', 'active' => 'portal.siswa.dompet.*'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],

    'kantin' => [
        ['label' => 'Beranda', 'route' => 'portal.kantin.dashboard', 'icon' => 'home'],
        ['label' => 'Belanja', 'route' => 'portal.kantin.pos', 'icon' => 'nfc'],
        ['label' => 'Transaksi', 'route' => 'portal.kantin.transaksi.index', 'icon' => 'receipt', 'active' => 'portal.kantin.transaksi.*'],
        ['label' => 'Profil', 'route' => 'profile.show', 'icon' => 'user'],
    ],

    'pimpinan' => [
        ['label' => 'Beranda', 'route' => 'portal.pimpinan.dashboard', 'icon' => 'home'],
        ['label' => 'Absensi', 'route' => 'portal.pimpinan.laporan-absensi-siswa', 'icon' => 'calendar-check', 'active' => ['portal.pimpinan.laporan-absensi-siswa*', 'portal.pimpinan.laporan-absensi-guru*', 'portal.pimpinan.rekap-presensi*', 'portal.pimpinan.rekap-presensi-guru*']],
        ['label' => 'Keuangan', 'route' => 'portal.pimpinan.laporan-keuangan', 'icon' => 'coin', 'active' => 'portal.pimpinan.laporan-keuangan*'],
        ['label' => 'Kantin', 'route' => 'portal.pimpinan.laporan-kantin', 'icon' => 'wallet', 'active' => 'portal.pimpinan.laporan-kantin*'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],

    'perpustakaan' => [
        ['label' => 'Beranda', 'route' => 'portal.perpustakaan.dashboard', 'icon' => 'home'],
        ['label' => 'Katalog', 'route' => 'portal.perpustakaan.katalog-buku.index', 'icon' => 'books', 'active' => ['portal.perpustakaan.katalog-buku.*', 'portal.perpustakaan.cari-buku']],
        ['label' => 'Peminjaman', 'route' => 'portal.perpustakaan.peminjaman.index', 'icon' => 'book-upload', 'active' => ['portal.perpustakaan.peminjaman.*', 'portal.perpustakaan.pengembalian-buku*', 'portal.perpustakaan.riwayat-peminjaman*']],
        ['label' => 'Denda', 'route' => 'portal.perpustakaan.denda-keterlambatan', 'icon' => 'alert-circle', 'active' => 'portal.perpustakaan.denda-keterlambatan*'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],

    'perizinan' => [
        ['label' => 'Beranda', 'route' => 'portal.perizinan.dashboard', 'icon' => 'home'],
        ['label' => 'Keluar Masuk', 'route' => 'portal.perizinan.keluar-masuk.index', 'icon' => 'door-exit', 'active' => 'portal.perizinan.keluar-masuk.*'],
        ['label' => 'Pondok', 'route' => 'portal.perizinan.keluar-masuk-pondok.index', 'icon' => 'building-community', 'active' => 'portal.perizinan.keluar-masuk-pondok.*'],
        ['label' => 'Rekap', 'route' => 'portal.perizinan.rekap-laporan', 'icon' => 'clipboard-check', 'active' => 'portal.perizinan.rekap-laporan*'],
        ['label' => 'Menu', 'icon' => 'menu-2', 'action' => 'sidebar'],
    ],
];
