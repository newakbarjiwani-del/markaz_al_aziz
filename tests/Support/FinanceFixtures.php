<?php

namespace Tests\Support;

use App\Models\JenisTagihan;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Support\TagihanPeriode;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Hash;

final class FinanceFixtures
{
    public static function seedPermissions(): void
    {
        (new RolePermissionSeeder)->run();
    }

    /**
     * @param  array<string, array<string, mixed>>  $overrides
     * @return object{
     *     sekolah: Sekolah,
     *     kelas: Kelas,
     *     tahun: TahunAkademik,
     *     siswa: Siswa,
     *     spp: JenisTagihan
     * }
     */
    public static function schoolWithStudent(array $overrides = []): object
    {
        $sekolah = Sekolah::create(array_merge([
            'code' => 'ma',
            'name' => 'MA Test',
            'address' => 'A',
            'is_active' => true,
        ], $overrides['sekolah'] ?? []));

        $kelas = Kelas::create(array_merge([
            'sekolah_id' => $sekolah->id,
            'name' => 'X IPA 1',
            'is_active' => true,
        ], $overrides['kelas'] ?? []));

        $tahun = TahunAkademik::create(array_merge([
            'name' => '2025/2026',
            'is_active' => true,
        ], $overrides['tahun'] ?? []));

        $spp = JenisTagihan::create(array_merge([
            'name' => 'SPP',
            'default_amount' => 500000,
            'is_spp' => true,
            'is_active' => true,
        ], $overrides['spp'] ?? []));

        $siswa = Siswa::create(array_merge([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => '1000001',
            'name' => 'Siswa Test',
            'status' => \App\Models\Siswa::STATUS_ACTIVE,
        ], $overrides['siswa'] ?? []));

        return (object) [
            'sekolah' => $sekolah,
            'kelas' => $kelas,
            'tahun' => $tahun,
            'siswa' => $siswa,
            'spp' => $spp,
        ];
    }

    public static function jenisTagihan(?Sekolah $sekolah = null, array $attributes = []): JenisTagihan
    {
        return JenisTagihan::create(array_merge([
            'name' => 'SPP',
            'default_amount' => 500000,
            'is_spp' => true,
            'is_active' => true,
        ], $attributes));
    }

    public static function tagihan(
        Siswa $siswa,
        JenisTagihan $jenis,
        TahunAkademik $tahun,
        array $attributes = []
    ): Tagihan {
        return Tagihan::create(array_merge([
            'sekolah_id' => $siswa->sekolah_id,
            'siswa_id' => $siswa->id,
            'tahun_akademik_id' => $tahun->id,
            'jenis_tagihan_id' => $jenis->id,
            'jenis' => $jenis->name,
            'amount' => $jenis->default_amount ?? 500000,
            'paid' => 0,
            'status' => Tagihan::STATUS_UNPAID,
            'periode' => TagihanPeriode::fromCalendarMonth(2026, 7),
        ], $attributes));
    }

    public static function adminUser(array $attributes = []): User
    {
        $admin = User::create(array_merge([
            'username' => 'admin.test',
            'name' => 'Admin Test',
            'email' => 'admin-test@local.test',
            'password' => Hash::make('password'),
            'status' => 'aktif',
        ], $attributes));
        $admin->assignRole('admin');

        return $admin;
    }
}
