<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database seed mode
    |--------------------------------------------------------------------------
    |
    | full        — roles, dummy school data, demo users (local development)
    | production  — roles, schools/classes, initial admin users from SEED_* env vars
    |
    */

    'mode' => env('SEED_MODE', 'full'),

    'production' => [
        'superadmin' => [
            'username' => env('SEED_SUPERADMIN_USERNAME', 'superadmin'),
            'name' => env('SEED_SUPERADMIN_NAME', 'Super Admin'),
            'email' => env('SEED_SUPERADMIN_EMAIL'),
            'phone' => env('SEED_SUPERADMIN_PHONE'),
            'password' => env('SEED_SUPERADMIN_PASSWORD'),
        ],
        'admin' => [
            'enabled' => env('SEED_ADMIN_ENABLED', true),
            'username' => env('SEED_ADMIN_USERNAME', 'admin'),
            'name' => env('SEED_ADMIN_NAME', 'Administrator'),
            'email' => env('SEED_ADMIN_EMAIL'),
            'phone' => env('SEED_ADMIN_PHONE'),
            'password' => env('SEED_ADMIN_PASSWORD'),
            'sekolah_code' => env('SEED_ADMIN_SEKOLAH_CODE'),
        ],
    ],

];
