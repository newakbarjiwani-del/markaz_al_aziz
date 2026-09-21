<?php

return [
    ['label' => 'Beranda', 'route' => 'portal.guru.dashboard', 'icon' => 'home'],

    ['label' => 'Absensi Siswa', 'route' => 'portal.guru.absensi-siswa.index', 'icon' => 'camera'],

    ['label' => 'Rekap Absensi Siswa', 'route' => 'portal.guru.rekap-siswa.index', 'icon' => 'clipboard-list'],

    ['label' => 'Absensi Saya', 'route' => 'portal.guru.absensi.index', 'icon' => 'user-check'],

    ['label' => 'Rekap Perizinan', 'route' => 'portal.guru.rekap-perizinan.index', 'icon' => 'door-exit'],

    ['label' => 'Booklet Sekolah', 'route' => 'portal.guru.booklet.index', 'icon' => 'book'],

    ['label' => 'Tahfidz', 'route' => 'portal.guru.tahfidz.index', 'icon' => 'book-2'],
    ['label' => 'Rekap Tahfidz', 'route' => 'portal.guru.tahfidz.rekap.index', 'icon' => 'clipboard-list'],

    [
        'label' => 'Prestasi & Pelanggaran',
        'icon' => 'award',
        'children' => [
            ['label' => 'Prestasi Siswa', 'route' => 'portal.guru.prestasi-siswa.index'],
            ['label' => 'Pelanggaran Siswa', 'route' => 'portal.guru.pelanggaran-siswa.index'],
        ],
    ],

    ['label' => 'Profil Saya', 'route' => 'portal.guru.profil', 'icon' => 'address-book'],

    ['label' => 'Kartu Guru', 'route' => 'portal.guru.kartu-guru', 'icon' => 'id-badge-2'],
];
