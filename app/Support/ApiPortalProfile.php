<?php

namespace App\Support;

use App\Models\Siswa;
use App\Models\User;
use App\Services\Finance\SccttranCashlessService;
use Illuminate\Support\Collection;

class ApiPortalProfile
{
    public static function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->getRoleNames()->first(),
            'sekolah_id' => $user->sekolah_id,
            'sekolah' => $user->sekolah?->only(['id', 'code', 'name']),
        ];
    }

    public static function orangTuaPayload(User $user): array
    {
        $orangTua = $user->orangTua;
        $children = $orangTua
            ? $orangTua->siswa()
                ->with('kelas')
                ->where('status', Siswa::STATUS_ACTIVE)
                ->orderBy('name')
                ->get()
            : collect();

        return [
            'orang_tua_id' => $user->orang_tua_id,
            'display_name' => $orangTua?->displayName(),
            'children' => $children->map(fn (Siswa $siswa) => self::siswaSummary($siswa))->values()->all(),
        ];
    }

    public static function siswaPayload(User $user): array
    {
        $siswa = $user->siswa?->loadMissing(['kelas', 'dompet', 'profil']);

        if (! $siswa) {
            return [
                'siswa_id' => $user->siswa_id,
            ];
        }

        return self::siswaDetail($siswa);
    }

    public static function siswaSummary(Siswa $siswa): array
    {
        return [
            'id' => $siswa->id,
            'nis' => $siswa->nis,
            'name' => $siswa->name,
            'gender' => $siswa->gender,
            'kelas' => $siswa->kelas?->only(['id', 'name', 'unit', 'jenjang']),
            'virtual_account' => $siswa->virtualAccountNumber(),
        ];
    }

    public static function siswaDetail(Siswa $siswa): array
    {
        $extra = $siswa->profil?->extra_fields ?? [];
        $cashless = app(SccttranCashlessService::class)->balanceForSiswa($siswa->id);

        return array_merge(self::siswaSummary($siswa), [
            'birth_date' => $siswa->birth_date?->toDateString(),
            'address' => $siswa->address,
            'nomor_pendaftaran' => $siswa->nomor_pendaftaran,
            'kelas_kelompok' => $extra['kelas_kelompok'] ?? null,
            'saldo_cashless' => $cashless,
        ]);
    }

    public static function childrenCollection(User $user): Collection
    {
        return $user->orangTua
            ? $user->orangTua->siswa()
                ->with(['kelas', 'dompet'])
                ->where('status', Siswa::STATUS_ACTIVE)
                ->orderBy('name')
                ->get()
            : collect();
    }
}
