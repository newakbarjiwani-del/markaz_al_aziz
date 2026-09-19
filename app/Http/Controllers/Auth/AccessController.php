<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginLogService;
use App\Services\PortalAccessTokenService;
use App\Support\LoginLogMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function __construct(
        private readonly PortalAccessTokenService $portalAccess,
        private readonly LoginLogService $loginLog,
    ) {}

    public function show(Request $request): RedirectResponse|View
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:48'],
            'role' => ['required', 'in:orang_tua,siswa'],
        ]);

        $tokenHint = substr($validated['token'], 0, 8).'…';

        $accessToken = $this->portalAccess->validate($validated['token'], $validated['role']);

        if (! $accessToken) {
            $this->loginLog->recordFailure(
                LoginLogMethod::PORTAL_TOKEN,
                $request,
                'Token portal tidak valid atau sudah kedaluwarsa.',
                $tokenHint,
            );

            return view('auth.access-invalid', [
                'title' => 'Link Tidak Valid',
            ]);
        }

        $user = $accessToken->user;
        if (! $user->canLogin() || ! $user->hasRole($validated['role'])) {
            $this->loginLog->recordFailure(
                LoginLogMethod::PORTAL_TOKEN,
                $request,
                'Akses token portal ditolak.',
                $user->username,
                $user,
                $accessToken,
            );

            return view('auth.access-invalid', [
                'title' => 'Akses Ditolak',
            ]);
        }

        // Magic-link TTL ≠ web session. Align idle session + remember-me with
        // portal token lifetime so users are not forced to re-open the link
        // every SESSION_LIFETIME (default 120 minutes).
        $ttlMinutes = max(1, (int) config('portal.session_lifetime_minutes'));
        config(['session.lifetime' => $ttlMinutes]);
        Auth::guard()->setRememberDuration($ttlMinutes);

        Auth::login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->put('auth.portal_access_token_id', $accessToken->id);
        $this->portalAccess->markUsed($accessToken);

        $this->loginLog->recordSuccess(
            $user,
            LoginLogMethod::PORTAL_TOKEN,
            $request,
            $accessToken,
            $user->username,
        );

        return redirect()->intended(match ($validated['role']) {
            'orang_tua' => route('portal.ortu.dashboard'),
            'siswa' => route('portal.siswa.dashboard'),
        });
    }
}
