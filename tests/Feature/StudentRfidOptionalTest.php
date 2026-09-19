<?php

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\RfidUid;
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
        'address' => 'Pekanbaru',
    ]);
    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X A',
        'is_active' => true,
    ]);
    $this->admin = User::create([
        'username' => 'admin.rfid',
        'name' => 'Admin RFID',
        'email' => 'admin-rfid@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('student can be stored and updated without rfid', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.store'), [
            'nis' => '2026010001',
            'name' => 'Tanpa RFID',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => '',
        ])
        ->assertCreated();

    $siswa = Siswa::where('nis', '2026010001')->firstOrFail();
    expect($siswa->rfidUid())->toBeNull();

    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.data-siswa.update', $siswa), [
            'nis' => '2026010001',
            'name' => 'Tanpa RFID Edit',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => '',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($siswa->fresh()->name)->toBe('Tanpa RFID Edit')
        ->and($siswa->fresh()->rfidUid())->toBeNull();
});

test('student update sanitizes rfid scanner paste and ignores own nis', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '2026010002',
        'name' => 'Dengan Scanner',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.data-siswa.update', $siswa), [
            'nis' => '2026010002',
            'name' => 'Dengan Scanner',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => "CARD99XYZ\r\n",
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($siswa->fresh()->rfidUid())->toBe('CARD99XYZ');
});

test('long nis is preserved when setting rfid on update', function () {
    $longNis = '512336041084200004';
    // Prefix collision trap: another siswa already owns the first 10 digits.
    Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => substr($longNis, 0, 10),
        'name' => 'Short Prefix Owner',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => $longNis,
        'name' => 'Long NIS RFID',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.manajemen-siswa.data-siswa.update', $siswa), [
            'nis' => $longNis,
            'name' => 'Long NIS RFID',
            'kelas_id' => $this->kelas->id,
            'status' => Siswa::STATUS_ACTIVE,
            'rfid_uid' => 'PHYSICAL-CARD-LONG-01',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($siswa->fresh()->nis)->toBe($longNis)
        ->and($siswa->fresh()->rfidUid())->toBe('PHYSICAL-CARD-LONG-01');
});

test('siswa clear auto rfid command clears legacy generated uids only', function () {
    $auto = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '2026010003',
        'name' => 'Auto RFID',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($auto, RfidUid::fromNis('2026010003', $this->sekolah->id));
    $longNis = '512336041084200004';
    $autoLong = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => $longNis,
        'name' => 'Auto Long NIS RFID',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    // Historical long-NIS form (school prefix need not match current id)
    assignRfid($autoLong, 'S01'.$longNis);
    $manual = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '2026010004',
        'name' => 'Manual RFID',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($manual, 'PHYSICAL-CARD-01');

    $this->artisan('siswa:clear-auto-rfid', ['--force' => true])
        ->assertSuccessful();

    expect($auto->fresh()->rfidUid())->toBeNull()
        ->and($autoLong->fresh()->rfidUid())->toBeNull()
        ->and($manual->fresh()->rfidUid())->toBe('PHYSICAL-CARD-01');
});
