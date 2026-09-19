<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\User;
use App\Support\UserStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PortalUserProvisioner
{
    public static function forSiswa(Siswa $siswa): User
    {
        $existing = User::query()->where('siswa_id', $siswa->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($siswa) {
            $username = self::uniqueUsername(self::siswaUsernameBase($siswa));
            $user = User::create([
                'username' => $username,
                'name' => $siswa->name,
                'email' => null,
                'password' => Hash::make($username),
                'status' => UserStatus::ACTIVE,
                'sekolah_id' => $siswa->sekolah_id,
                'siswa_id' => $siswa->id,
            ]);

            $user->assignRole('siswa');

            return $user;
        });
    }

    public static function forOrangTua(OrangTua $orangTua): User
    {
        $existing = User::query()->where('orang_tua_id', $orangTua->id)->first();
        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($orangTua) {
            $username = self::uniqueUsername(self::orangTuaUsernameBase($orangTua));
            $user = User::create([
                'username' => $username,
                'name' => $orangTua->displayName(),
                'email' => null,
                'phone' => $orangTua->primaryPhone(),
                'password' => Hash::make($username),
                'status' => UserStatus::ACTIVE,
                'sekolah_id' => $orangTua->sekolah_id,
                'orang_tua_id' => $orangTua->id,
            ]);

            $user->assignRole('orang_tua');

            return $user;
        });
    }

    /**
     * @return array{user: User, created: bool}
     */
    public static function forGuru(Guru $guru, string $plainPassword): array
    {
        $existing = User::query()->where('guru_id', $guru->id)->first();
        if ($existing) {
            return ['user' => $existing, 'created' => false];
        }

        $user = DB::transaction(function () use ($guru, $plainPassword) {
            $username = self::uniqueUsername(self::guruUsernameBase($guru));
            $user = User::create([
                'username' => $username,
                'name' => $guru->name,
                'email' => null,
                'phone' => $guru->phone,
                'password' => Hash::make($plainPassword),
                'status' => $guru->status === 'aktif' ? UserStatus::ACTIVE : UserStatus::DISABLED,
                'sekolah_id' => $guru->sekolah_id,
                'guru_id' => $guru->id,
            ]);

            $user->assignRole('guru');

            return $user;
        });

        return ['user' => $user, 'created' => true];
    }

    /** @return array{user: User} */
    public static function resetGuruPassword(Guru $guru, string $plainPassword): array
    {
        $user = User::query()->where('guru_id', $guru->id)->firstOrFail();

        $user->update([
            'password' => Hash::make($plainPassword),
        ]);

        return ['user' => $user->fresh()];
    }

    public static function siswaUsernameBase(Siswa $siswa): string
    {
        $nis = preg_replace('/\D/', '', (string) $siswa->nis) ?: '0';
        $firstWord = self::firstNameWord($siswa->name);

        return "{$nis}_{$firstWord}";
    }

    public static function orangTuaUsernameBase(OrangTua $orangTua): string
    {
        $nameSource = $orangTua->nama_ayah ?: $orangTua->nama_ibu ?: 'ortu';
        $firstWord = self::firstNameWord($nameSource);
        $phoneDigits = preg_replace('/\D/', '', (string) $orangTua->primaryPhone()) ?? '';
        $suffix = strlen($phoneDigits) >= 4
            ? substr($phoneDigits, -4)
            : (string) $orangTua->id;

        return "{$firstWord}_{$suffix}";
    }

    public static function guruUsernameBase(Guru $guru): string
    {
        $nip = trim((string) $guru->nip);
        if ($nip === '') {
            return 'guru_'.$guru->id;
        }

        $slug = Str::lower(Str::slug($nip, '_'));

        return $slug !== '' ? $slug : 'guru_'.$guru->id;
    }

    private static function firstNameWord(string $name): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $word = explode(' ', $normalized)[0] ?? 'user';
        $slug = Str::slug($word);

        return $slug !== '' ? $slug : 'user';
    }

    private static function uniqueUsername(string $base): string
    {
        $username = Str::lower(Str::slug($base, '_'));
        $candidate = $username;
        $counter = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $candidate = $username.'_'.$counter;
            $counter++;
        }

        return $candidate;
    }

}
