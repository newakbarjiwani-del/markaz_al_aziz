<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portal PWA
    |--------------------------------------------------------------------------
    |
    | Progressive Web App settings for portal roles (guru, ortu, siswa, kantin,
    | pimpinan, perpustakaan). Admin/super_admin pages are excluded.
    |
    */

    'name' => env('PORTAL_APP_NAME', config('portal.app_name', 'ITTIHAD APP')),

    'short_name' => env('PWA_SHORT_NAME', 'ITTIHAD APP'),

    'description' => env('PWA_DESCRIPTION', 'Portal sekolah YAYASAN ITTIHAD — absensi, keuangan, dompet digital, dan perpustakaan.'),

    'theme_color' => '#189e61',

    'background_color' => '#f2f9f5',

    'theme_color_dark' => '#09140e',

    'background_color_dark' => '#09140e',

    'cache_version' => env('PWA_CACHE_VERSION', '3'),

];
