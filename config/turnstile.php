<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Bot protection on login forms. Disabled by default (TURNSTILE=FALSE).
    | Set TURNSTILE=true and provide site/secret keys to enable.
    |
    */

    'enabled' => filter_var(env('TURNSTILE', false), FILTER_VALIDATE_BOOLEAN),

    'site_key' => env('TURNSTILE_SITE_KEY'),

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

    'verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',

];
