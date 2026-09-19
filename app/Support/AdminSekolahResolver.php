<?php

namespace App\Support;

use App\Models\Sekolah;
use App\Models\User;

class AdminSekolahResolver
{
    public static function requiresSchoolSelection(?User $user): bool
    {
        return $user !== null && $user->sekolah_id === null;
    }

    /**
     * @return array{sekolah: Sekolah|null, error: string|null}
     */
    public static function resolve(?User $user, mixed $requestedSekolahId = null): array
    {
        if ($user?->sekolah_id) {
            $sekolah = Sekolah::query()->find($user->sekolah_id);

            if ($sekolah === null) {
                return ['sekolah' => null, 'error' => 'Sekolah akun admin tidak ditemukan.'];
            }

            return ['sekolah' => $sekolah, 'error' => null];
        }

        $id = is_numeric($requestedSekolahId) ? (int) $requestedSekolahId : null;

        if ($id === null) {
            return ['sekolah' => null, 'error' => 'Pilih sekolah tujuan import terlebih dahulu.'];
        }

        $sekolah = Sekolah::query()->find($id);

        if ($sekolah === null) {
            return ['sekolah' => null, 'error' => 'Sekolah tujuan import tidak ditemukan.'];
        }

        return ['sekolah' => $sekolah, 'error' => null];
    }

    /**
     * Import buku: sekolah opsional untuk super_admin tanpa sekolah (katalog global).
     *
     * @return array{sekolah: Sekolah|null, error: string|null}
     */
    public static function resolveOptional(?User $user, mixed $requestedSekolahId = null): array
    {
        if ($user?->sekolah_id) {
            return self::resolve($user, $requestedSekolahId);
        }

        $id = is_numeric($requestedSekolahId) ? (int) $requestedSekolahId : null;

        if ($id === null) {
            return ['sekolah' => null, 'error' => null];
        }

        $sekolah = Sekolah::query()->find($id);

        if ($sekolah === null) {
            return ['sekolah' => null, 'error' => 'Sekolah tujuan import tidak ditemukan.'];
        }

        return ['sekolah' => $sekolah, 'error' => null];
    }
}
