<?php

use App\Models\Kelas;
use App\Models\ProfilSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'Madrasah Aliyah (MA)',
        'address' => 'Tigamaya',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.profil.siswa',
        'name' => 'Admin Profil Siswa',
        'email' => 'admin-profil-siswa@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '9005001',
        'name' => 'Siswa Profil Test',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    ProfilSiswa::create([
        'siswa_id' => $this->siswa->id,
        'extra_fields' => [
            'nama_panggilan' => 'Profil',
        ],
    ]);
});

test('admin can update student profil extra fields', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.profil-siswa.update', $this->siswa), [
            'nama_panggilan' => 'Ujang',
            'golongan_darah' => 'O',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $extra = $this->siswa->fresh()->profil?->extra_fields ?? [];

    expect($extra['nama_panggilan'])->toBe('Ujang')
        ->and($extra['golongan_darah'])->toBe('O');
});

test('profil siswa datatable includes edit action for admin', function () {
    $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.profil-siswa.data'))
        ->assertOk()
        ->assertJsonPath('data.0.8.type', 'action');
});

test('profil siswa update validates golongan darah', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.profil-siswa.update', $this->siswa), [
            'golongan_darah' => 'INVALID',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['golongan_darah']);
});
