<?php

return [
    ['label' => 'Beranda', 'route' => 'admin.dashboard', 'icon' => 'home'],

    ['label' => 'Super Admin', 'route' => 'super-admin.dashboard', 'icon' => 'shield', 'roles' => ['super_admin']],

    ['label' => 'Manajemen User', 'route' => 'super-admin.users.index', 'icon' => 'user-cog', 'roles' => ['super_admin']],
    ['label' => 'Manajemen User', 'route' => 'admin.manajemen-user.index', 'icon' => 'user-cog', 'roles' => ['admin']],

    ['label' => 'Log Login', 'route' => 'super-admin.login-logs.index', 'icon' => 'history', 'roles' => ['super_admin']],

    ['label' => 'UI Kit', 'route' => 'admin.komponen-ui', 'icon' => 'palette', 'roles' => ['super_admin'], 'local_only' => true],

    [
        'label' => 'Master Data',
        'icon' => 'database',
        'children' => [
            ['label' => 'Sekolah', 'route' => 'admin.master-data.sekolah.index'],
            ['label' => 'Kelas', 'route' => 'admin.master-data.kelas.index'],
            ['label' => 'Tahun Akademik', 'route' => 'admin.master-data.tahun-akademik.index'],
            ['label' => 'Jenis Tagihan', 'route' => 'admin.master-data.jenis-tagihan.index'],
            ['label' => 'Kamar', 'route' => 'admin.master-data.kamar.index'],
            ['label' => 'Status Santri', 'route' => 'admin.master-data.status-santri.index'],
        ],
    ],

    [
        'label' => 'Manajemen Siswa',
        'icon' => 'school',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.manajemen-siswa.dashboard'],
            ['label' => 'Data Siswa', 'route' => 'admin.manajemen-siswa.data-siswa.index'],
            ['label' => 'Profil Siswa', 'route' => 'admin.manajemen-siswa.profil-siswa'],
            ['label' => 'Orang Tua', 'route' => 'admin.manajemen-siswa.orang-tua.index'],
            ['label' => 'Riwayat Akademik', 'route' => 'admin.manajemen-siswa.riwayat-akademik'],
            ['label' => 'Berkas Siswa', 'route' => 'admin.manajemen-siswa.berkas-siswa'],
            ['label' => 'Pindah Kelas', 'route' => 'admin.manajemen-siswa.pindah-kelas'],
            ['label' => 'Kartu Pelajar', 'route' => 'admin.manajemen-siswa.kartu-pelajar'],
            ['label' => 'Import/Export', 'route' => 'admin.manajemen-siswa.impor-ekspor'],
        ],
    ],

    [
        'label' => 'Manajemen Guru',
        'icon' => 'chalkboard',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.manajemen-guru.dashboard'],
            ['label' => 'Data Guru', 'route' => 'admin.manajemen-guru.data-guru.index'],
            ['label' => 'Profil Guru', 'route' => 'admin.manajemen-guru.profil-guru'],
            ['label' => 'Kartu Guru', 'route' => 'admin.manajemen-guru.kartu-guru'],
            ['label' => 'Riwayat Mengajar', 'route' => 'admin.manajemen-guru.riwayat-mengajar.index'],
            ['label' => 'Import/Export', 'route' => 'admin.manajemen-guru.impor-ekspor'],
        ],
    ],

    [
        'label' => 'SPMB',
        'icon' => 'user-plus',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.spmb.dashboard'],
            ['label' => 'Periode', 'route' => 'admin.spmb.periode.index'],
            ['label' => 'Pendaftar', 'route' => 'admin.spmb.pendaftar.index'],
            ['label' => 'Pengumuman', 'route' => 'admin.spmb.pengumuman.index'],
            ['label' => 'Berita', 'route' => 'admin.spmb.berita.index'],
            ['label' => 'Galeri', 'route' => 'admin.spmb.galeri.index'],
        ],
    ],

    [
        'label' => 'Akademik',
        'icon' => 'book',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.akademik.dashboard'],
            ['label' => 'Mata Pelajaran', 'route' => 'admin.akademik.mata-pelajaran.index'],
            ['label' => 'Kurikulum', 'route' => 'admin.akademik.kurikulum.index'],
            ['label' => 'Jadwal Pelajaran', 'route' => 'admin.akademik.jadwal-pelajaran.index'],
            ['label' => 'Kalender Pendidikan', 'route' => 'admin.akademik.kalender.index'],
            ['label' => 'Nilai', 'route' => 'admin.akademik.nilai.index'],
            ['label' => 'Rapor', 'route' => 'admin.akademik.rapor.index'],
            ['label' => 'Laporan Nilai', 'route' => 'admin.akademik.laporan-nilai.index'],
        ],
    ],

    [
        'label' => 'Ujian Online',
        'icon' => 'clipboard-list',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.ujian.dashboard'],
            ['label' => 'Daftar Ujian', 'route' => 'admin.ujian.ujian.index'],
        ],
    ],

    [
        'label' => 'Booklet Sekolah',
        'icon' => 'book',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.booklet.dashboard'],
            ['label' => 'Daftar Booklet', 'route' => 'admin.booklet.booklet.index'],
        ],
    ],

    [
        'label' => 'Alumni',
        'icon' => 'users',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.alumni.dashboard'],
            ['label' => 'Data Alumni', 'route' => 'admin.alumni.alumni.index'],
            ['label' => 'Tracer Study', 'route' => 'admin.alumni.tracer.index'],
        ],
    ],

    [
        'label' => 'Tahfidz',
        'icon' => 'book-2',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.tahfidz.dashboard'],
            ['label' => 'Progress', 'route' => 'admin.tahfidz.progress.index'],
            ['label' => 'Target', 'route' => 'admin.tahfidz.target.index'],
            ['label' => 'Jadwal', 'route' => 'admin.tahfidz.jadwal.index'],
        ],
    ],

    [
        'label' => 'Prestasi & Pelanggaran',
        'icon' => 'award',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.prestasi-pelanggaran.dashboard'],
            ['label' => 'Katalog Prestasi', 'route' => 'admin.prestasi-pelanggaran.katalog-prestasi.index'],
            ['label' => 'Katalog Pelanggaran', 'route' => 'admin.prestasi-pelanggaran.katalog-pelanggaran.index'],
            ['label' => 'Prestasi Siswa', 'route' => 'admin.prestasi-pelanggaran.prestasi-siswa.index'],
            ['label' => 'Rekap Prestasi Siswa', 'route' => 'admin.prestasi-pelanggaran.rekap-prestasi-siswa.index'],
            ['label' => 'Pelanggaran Siswa', 'route' => 'admin.prestasi-pelanggaran.pelanggaran-siswa.index'],
            ['label' => 'Rekap Pelanggaran Siswa', 'route' => 'admin.prestasi-pelanggaran.rekap-pelanggaran-siswa.index'],
            ['label' => 'Hukuman Siswa', 'route' => 'admin.prestasi-pelanggaran.hukuman-siswa.index'],
            ['label' => 'Prestasi Guru', 'route' => 'admin.prestasi-pelanggaran.prestasi-guru.index'],
            ['label' => 'Pelanggaran Guru', 'route' => 'admin.prestasi-pelanggaran.pelanggaran-guru.index'],
            ['label' => 'Import Prestasi', 'route' => 'admin.prestasi-pelanggaran.impor-prestasi'],
            ['label' => 'Import Pelanggaran', 'route' => 'admin.prestasi-pelanggaran.impor-pelanggaran'],
        ],
    ],

    [
        'label' => 'Keuangan',
        'icon' => 'coin',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.keuangan.dashboard'],
            ['label' => 'Tagihan', 'route' => 'admin.keuangan.tagihan.index'],
            ['label' => 'Kirim Tagihan WA', 'route' => 'admin.keuangan.kirim-tagihan-wa.index'],
            ['label' => 'Template Pesan WA', 'route' => 'admin.keuangan.template-pesan-tagihan.index'],
            ['label' => 'Katalog Potongan', 'route' => 'admin.keuangan.katalog-potongan.index'],
            ['label' => 'Potongan Siswa', 'route' => 'admin.keuangan.potongan-siswa.index'],
            ['label' => 'Riwayat Pemakaian Potongan', 'route' => 'admin.keuangan.potongan-pemakaian.index'],
            ['label' => 'Import Tagihan', 'route' => 'admin.keuangan.impor-tagihan'],
            ['label' => 'Pembayaran', 'route' => 'admin.keuangan.pembayaran.index'],
            ['label' => 'Riwayat Pembayaran', 'route' => 'admin.keuangan.riwayat-pembayaran.index'],
            ['label' => 'Batalkan Pembayaran', 'route' => 'admin.keuangan.batalkan-pembayaran.index'],
            ['label' => 'Log Batalkan Pembayaran', 'route' => 'admin.keuangan.log-batalkan-pembayaran.index'],
            ['label' => 'Saldo Siswa', 'route' => 'admin.keuangan.saldo-siswa.index'],
            ['label' => 'Kas Manual', 'route' => 'admin.keuangan.kas-manual.index'],
            ['label' => 'Laporan Keuangan', 'route' => 'admin.keuangan.laporan-keuangan'],
            ['label' => 'Pindah Saldo', 'route' => 'admin.keuangan.pindah-saldo'],
        ],
    ],

    [
        'label' => 'Absensi',
        'icon' => 'calendar-check',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.absensi.dashboard'],
            ['label' => 'Pelajaran', 'route' => 'admin.absensi.pelajaran.index'],
            ['label' => 'Jadwal Absen', 'route' => 'admin.absensi.jadwal-absen.index'],
            ['label' => 'Absensi Siswa', 'route' => 'admin.absensi.absensi-siswa.index'],
            ['label' => 'Absensi RFID (Kiosk)', 'route' => 'admin.absensi.absensi-rfid'],
            ['label' => 'Absensi Guru', 'route' => 'admin.absensi.absensi-guru.index'],
            ['label' => 'Jadwal Absensi Guru', 'route' => 'admin.absensi.jadwal-absensi-guru.index'],
            ['label' => 'Hari Libur', 'route' => 'admin.absensi.hari-libur.index'],
            ['label' => 'Pengecualian Sesi', 'route' => 'admin.absensi.sesi-pengecualian.index'],
            ['label' => 'Rekap Presensi Siswa', 'route' => 'admin.absensi.rekap-presensi'],
            ['label' => 'Rekap Presensi Guru', 'route' => 'admin.absensi.rekap-presensi-guru'],
            ['label' => 'Laporan Absensi', 'route' => 'admin.absensi.laporan-absensi'],
            ['label' => 'Export Absensi', 'route' => 'admin.absensi.ekspor-absensi'],
        ],
    ],

    [
        'label' => 'Cashless',
        'icon' => 'wallet',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.dompet-digital.dashboard'],
            // Hidden (routes remain): alokasi-uang-saku, transfer-kantin, pengajuan-tambahan, menu-kantin, topup-saldo
            ['label' => 'Saldo Cashless', 'route' => 'admin.dompet-digital.saldo-cashless.index'],
            ['label' => 'Saldo RFID (Kiosk)', 'route' => 'admin.dompet-digital.saldo-rfid.index', 'active' => 'admin.dompet-digital.saldo-rfid.*'],
            ['label' => 'Riwayat Transaksi', 'route' => 'admin.dompet-digital.transaksi.index'],
            ['label' => 'Pendapatan Kantin', 'route' => 'admin.dompet-digital.pendapatan-kantin.index', 'active' => [
                'admin.dompet-digital.pendapatan-kantin.index',
                'admin.dompet-digital.pendapatan-kantin.show',
                'admin.dompet-digital.pendapatan-kantin.transactions',
                'admin.dompet-digital.pendapatan-kantin.withdraw',
            ]],
            ['label' => 'Riwayat Penarikan Kantin', 'route' => 'admin.dompet-digital.pendapatan-kantin.penarikan.index', 'active' => 'admin.dompet-digital.pendapatan-kantin.penarikan.*'],
            ['label' => 'Limit & Kontrol', 'route' => 'admin.dompet-digital.limit-kontrol'],
            ['label' => 'Kontrol RFID', 'route' => 'admin.dompet-digital.rfid-kontrol'],
            ['label' => 'Kartu / QR & POS', 'route' => 'admin.dompet-digital.kartu-pos'],
            ['label' => 'Setting', 'route' => 'admin.pengaturan-modul.show', 'params' => ['module' => 'cashless']],
        ],
    ],

    [
        'label' => 'Perpustakaan',
        'icon' => 'books',
        'children' => [
            ['label' => 'Dashboard', 'route' => 'admin.perpustakaan.dashboard'],
            ['label' => 'Katalog Buku', 'route' => 'admin.perpustakaan.katalog-buku.index'],
            ['label' => 'Import Buku', 'route' => 'admin.perpustakaan.impor-buku'],
            ['label' => 'Peminjaman Buku', 'route' => 'admin.perpustakaan.peminjaman.index'],
            ['label' => 'Pengembalian Buku', 'route' => 'admin.perpustakaan.pengembalian-buku'],
            ['label' => 'History Peminjaman', 'route' => 'admin.perpustakaan.riwayat-peminjaman'],
            ['label' => 'Denda Keterlambatan', 'route' => 'admin.perpustakaan.denda-keterlambatan'],
            ['label' => 'Search Buku', 'route' => 'admin.perpustakaan.cari-buku'],
            ['label' => 'Rating & Review', 'route' => 'admin.perpustakaan.rating-ulasan'],
            ['label' => 'Setting', 'route' => 'admin.perpustakaan.setting-denda.index', 'active' => 'admin.perpustakaan.setting-denda.*'],
        ],
    ],

    ['label' => 'Rekap Perizinan', 'route' => 'admin.perizinan.rekap-laporan', 'icon' => 'door-exit', 'active' => 'admin.perizinan.*'],
];
