<?php

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->schoolA = Sekolah::create(['code' => 'paud', 'name' => 'PAUD Test']);
    $this->schoolB = Sekolah::create(['code' => 'mts', 'name' => 'MTs Test']);

    $kelasA = Kelas::create(['sekolah_id' => $this->schoolA->id, 'name' => 'A', 'is_active' => true]);
    $kelasB = Kelas::create(['sekolah_id' => $this->schoolB->id, 'name' => 'B', 'is_active' => true]);

    Siswa::create([
        'sekolah_id' => $this->schoolA->id,
        'kelas_id' => $kelasA->id,
        'nis' => '1001',
        'name' => 'Siswa A',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    Siswa::create([
        'sekolah_id' => $this->schoolB->id,
        'kelas_id' => $kelasB->id,
        'nis' => '2001',
        'name' => 'Siswa B',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->scopedAdmin = User::create([
        'username' => 'admin.a',
        'name' => 'Admin A',
        'email' => 'admin-a@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->schoolA->id,
    ]);
    $this->scopedAdmin->assignRole('admin');

    $this->superAdmin = User::create([
        'username' => 'super',
        'name' => 'Super',
        'email' => 'super@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('scoped admin only sees students from assigned school', function () {
    $response = $this->actingAs($this->scopedAdmin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk();
    expect($response->json('recordsTotal'))->toBe(1);
});

test('super admin sees all students', function () {
    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk();
    expect($response->json('recordsTotal'))->toBe(2);
});

test('scoped admin cannot access student from other school', function () {
    $other = Siswa::where('sekolah_id', $this->schoolB->id)->first();

    $this->actingAs($this->scopedAdmin)
        ->get(route('admin.manajemen-siswa.data-siswa.show', $other))
        ->assertNotFound();
});

test('perizinan user linked to guru can load scoped kelas without memory recursion', function () {
    $guru = \App\Models\Guru::create([
        'sekolah_id' => $this->schoolA->id,
        'nip' => 'G-001',
        'name' => 'Guru Perizinan',
        'status' => 'aktif',
    ]);

    $petugas = User::create([
        'username' => 'petugas.perizinan',
        'name' => 'Petugas Perizinan',
        'email' => 'petugas-perizinan@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->schoolA->id,
        'guru_id' => $guru->id,
    ]);
    $petugas->assignRole('perizinan');

    $this->actingAs($petugas);

    $kelas = \App\Support\AdminSchoolScope::kelasList();
    expect($kelas)->toHaveCount(1);
    expect($kelas->first()->sekolah_id)->toBe($this->schoolA->id);

    $this->get(route('portal.perizinan.keluar-masuk.index'))
        ->assertOk();
});
