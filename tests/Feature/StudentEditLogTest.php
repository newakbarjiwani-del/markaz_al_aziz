<?php

use App\Models\Kelas;
use App\Models\LogSiswaEdit;
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
        'name' => 'MA Test',
        'address' => 'A',
        'is_active' => true,
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.siswa',
        'name' => 'Admin Siswa',
        'email' => 'admin-siswa@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '2026001001',
        'name' => 'Siswa Awal',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
        'address' => 'Alamat lama',
        'daily_transaction_limit' => 50000,
    ]);
    assignRfid($this->siswa, 'S012026001001');
});

test('student update logs changed fields only', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.data-siswa.update', $this->siswa), [
            'nis' => '2026001001',
            'name' => 'Siswa Diperbarui',
            'kelas_id' => $this->kelas->id,
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
            'address' => 'Alamat lama',
            'rfid_uid' => 'S012026001001',
            'rfid_blocked' => '0',
            'daily_transaction_limit' => '50000',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(LogSiswaEdit::query()->where('siswa_id', $this->siswa->id)->count())->toBe(1);

    $this->assertDatabaseHas('log_siswa_edit', [
        'siswa_id' => $this->siswa->id,
        'user_id' => $this->admin->id,
        'field' => 'name',
        'old_value' => 'Siswa Awal',
        'new_value' => 'Siswa Diperbarui',
    ]);
});

test('student update without changes does not create edit log', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.data-siswa.update', $this->siswa), [
            'nis' => '2026001001',
            'name' => 'Siswa Awal',
            'kelas_id' => $this->kelas->id,
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
            'address' => 'Alamat lama',
            'rfid_uid' => 'S012026001001',
            'rfid_blocked' => '0',
            'daily_transaction_limit' => '50000',
        ])
        ->assertOk();

    expect(LogSiswaEdit::query()->where('siswa_id', $this->siswa->id)->count())->toBe(0);
});

test('profil siswa update logs extra field changes', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.profil-siswa.update', $this->siswa), [
            'nama_panggilan' => 'Budi',
            'golongan_darah' => 'O',
        ])
        ->assertOk();

    $this->assertDatabaseHas('log_siswa_edit', [
        'siswa_id' => $this->siswa->id,
        'field' => 'nama_panggilan',
        'old_value' => null,
        'new_value' => 'Budi',
    ]);

    $this->assertDatabaseHas('log_siswa_edit', [
        'siswa_id' => $this->siswa->id,
        'field' => 'golongan_darah',
        'old_value' => null,
        'new_value' => 'O',
    ]);
});
