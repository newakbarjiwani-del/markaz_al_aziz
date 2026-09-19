<?php

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\JadwalAbsensiGuru;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\GuruJadwalAttendanceService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create([
        'username' => 'admin',
        'email' => 'admin@test.local',
        'password' => Hash::make('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->sekolah = Sekolah::create([
        'code' => 'TST',
        'name' => 'Test School',
        'address' => 'Jl. Test',
    ]);

    $this->jadwal = JadwalAbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Reguler',
        'jam_masuk' => '07:00',
        'jam_pulang' => '15:00',
        'toleransi_menit' => 15,
        'is_active' => true,
    ]);

    $this->guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'jadwal_absensi_guru_id' => $this->jadwal->id,
        'nip' => 'G001',
        'name' => 'Guru Test',
        'status' => 'aktif',
    ]);
    assignRfid($this->guru, 'RFID-GURU-001');
});

it('shows dedicated guru attendance page for jadwal', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.absensi.jadwal-absensi-guru.absensi', $this->jadwal))
        ->assertOk()
        ->assertSee('Absensi Guru')
        ->assertSee('RFID')
        ->assertSee('Manual')
        ->assertSee('Guru Test');
});

it('records manual check-in for guru in jadwal', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 07:05:00'));

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'manual',
            'guru_id' => $this->guru->id,
        ])
        ->assertCreated()
        ->assertJson(['success' => true, 'data' => ['action' => GuruJadwalAttendanceService::ACTION_MASUK]]);

    $this->assertDatabaseHas('absensi_guru', [
        'guru_id' => $this->guru->id,
        'jadwal_absensi_guru_id' => $this->jadwal->id,
        'method' => 'manual',
    ]);

    $row = AbsensiGuru::query()->where('guru_id', $this->guru->id)->first();
    expect($row->jam_masuk)->not->toBeNull();
    expect($row->jam_keluar)->toBeNull();

    Carbon::setTestNow();
});

it('auto records check-in and check-out on first manual attendance after jam pulang', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 15:05:00'));

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'manual',
            'guru_id' => $this->guru->id,
        ])
        ->assertCreated()
        ->assertJson(['success' => true, 'data' => ['action' => GuruJadwalAttendanceService::ACTION_MASUK_PULANG]]);

    $row = AbsensiGuru::query()->where('guru_id', $this->guru->id)->first();
    expect($row)->not->toBeNull();
    expect($row->jam_masuk)->not->toBeNull();
    expect($row->jam_keluar)->not->toBeNull();
    expect($row->method)->toBe('manual');
    expect($row->method_keluar)->toBe('manual');
    expect($row->status_pulang)->toBe(AbsensiGuru::STATUS_PULANG_TEPAT);

    Carbon::setTestNow();
});

it('records rfid check-in for guru in jadwal', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'rfid',
            'rfid_uid' => 'RFID-GURU-001',
        ])
        ->assertCreated()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('absensi_guru', [
        'guru_id' => $this->guru->id,
        'method' => 'rfid',
    ]);
});

it('records check-out on second scan same day', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 07:05:00'));

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'manual',
            'guru_id' => $this->guru->id,
        ])
        ->assertCreated();

    Carbon::setTestNow(Carbon::parse('2026-07-06 15:05:00'));

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'rfid',
            'rfid_uid' => 'RFID-GURU-001',
        ])
        ->assertCreated()
        ->assertJson(['success' => true, 'data' => ['action' => GuruJadwalAttendanceService::ACTION_PULANG]]);

    $row = AbsensiGuru::query()->where('guru_id', $this->guru->id)->first();
    expect($row->jam_masuk)->not->toBeNull();
    expect($row->jam_keluar)->not->toBeNull();
    expect($row->status_pulang)->not->toBeNull();
    expect($row->method_keluar)->toBe('rfid');

    Carbon::setTestNow();
});

it('rejects immediate double manual submit from becoming pulang', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 07:05:00'));

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'manual',
            'guru_id' => $this->guru->id,
        ])
        ->assertCreated()
        ->assertJson(['success' => true, 'data' => ['action' => GuruJadwalAttendanceService::ACTION_MASUK]]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'manual',
            'guru_id' => $this->guru->id,
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $row = AbsensiGuru::query()->where('guru_id', $this->guru->id)->first();
    expect($row)->not->toBeNull();
    expect($row->jam_masuk)->not->toBeNull();
    expect($row->jam_keluar)->toBeNull();

    Carbon::setTestNow();
});

it('rejects attendance after check-in and check-out completed', function () {
    AbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $this->guru->id,
        'jadwal_absensi_guru_id' => $this->jadwal->id,
        'date' => now()->toDateString(),
        'status' => 'hadir',
        'method' => 'manual',
        'jam_masuk' => '07:05',
        'jam_keluar' => '15:10',
        'status_pulang' => AbsensiGuru::STATUS_PULANG_TEPAT,
        'method_keluar' => 'rfid',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $this->jadwal), [
            'method' => 'rfid',
            'rfid_uid' => 'RFID-GURU-001',
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('marks terlambat when check-in exceeds tolerance', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 07:20:00'));

    $service = app(GuruJadwalAttendanceService::class);
    $result = $service->record($this->jadwal, $this->guru, 'manual');

    expect($result['attendance']->status)->toBe(AbsensiGuru::STATUS_TERLAMBAT);

    Carbon::setTestNow();
});

it('marks pulang awal when check-out before jam pulang', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 07:05:00'));

    $service = app(GuruJadwalAttendanceService::class);
    $service->record($this->jadwal, $this->guru, 'manual');

    Carbon::setTestNow(Carbon::parse('2026-07-06 14:30:00'));
    $result = $service->record($this->jadwal, $this->guru, 'rfid');

    expect($result['attendance']->status_pulang)->toBe(AbsensiGuru::STATUS_PULANG_AWAL);

    Carbon::setTestNow();
});

it('marks pulang tepat when check-out at or after jam pulang', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06 07:05:00'));

    $service = app(GuruJadwalAttendanceService::class);
    $service->record($this->jadwal, $this->guru, 'manual');

    Carbon::setTestNow(Carbon::parse('2026-07-06 15:05:00'));
    $result = $service->record($this->jadwal, $this->guru, 'rfid');

    expect($result['attendance']->status_pulang)->toBe(AbsensiGuru::STATUS_PULANG_TEPAT);

    Carbon::setTestNow();
});
