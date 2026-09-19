# Username or email login

- Login form field is `login` (not `email`).
- Resolve credentials with `App\Support\LoginIdentifier`: email if `FILTER_VALIDATE_EMAIL`, otherwise `username`.
- Users have required unique `username`; email is nullable.
- Gate: `super_admin` bypasses all abilities via `AuthServiceProvider`.
- Optional Cloudflare Turnstile via `config/turnstile.php` + `ValidatesTurnstile` trait.
- Portal magic-link (`AccessController`): after validate, set `session.lifetime` + `Auth::guard()->setRememberDuration()` from `config('portal.session_lifetime_minutes')` (default = `PORTAL_ACCESS_TOKEN_TTL_DAYS` × 24 × 60; override with `PORTAL_SESSION_LIFETIME`), then `Auth::login($user, remember: true)` and store `auth.portal_access_token_id` in session. Keep `PORTAL_APP_NAME` as Ittihad branding.
