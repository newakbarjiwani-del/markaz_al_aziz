<?php

use App\Models\DokumenSiswa;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.berkas',
        'name' => 'Admin Berkas',
        'email' => 'admin-berkas@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '9006001',
        'name' => 'Siswa Berkas',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    DokumenSiswa::create([
        'siswa_id' => $siswa->id,
        'title' => 'Akta Kelahiran',
        'file_type' => 'pdf',
        'file_path' => 'dokumen/akta.pdf',
    ]);
});

test('admin can view berkas siswa page and datatable', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.berkas-siswa'))
        ->assertOk()
        ->assertSee('Berkas Siswa');

    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.berkas-siswa.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});
