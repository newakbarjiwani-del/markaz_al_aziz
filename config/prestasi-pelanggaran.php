<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Minimum violation points to issue hukuman
    |--------------------------------------------------------------------------
    |
    | Students appear in the hukuman eligible list / can be issued punishment
    | only when the SUM of pelanggaran_siswa.point is at least this value.
    |
    */
    'hukuman_min_points' => (int) env('HUKUMAN_MIN_POINTS', 250),

    /*
    |--------------------------------------------------------------------------
    | Default jenis pelanggaran when perizinan return is late
    |--------------------------------------------------------------------------
    */
    'perizinan_late_return_jenis_nama' => env(
        'PERIZINAN_LATE_JENIS_NAMA',
        'Kembali ke pondok melebihi batas waktu izin'
    ),
];
