<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Virtual account prefix (6 digits)
    |--------------------------------------------------------------------------
    |
    | Combined with zero-padded NIS (10 digits) to form a 16-digit VA number.
    |
    */
    'va_prefix' => env('VA_PREFIX', 'XXXXXX'),

    /*
    |--------------------------------------------------------------------------
    | Manual keuangan saldo adjustment (top-up / withdraw)
    |--------------------------------------------------------------------------
    |
    | When false, admin manual penyesuaian saldo keuangan is blocked in UI
    | and API until re-enabled.
    |
    */
    'manual_saldo_keuangan_adjustment_enabled' => env('MANUAL_SALDO_KEUANGAN_ADJUSTMENT_ENABLED', false),

    /*

    |--------------------------------------------------------------------------
    | Manual Cashless (Top-up) Saldo Adjustment
    |--------------------------------------------------------------------------
    |
    | When false, admin manual top-up / tarik saldo cashless (dompet) is blocked in
    | UI and API until re-enabled.
    |
    */

    'manual_saldo_cashless_adjustment_enabled' => env('MANUAL_SALDO_CASHLESS_ADJUSTMENT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Profile photo WebP quality
    |--------------------------------------------------------------------------
    |
    | Quality used when converting student/teacher profile photos to WebP.
    | Recommended safe range is 80-95.
    |
    */
    'profile_photo_webp_quality' => env('PROFILE_PHOTO_WEBP_QUALITY', 88),

];
