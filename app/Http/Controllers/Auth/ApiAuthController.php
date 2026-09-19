<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiLoginRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\User;
use App\Services\LoginLogService;
use App\Support\ApiPortalProfile;
use App\Support\LoginIdentifier;
use App\Support\LoginLogMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class ApiAuthController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly LoginLogService $loginLog,
    ) {}

    public function loginOrangTua(ApiLoginRequest $request): JsonResponse
    {
        return $this->issueToken($request, 'orang_tua');
    }

    public function loginSiswa(ApiLoginRequest $request): JsonResponse
    {
        return $this->issueToken($request, 'siswa');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken()
            ?? PersonalAccessToken::findToken((string) $request->bearerToken());

        $token?->delete();

        return $this->jsonSuccess('Logout berhasil.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['sekolah', 'orangTua', 'siswa.kelas', 'siswa.dompet', 'siswa.profil']);

        return $this->jsonSuccess('OK', $this->authenticatedPayload($user));
    }

    private function issueToken(ApiLoginRequest $request, string $expectedRole): JsonResponse
    {
        $login = $request->loginIdentifier();

        if (! Auth::attempt(LoginIdentifier::credentials(
            $login,
            $request->input('password'),
        ))) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_API,
                $request,
                'Kredensial tidak valid.',
                $login,
            );

            throw ValidationException::withMessages([
                'login' => ['Kredensial tidak valid.'],
            ]);
        }

        $user = Auth::user();
        Auth::logout();

        if (! $user->canLogin()) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_API,
                $request,
                'Akun tidak aktif.',
                $login,
                $user,
            );

            return $this->jsonError('Akun tidak aktif.', null, 403);
        }

        if (! $user->hasRole($expectedRole)) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_API,
                $request,
                'Role akun tidak sesuai.',
                $login,
                $user,
            );

            return $this->jsonError('Akun ini bukan pengguna '.str_replace('_', ' ', $expectedRole).'.', null, 403);
        }

        if ($expectedRole === 'orang_tua' && ! $user->orang_tua_id) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_API,
                $request,
                'Akun orang tua belum terhubung ke data wali.',
                $login,
                $user,
            );

            return $this->jsonError('Akun orang tua belum terhubung ke data wali.', null, 403);
        }

        if ($expectedRole === 'siswa' && ! $user->siswa_id) {
            $this->loginLog->recordFailure(
                LoginLogMethod::CREDENTIALS_API,
                $request,
                'Akun siswa belum terhubung ke data pelajar.',
                $login,
                $user,
            );

            return $this->jsonError('Akun siswa belum terhubung ke data pelajar.', null, 403);
        }

        $user->load(['sekolah', 'orangTua', 'siswa.kelas', 'siswa.dompet', 'siswa.profil']);

        $token = $user->createToken(
            $request->deviceName(),
            [$expectedRole],
        )->plainTextToken;

        $this->loginLog->recordSuccess(
            $user,
            LoginLogMethod::CREDENTIALS_API,
            $request,
            identifier: $login,
        );

        return $this->jsonSuccess('Login berhasil.', array_merge(
            ['token' => $token],
            $this->authenticatedPayload($user),
        ));
    }

    private function authenticatedPayload(User $user): array
    {
        $role = $user->getRoleNames()->first();

        return [
            'user' => ApiPortalProfile::userPayload($user),
            'profile' => match ($role) {
                'orang_tua' => ApiPortalProfile::orangTuaPayload($user),
                'siswa' => ApiPortalProfile::siswaPayload($user),
                default => null,
            },
        ];
    }
}
