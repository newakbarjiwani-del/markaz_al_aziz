<?php

use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\JadwalAbsensiGuru;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\GuruJadwalAttendanceService;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

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
});

it('allows admin to manage hari libur', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.hari-libur.store'), [
            'sekolah_id' => $this->sekolah->id,
            'applies_to' => 'both',
            'notes' => 'Catatan umum',
            'entries' => [
                ['date' => '2026-07-10', 'name' => 'Libur Semester'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.count', 1);

    $this->assertDatabaseHas('hari_libur', [
        'name' => 'Libur Semester',
        'applies_to' => 'both',
        'notes' => 'Catatan umum',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.absensi.hari-libur.index'))
        ->assertOk()
        ->assertSee('Hari Libur');
});

it('allows admin to store multiple hari libur at once', function () {
    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.hari-libur.store'), [
            'sekolah_id' => $this->sekolah->id,
            'applies_to' => 'guru',
            'entries' => [
                ['date' => '2026-07-20', 'name' => 'Libur 1'],
                ['date' => '2026-07-21', 'name' => 'Libur 2'],
                ['date' => '2026-07-22', 'name' => 'Libur 3'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.count', 3);

    $this->assertDatabaseCount('hari_libur', 3);

    $this->actingAs($this->admin)
        ->putJson(route('admin.absensi.hari-libur.update', $response->json('data.items.0.id')), [
            'sekolah_id' => $this->sekolah->id,
            'date' => '2026-07-20',
            'name' => 'Libur 1 Diubah',
            'applies_to' => 'guru',
            'notes' => 'Catatan edit',
        ])
        ->assertOk();

    $this->assertDatabaseHas('hari_libur', [
        'name' => 'Libur 1 Diubah',
        'notes' => 'Catatan edit',
    ]);
});

it('blocks guru attendance on hari libur', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-10 08:00:00'));

    $jadwal = JadwalAbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Reguler',
        'jam_masuk' => '07:00',
        'jam_pulang' => '15:00',
        'toleransi_menit' => 15,
        'is_active' => true,
    ]);

    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'jadwal_absensi_guru_id' => $jadwal->id,
        'nip' => 'G001',
        'name' => 'Guru Test',
        'status' => 'aktif',
    ]);

    HariLibur::create([
        'sekolah_id' => $this->sekolah->id,
        'date' => '2026-07-10',
        'name' => 'Libur Nasional',
        'applies_to' => HariLibur::APPLIES_GURU,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $jadwal), [
            'method' => 'manual',
            'guru_id' => $guru->id,
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    Carbon::setTestNow();
});

it('records manual alpha status for guru without jam masuk', function () {
    $jadwal = JadwalAbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Reguler',
        'jam_masuk' => '07:00',
        'jam_pulang' => '15:00',
        'toleransi_menit' => 15,
        'is_active' => true,
    ]);

    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'jadwal_absensi_guru_id' => $jadwal->id,
        'nip' => 'G002',
        'name' => 'Guru Alpha',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.absensi.store', $jadwal), [
            'method' => 'manual',
            'guru_id' => $guru->id,
            'status' => AttendanceStatus::ALPHA,
        ])
        ->assertCreated()
        ->assertJson(['success' => true, 'data' => ['action' => GuruJadwalAttendanceService::ACTION_ABSEN]]);

    $this->assertDatabaseHas('absensi_guru', [
        'guru_id' => $guru->id,
        'status' => AttendanceStatus::ALPHA,
        'jam_masuk' => null,
    ]);
});
