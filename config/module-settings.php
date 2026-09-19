<?php

return [
    'finance' => [
        'title' => 'Setting Keuangan',
        'fields' => [
            ['key' => 'spp_amount', 'label' => 'Nominal SPP default (Rp)', 'type' => 'number', 'default' => 500000],
            ['key' => 'due_day', 'label' => 'Tanggal jatuh tempo (hari)', 'type' => 'number', 'default' => 10],
        ],
    ],
    'attendance' => [
        'title' => 'Setting Absensi',
        'fields' => [
            ['key' => 'jam_masuk', 'label' => 'Jam masuk', 'type' => 'time', 'default' => '07:00'],
            ['key' => 'jam_pulang', 'label' => 'Jam pulang', 'type' => 'time', 'default' => '15:00'],
            ['key' => 'toleransi_menit', 'label' => 'Toleransi waktu (menit)', 'type' => 'number', 'default' => 15],
            ['key' => 'enable_qr', 'label' => 'Aktifkan QR check-in', 'type' => 'checkbox', 'default' => true],
        ],
    ],
    'cashless' => [
        'title' => 'Setting Cashless',
        'fields' => [
            // Stored in table `pengaturan_cashless` (per sekolah).
            // Only daily limit is enforced for now — weekly/monthly intentionally omitted.
            ['key' => 'daily_transaction_limit', 'label' => 'Limit transaksi harian global (Rp)', 'type' => 'number', 'default' => 50000],
            ['key' => 'min_topup', 'label' => 'Minimum top-up (Rp)', 'type' => 'number', 'default' => 10000],
            ['key' => 'allow_transfer', 'label' => 'Izinkan transfer US → Kantin', 'type' => 'checkbox', 'default' => true],
        ],
    ],
    'library' => [
        'title' => 'Setting Perpustakaan & Denda',
        'fields' => [
            ['key' => 'loan_days', 'label' => 'Masa pinjam (hari)', 'type' => 'number', 'default' => 7],
            ['key' => 'max_books', 'label' => 'Maks buku per siswa', 'type' => 'number', 'default' => 3],
            ['key' => 'max_extensions', 'label' => 'Maks perpanjangan', 'type' => 'number', 'default' => 1],
            ['key' => 'extension_days', 'label' => 'Saran perpanjangan (hari)', 'type' => 'number', 'default' => 7],
        ],
    ],
];
