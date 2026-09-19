<?php

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsenHari;
use App\Models\JadwalAbsenSlot;
use App\Models\Kelas;
use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->sekolahA = Sekolah::create(['code' => 'ma', 'name' => 'MA Ittihad', 'address' => 'A']);
    $this->sekolahB = Sekolah::create(['code' => 'mts', 'name' => 'MTs Ittihad', 'address' => 'B']);

    $this->kelasA = Kelas::create(['sekolah_id' => $this->sekolahA->id, 'name' => 'X IPA 1']);
    $this->kelasB = Kelas::create(['sekolah_id' => $this->sekolahB->id, 'name' => 'VII A']);

    $this->siswa1 = Siswa::create([
        'sekolah_id' => $this->sekolahA->id,
        'kelas_id' => $this->kelasA->id,
        'nis' => '1001',
        'name' => 'Ahmad Bilal',
        'status' => 1,
    ]);

    $this->siswa2 = Siswa::create([
        'sekolah_id' => $this->sekolahB->id,
        'kelas_id' => $this->kelasB->id,
        'nis' => '1002',
        'name' => 'Budi Santoso',
        'status' => 1,
    ]);

    $this->pelajaran1 = Pelajaran::create([
        'sekolah_id' => $this->sekolahA->id,
        'name' => 'Fisika',
        'is_active' => true,
    ]);

    $this->pelajaran2 = Pelajaran::create([
        'sekolah_id' => $this->sekolahB->id,
        'name' => 'Matematika',
        'is_active' => true,
    ]);

    $this->jadwal1 = JadwalAbsen::create([
        'sekolah_id' => $this->sekolahA->id,
        'name' => 'Presensi Pagi MA',
        'assignment_type' => 'sekolah',
    ]);

    $this->jadwal2 = JadwalAbsen::create([
        'sekolah_id' => $this->sekolahB->id,
        'name' => 'Presensi Pagi MTs',
        'assignment_type' => 'sekolah',
    ]);

    $this->hari1 = JadwalAbsenHari::create(['jadwal_absen_id' => $this->jadwal1->id, 'day_of_week' => 1, 'is_active' => true]);
    $this->hari2 = JadwalAbsenHari::create(['jadwal_absen_id' => $this->jadwal2->id, 'day_of_week' => 1, 'is_active' => true]);

    $this->guru = Guru::create([
        'sekolah_id' => $this->sekolahA->id,
        'nip' => 'G101',
        'name' => 'Guru Pengajar',
        'status' => 'aktif',
    ]);

    $this->slot1 = JadwalAbsenSlot::create([
        'jadwal_absen_hari_id' => $this->hari1->id,
        'pelajaran_id' => $this->pelajaran1->id,
        'guru_id' => $this->guru->id,
        'time_start' => '07:00',
        'time_end' => '08:00',
    ]);

    $this->slot2 = JadwalAbsenSlot::create([
        'jadwal_absen_hari_id' => $this->hari2->id,
        'pelajaran_id' => $this->pelajaran2->id,
        'guru_id' => $this->guru->id,
        'time_start' => '07:00',
        'time_end' => '08:00',
    ]);

    $this->admin = User::create([
        'username' => 'admin.absensi.filter',
        'name' => 'Admin Absensi Filter',
        'email' => 'admin-absensi-filter@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->admin->assignRole('super_admin');
});

test('absensi siswa index renders with all filter parameters', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.absensi.absensi-siswa.index'))
        ->assertOk()
        ->assertViewHas('schools')
        ->assertViewHas('classes')
        ->assertViewHas('jadwals')
        ->assertViewHas('pelajarans');
});

test('absensi siswa data can be filtered by sekolah, kelas, jadwal_absen, pelajaran, status, method, and date range', function () {
    $rec1 = AbsensiSiswa::create([
        'sekolah_id' => $this->sekolahA->id,
        'siswa_id' => $this->siswa1->id,
        'jadwal_absen_slot_id' => $this->slot1->id,
        'date' => '2026-08-01',
        'status' => 'hadir',
        'method' => 'rfid',
        'time_in' => '07:05:00',
    ]);

    $rec2 = AbsensiSiswa::create([
        'sekolah_id' => $this->sekolahB->id,
        'siswa_id' => $this->siswa2->id,
        'jadwal_absen_slot_id' => $this->slot2->id,
        'date' => '2026-08-02',
        'status' => 'izin',
        'method' => 'manual',
        'time_in' => null,
    ]);

    // Test filter by sekolah_id
    $resSekolah = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.absensi-siswa.data', ['sekolah_id' => $this->sekolahA->id]));
    $resSekolah->assertOk();
    expect(count($resSekolah->json('data')))->toBe(1)
        ->and($resSekolah->json('data.0.0'))->toBe('1001')
        ->and($resSekolah->json('data.0.7'))->toBe('07:05 (RFID)');

    // Test filter by kelas_id
    $resKelas = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.absensi-siswa.data', ['kelas_id' => $this->kelasB->id]));
    $resKelas->assertOk();
    expect(count($resKelas->json('data')))->toBe(1)
        ->and($resKelas->json('data.0.0'))->toBe('1002');

    // Test filter by jadwal_absen_id
    $resJadwal = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.absensi-siswa.data', ['jadwal_absen_id' => $this->jadwal1->id]));
    $resJadwal->assertOk();
    expect(count($resJadwal->json('data')))->toBe(1)
        ->and($resJadwal->json('data.0.0'))->toBe('1001');

    // Test filter by pelajaran_id
    $resPelajaran = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.absensi-siswa.data', ['pelajaran_id' => $this->pelajaran2->id]));
    $resPelajaran->assertOk();
    expect(count($resPelajaran->json('data')))->toBe(1)
        ->and($resPelajaran->json('data.0.0'))->toBe('1002');

    // Test filter by status
    $resStatus = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.absensi-siswa.data', ['status' => 'hadir']));
    $resStatus->assertOk();
    expect(count($resStatus->json('data')))->toBe(1)
        ->and($resStatus->json('data.0.0'))->toBe('1001');

    // Test filter by method
    $resMethod = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.absensi-siswa.data', ['method' => 'manual']));
    $resMethod->assertOk();
    expect(count($resMethod->json('data')))->toBe(1)
        ->and($resMethod->json('data.0.0'))->toBe('1002');

    // Test filter by date range
    $resDate = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.absensi-siswa.data', [
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-01',
        ]));
    $resDate->assertOk();
    expect(count($resDate->json('data')))->toBe(1)
        ->and($resDate->json('data.0.0'))->toBe('1001');
});
