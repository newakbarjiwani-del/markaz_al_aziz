<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Finance JWT (bank / VA online payment)
    |--------------------------------------------------------------------------
    |
    | Separate signing key from portal JWT (config/jwt.php).
    |
    */

    'jwt_key' => env('FINANCE_JWT_KEY'),

    'jwt_algo' => env('FINANCE_JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Bill allocation after TOP UP
    |--------------------------------------------------------------------------
    |
    | auto_loop — pay all eligible bills while saldo covers each full remaining
    | single    — pay only the first eligible bill
    |
    */

    'payment_mode' => env('FINANCE_PAYMENT_MODE', 'single'),

    /*
    |--------------------------------------------------------------------------
    | Admin fee (deducted from PAYMENT token amount before crediting saldo)
    |--------------------------------------------------------------------------
    */

    'biaya_admin' => (int) env('BIAYA_ADMIN', 0),

    /*
    |--------------------------------------------------------------------------
    | Admin fee for transfer from finance saldo to cashless wallet
    |--------------------------------------------------------------------------
    */

    'biaya_admin_pindah_saldo' => (int) env('BIAYA_ADMIN_PINDAH_SALDO', 1000),

    /*
    |--------------------------------------------------------------------------
    | Infaq (donation) on pindah saldo
    |--------------------------------------------------------------------------
    |
    | off      — infaq disabled, no UI or records
    | on       — infaq mandatory, auto-calculated from tiers, deducted from cashless
    | optional — infaq suggested with checkbox + editable amount (default checked)
    |
    */

    'infaq_mode' => env('INFAQ_MODE', 'off'),

    'infaq_max' => (int) env('INFAQ_MAX', 10000),

    'infaq_tiers' => [
        ['min' => 50000,    'max' => 200000,  'amount' => 2000],
        ['min' => 200001,   'max' => 500000,  'amount' => 3000],
        ['min' => 500001,   'max' => 700000,  'amount' => 5000],
        ['min' => 700001,   'max' => 1000000, 'amount' => 7000],
        ['min' => 1000001,  'max' => null,     'amount' => 10000],
    ],

];
