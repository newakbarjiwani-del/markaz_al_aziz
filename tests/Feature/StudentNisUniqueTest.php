<?php

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\VirtualAccountNumber;
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
        'address' => 'Tigamaya',
    ]);

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'is_active' => true,
    ]);

    $this->admin = User::create([
        'username' => 'admin.nis',
        'name' => 'Admin NIS',
        'email' => 'admin-nis@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '2026001001',
        'name' => 'Siswa Satu',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($this->siswa, 'RFID-NIS-001');
});

test('updating student keeps own nis without unique error', function () {
    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.data-siswa.update', $this->siswa), [
            'nis' => '2026001001',
            'name' => 'Siswa Satu Updated',
            'kelas_id' => $this->kelas->id,
            'gender' => 'L',
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => 'RFID-NIS-001',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->siswa->fresh()->name)->toBe('Siswa Satu Updated')
        ->and($this->siswa->fresh()->nis_key)->toBe('2026001001');
});

test('update student request ignores current siswa when validating nis', function () {
    $rules = VirtualAccountNumber::nisRules($this->siswa->id);
    $validator = validator(['nis' => $this->siswa->nis], ['nis' => $rules]);

    expect($validator->fails())->toBeFalse();
});

test('nis rules without ignore id flags the same nis as taken', function () {
    $rules = VirtualAccountNumber::nisRules(null);
    $validator = validator(['nis' => $this->siswa->nis], ['nis' => $rules]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('nis'))->toContain('Siswa Satu');
});

test('updating student rejects nis already used by another active student', function () {
    $other = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '2026001002',
        'name' => 'Siswa Dua',
        'gender' => 'P',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($other, 'RFID-NIS-002');

    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.data-siswa.update', $this->siswa), [
            'nis' => '2026001002',
            'name' => 'Siswa Satu',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => 'RFID-NIS-001',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nis']);
});

test('soft deleted student frees nis for reuse', function () {
    $this->siswa->delete();

    expect($this->siswa->fresh()->nis_key)->toBeNull();

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => '2026001001',
            'name' => 'Siswa Baru',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => 'RFID-NIS-003',
        ])
        ->assertCreated();
});

test('creating student with duplicate active nis is rejected', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => '2026001001',
            'name' => 'Duplikat',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => 'RFID-NIS-004',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['nis']);
});
