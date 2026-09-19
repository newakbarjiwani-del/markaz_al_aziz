<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\UserStatus;
use Database\Seeders\Dummy\DummySchoolCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $demoSchool = Sekolah::query()
            ->where('code', DummySchoolCatalog::demoSchoolCode())
            ->first();

        $firstGuru = Guru::query()
            ->when($demoSchool, fn ($q) => $q->where('sekolah_id', $demoSchool->id))
            ->orderBy('id')
            ->first();

        $firstParent = OrangTua::query()
            ->when($demoSchool, fn ($q) => $q->where('sekolah_id', $demoSchool->id))
            ->orderBy('id')
            ->first();

        $firstSiswa = Siswa::query()
            ->when($demoSchool, fn ($q) => $q->where('sekolah_id', $demoSchool->id))
            ->orderBy('id')
            ->first();

        $users = [
            [
                'username' => 'superadmin',
                'name' => 'Super Admin',
                'email' => 'superadmin@school.local',
                'phone' => '628110000001',
                'role' => 'super_admin',
                'sekolah_id' => null,
            ],
            [
                'username' => 'admin',
                'name' => 'Administrator',
                'email' => 'admin@school.local',
                'phone' => '628110000002',
                'role' => 'admin',
                'sekolah_id' => $demoSchool?->id,
            ],
            [
                'username' => 'guru',
                'name' => $firstGuru?->name ?? 'Guru Demo',
                'email' => 'guru@school.local',
                'phone' => '628110000003',
                'role' => 'guru',
                'sekolah_id' => $demoSchool?->id,
                'guru_id' => $firstGuru?->id,
            ],
            [
                'username' => 'ortu',
                'name' => $firstParent?->displayName() ?? 'Orang Tua Demo',
                'email' => 'ortu@school.local',
                'phone' => '628110000004',
                'role' => 'orang_tua',
                'sekolah_id' => $demoSchool?->id,
                'orang_tua_id' => $firstParent?->id,
            ],
            [
                'username' => 'siswa',
                'name' => $firstSiswa?->name ?? 'Siswa Demo',
                'email' => 'siswa@school.local',
                'phone' => '628110000005',
                'role' => 'siswa',
                'sekolah_id' => $demoSchool?->id,
                'siswa_id' => $firstSiswa?->id,
            ],
            [
                'username' => 'pimpinan',
                'name' => 'Pimpinan Demo',
                'email' => 'pimpinan@school.local',
                'phone' => '628110000006',
                'role' => 'pimpinan',
                'sekolah_id' => $demoSchool?->id,
            ],
            [
                'username' => 'prestasi',
                'name' => 'Operator Prestasi',
                'email' => 'prestasi_pelanggaran@school.local',
                'phone' => '628110000010',
                'role' => 'prestasi_pelanggaran',
                'sekolah_id' => $demoSchool?->id,
            ],
            [
                'username' => 'perpustakaan',
                'name' => 'Petugas Perpustakaan',
                'email' => 'perpustakaan@school.local',
                'phone' => '628110000007',
                'role' => 'perpustakaan',
                'sekolah_id' => $demoSchool?->id,
            ],
            [
                'username' => 'cashless',
                'name' => 'Operator Cashless',
                'email' => 'cashless@school.local',
                'phone' => '628110000008',
                'role' => 'cashless',
                'sekolah_id' => $demoSchool?->id,
            ],
            [
                'username' => 'bendahara',
                'name' => 'Bendahara Demo',
                'email' => 'bendahara@school.local',
                'phone' => '628110000009',
                'role' => 'bendahara',
                'sekolah_id' => $demoSchool?->id,
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::create(array_merge($userData, [
                'password' => Hash::make('password'),
                'status' => UserStatus::ACTIVE,
            ]));

            $user->assignRole($role);
        }
    }
}
