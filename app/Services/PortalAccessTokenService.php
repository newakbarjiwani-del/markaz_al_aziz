<?php

namespace App\Services;

use App\Models\OrangTua;
use App\Models\PortalAccessToken;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalAccessTokenService
{
    public function issueForSiswa(Siswa $siswa, ?int $createdBy = null): array
    {
        $user = PortalUserProvisioner::forSiswa($siswa);

        return $this->issue($user, 'siswa', $createdBy, [
            'recipient_name' => $siswa->name,
            'student_names' => [$siswa->name],
            'phone' => $this->phoneForSiswa($siswa),
        ]);
    }

    public function issueForOrangTua(OrangTua $orangTua, ?Siswa $contextStudent = null, ?int $createdBy = null): array
    {
        $user = PortalUserProvisioner::forOrangTua($orangTua);
        $children = $orangTua->siswa()->where('status', \App\Models\Siswa::STATUS_ACTIVE)->orderBy('name')->get();
        $studentNames = $children->pluck('name')->all();

        if ($contextStudent && ! in_array($contextStudent->name, $studentNames, true)) {
            $studentNames[] = $contextStudent->name;
        }

        return $this->issue($user, 'orang_tua', $createdBy, [
            'recipient_name' => $orangTua->displayName(),
            'student_names' => $studentNames !== [] ? $studentNames : ['Anak Anda'],
            'phone' => $orangTua->primaryPhone(),
        ]);
    }

    public function revokeForSiswa(Siswa $siswa, ?string $role = null): int
    {
        $count = 0;
        $siswa->loadMissing('orangTua');

        $user = User::query()->where('siswa_id', $siswa->id)->first();
        if ($user && ($role === null || $role === 'siswa')) {
            $count += $this->revokeForUser($user, 'siswa');
        }

        foreach ($siswa->orangTua as $orangTua) {
            $ortuUser = User::query()->where('orang_tua_id', $orangTua->id)->first();
            if ($ortuUser && ($role === null || $role === 'orang_tua')) {
                $count += $this->revokeForUser($ortuUser, 'orang_tua');
            }
        }

        return $count;
    }

    public function revokeForOrangTua(OrangTua $orangTua): int
    {
        $user = User::query()->where('orang_tua_id', $orangTua->id)->first();

        return $user ? $this->revokeForUser($user, 'orang_tua') : 0;
    }

    public function revokeForUser(User $user, ?string $role = null): int
    {
        $tokenQuery = PortalAccessToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at');

        if ($role !== null) {
            $tokenQuery->where('role', $role);
        }

        $revoked = $tokenQuery->update(['revoked_at' => now()]);

        $sanctumRevoked = $user->tokens()->count();
        $user->tokens()->delete();

        return $revoked + $sanctumRevoked;
    }

    public function validate(string $plainToken, string $role): ?PortalAccessToken
    {
        $accessToken = PortalAccessToken::query()
            ->where('token', $plainToken)
            ->where('role', $role)
            ->first();

        if (! $accessToken || ! $accessToken->isActive()) {
            return null;
        }

        return $accessToken->load('user');
    }

    public function markUsed(PortalAccessToken $accessToken): void
    {
        $accessToken->update(['last_used_at' => now()]);
    }

    public function activeToken(User $user, string $role): ?PortalAccessToken
    {
        return PortalAccessToken::query()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /** @return array{active: bool, access_url: ?string, expires_at: ?string, last_used_at: ?string, created_at: ?string} */
    public function tokenStatus(User $user, string $role): array
    {
        $token = $this->activeToken($user, $role);

        if (! $token) {
            return [
                'active' => false,
                'access_url' => null,
                'expires_at' => null,
                'last_used_at' => null,
                'created_at' => null,
            ];
        }

        return [
            'active' => true,
            'access_url' => $this->accessUrl($token),
            'expires_at' => $token->expires_at?->toIso8601String(),
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
        ];
    }

    public function accessUrl(PortalAccessToken $token): string
    {
        return route('access.show', [
            'token' => $token->token,
            'role' => $token->role,
        ]);
    }

    /** @return array{access_url: string, expires_at: ?string} */
    public function activeLinkForSiswa(Siswa $siswa): array
    {
        $user = User::query()->where('siswa_id', $siswa->id)->first();
        abort_unless($user, 404, 'Akun portal siswa belum tersedia.');

        return $this->activeLinkForUser($user, 'siswa');
    }

    /** @return array{access_url: string, expires_at: ?string} */
    public function activeLinkForOrangTua(OrangTua $orangTua): array
    {
        $user = User::query()->where('orang_tua_id', $orangTua->id)->first();
        abort_unless($user, 404, 'Akun portal orang tua belum tersedia.');

        return $this->activeLinkForUser($user, 'orang_tua');
    }

    /** @return array{access_url: string, expires_at: ?string} */
    private function activeLinkForUser(User $user, string $role): array
    {
        $status = $this->tokenStatus($user, $role);
        abort_unless($status['active'] && filled($status['access_url']), 404, 'Token login portal tidak aktif.');

        return [
            'access_url' => $status['access_url'],
            'expires_at' => $status['expires_at'],
        ];
    }

    public function refreshForUser(User $user, string $role, ?int $createdBy = null): array
    {
        return match ($role) {
            'siswa' => $this->issueForSiswa(
                tap($user->siswa, fn ($siswa) => abort_unless($siswa, 403, 'Akun siswa tidak terhubung.')),
                $createdBy
            ),
            'orang_tua' => $this->issueForOrangTua(
                tap($user->orangTua, fn ($ortu) => abort_unless($ortu, 403, 'Akun orang tua tidak terhubung.')),
                null,
                $createdBy
            ),
            default => abort(422, 'Peran portal tidak valid.'),
        };
    }

    private function issue(User $user, string $role, ?int $createdBy, array $context): array
    {
        $plainToken = Str::random(48);
        $expiresAt = now()->addDays((int) config('portal.access_token_ttl_days', 7));

        $accessToken = DB::transaction(function () use ($user, $role, $createdBy, $plainToken, $expiresAt) {
            PortalAccessToken::query()
                ->where('user_id', $user->id)
                ->where('role', $role)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return PortalAccessToken::create([
                'user_id' => $user->id,
                'role' => $role,
                'token' => $plainToken,
                'expires_at' => $expiresAt,
                'created_by' => $createdBy,
            ]);
        });

        $accessUrl = route('access.show', [
            'token' => $plainToken,
            'role' => $role,
        ]);

        return [
            'access_token_id' => $accessToken->id,
            'access_url' => $accessUrl,
            'expires_at' => $expiresAt->toIso8601String(),
            'phone' => $context['phone'] ?? null,
            'message' => $this->buildInviteMessage(
                $context['recipient_name'],
                $context['student_names'],
                $accessUrl,
                $role,
            ),
        ];
    }

    /** @param list<string> $studentNames */
    private function buildInviteMessage(string $recipientName, array $studentNames, string $accessUrl, string $role): string
    {
        $appName = config('portal.app_name', 'ITTIHAD APP');
        $portalLabel = $role === 'orang_tua' ? 'Parent' : 'Siswa';
        $studentsText = implode(', ', $studentNames);
        $studentPhrase = count($studentNames) > 1 ? 'anak-anak Anda' : 'anak Anda';

        return "Halo {$recipientName},\n\n"
            ."Anda telah diberikan akses ke dashboard {$appName} {$portalLabel} "
            ."untuk melihat informasi {$studentPhrase} ({$studentsText}).\n\n"
            ."Klik link berikut untuk masuk:\n{$accessUrl}\n\n"
            ."Link berlaku ".config('portal.access_token_ttl_days', 7)." hari. "
            ."Jangan bagikan link ini kepada pihak lain.\n\nTerima kasih.";
    }

    private function phoneForSiswa(Siswa $siswa): ?string
    {
        /** @var Collection<int, OrangTua> $parents */
        $parents = $siswa->orangTua;

        foreach ($parents as $parent) {
            $phone = $parent->primaryPhone();
            if ($phone) {
                return $phone;
            }
        }

        return null;
    }
}
