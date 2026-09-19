<?php

$accessTokenTtlDays = max(1, (int) env('PORTAL_ACCESS_TOKEN_TTL_DAYS', 7));

return [

    /*
    |--------------------------------------------------------------------------
    | Portal access link (magic login URL)
    |--------------------------------------------------------------------------
    |
    | Token TTL for links sent via WhatsApp from admin student management.
    | This is how long the URL itself stays valid — not SESSION_LIFETIME.
    | After a successful /access login, the browser session + remember-me
    | cookie are extended to session_lifetime_minutes (default = TTL days).
    |
    */
    'access_token_ttl_days' => $accessTokenTtlDays,

    /*
    | Idle session lifetime (minutes) after magic-link login.
    | Defaults to PORTAL_ACCESS_TOKEN_TTL_DAYS × 24 × 60.
    | Override with PORTAL_SESSION_LIFETIME if needed.
    */
    'session_lifetime_minutes' => max(
        1,
        (int) (env('PORTAL_SESSION_LIFETIME') ?: ($accessTokenTtlDays * 24 * 60))
    ),

    'app_name' => env('PORTAL_APP_NAME', 'ITTIHAD APP'),

];
