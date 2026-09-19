<?php

use App\Models\Kelas;
use App\Models\ProfilSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\StudentSpreadsheetTemplate;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'Madrasah Aliyah (MA)',
        'address' => 'Tigamaya',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'XII 01-IBNU HAJAR',
        'unit' => 'MA',
        'jenjang' => 'XII',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.export',
        'name' => 'Admin Export',
        'email' => 'admin-export@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '9005001',
        'nomor_pendaftaran' => 'NODAF5001',
        'name' => 'SISWA EXPORT',
        'gender' => 'L',
        'birth_place' => 'Pekanbaru',
        'birth_date' => '2010-03-12',
        'address' => 'Jl. Export No. 1',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    ProfilSiswa::create([
        'siswa_id' => $siswa->id,
        'extra_fields' => [
            'unit' => 'MA',
            'kelas_kelompok' => '01-IBNU HAJAR',
            'angkatan' => '2026/2027',
            'nama_wali' => 'Wali Export',
        ],
    ]);
});

test('impor ekspor export route redirects to data siswa', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.impor-ekspor.export'))
        ->assertRedirect(route('admin.manajemen-siswa.data-siswa.index'));
});

test('impor ekspor page links export to data siswa', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.impor-ekspor'))
        ->assertOk()
        ->assertSee(route('admin.manajemen-siswa.data-siswa.index'), false)
        ->assertSee('Buka Data Siswa');
});

test('student export row includes complete import columns', function () {
    $siswa = Siswa::with(['kelas', 'sekolah', 'profil'])->where('nis', '9005001')->firstOrFail();
    $row = StudentSpreadsheetTemplate::exportRowFromSiswa($siswa);

    expect($row)->toBe([
        '9005001',
        'SISWA EXPORT',
        'NODAF5001',
        'MA',
        '12',
        '01-IBNU HAJAR',
        '2026/2027',
        'L',
        'Pekanbaru',
        '2010-03-12',
        'Jl. Export No. 1',
        'Wali Export',
        'aktif',
    ]);
});
