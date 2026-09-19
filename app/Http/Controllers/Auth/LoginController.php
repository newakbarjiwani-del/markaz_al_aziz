<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\ValidatesTurnstile;
use App\Services\LoginLogService;
use App\Support\HomeRedirect;
use App\Support\LoginIdentifier;
use App\Support\LoginLogMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    use ValidatesTurnstile;

    public function __construct(
        private readonly LoginLogService $loginLog,
    ) {}

    public function show(): View
    {
        return view('auth.login', [
            'turnstileEnabled' => config('turnstile.enabled'),
            'turnstileSiteKey' => config('turnstile.site_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(array_merge([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ], $this->turnstileRules()), $this->turnstileMessages());

        $login = trim($request->string('login')->toString());

        if (! Auth::attempt(
            LoginIdentifier::credentials($login, $request->input('password')),
            $request->boolean('remember'),
        )) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_WEB,
                $request,
                'Username, email, atau password salah.',
                $login,
            );

            return back()
                ->withErrors(['login' => 'Username, email, atau password salah.'])
                ->onlyInput('login');
        }

        $user = Auth::user();

        if (! $user->canLogin()) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_WEB,
                $request,
                'Akun tidak aktif.',
                $login,
                $user,
            );

            Auth::logout();

            return back()->withErrors(['login' => 'Akun tidak aktif.']);
        }

        $request->session()->regenerate();

        $home = HomeRedirect::for($user);

        if ($home === route('login')) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_WEB,
                $request,
                'Akun tidak memiliki akses.',
                $login,
                $user,
            );

            Auth::logout();

            return back()->withErrors(['login' => 'Akun tidak memiliki akses.']);
        }

        $this->loginLog->recordSuccess(
            $user,
            LoginLogMethod::CREDENTIALS_WEB,
            $request,
            identifier: $login,
        );

        return redirect()->intended($home);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
