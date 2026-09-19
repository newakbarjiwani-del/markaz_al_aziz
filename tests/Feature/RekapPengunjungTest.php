<?php

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\PengunjungPerpustakaan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'TST',
        'name' => 'Test School',
        'address' => 'Jl. Test',
    ]);

    $kelas = Kelas::create([
        'sekolah_id' => $sekolah->id,
        'name' => 'Kelas 7A',
        'level' => '7',
    ]);

    $this->sekolah = $sekolah;
    $this->kelas = $kelas;

    $this->siswa = Siswa::create([
        'sekolah_id' => $sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '70001',
        'name' => 'Siswa Test',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    assignRfid($this->siswa, 'RFID-SISWA-001');

    $this->guru = Guru::create([
        'sekolah_id' => $sekolah->id,
        'nip' => '1980010101',
        'name' => 'Guru Test',
        'jabatan' => 'Guru Mapel',
        'status' => 'aktif',
    ]);
    assignRfid($this->guru, 'RFID-GURU-001');

    $this->perpustakaan = User::create([
        'username' => 'perpustakaan.test',
        'name' => 'Perpustakaan Test',
        'email' => 'perpustakaan@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);
    $this->perpustakaan->assignRole('perpustakaan');
});

test('perpustakaan can access rekap pengunjung page', function () {
    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.rekap-pengunjung.index'))
        ->assertOk()
        ->assertSee('Rekap Pengunjung')
        ->assertSee('RFID');
});

test('perpustakaan can record siswa visitor manually', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.store'), [
            'visitor_type' => 'siswa',
            'method' => 'manual',
            'siswa_id' => $this->siswa->id,
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);
});

test('perpustakaan can record guru visitor manually', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.store'), [
            'visitor_type' => 'guru',
            'method' => 'manual',
            'guru_id' => $this->guru->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('pengunjung_perpustakaan', [
        'visitor_type' => 'guru',
        'guru_id' => $this->guru->id,
        'nama' => 'Guru Test',
        'nis' => '1980010101',
    ]);
});

test('perpustakaan can record karyawan visitor without face photo', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.store'), [
            'visitor_type' => 'karyawan',
            'method' => 'manual',
            'nama' => 'Staff TU',
            'asal' => 'Tata Usaha',
        ])
        ->assertCreated();

    expect(PengunjungPerpustakaan::first())
        ->visitor_type->toBe('karyawan')
        ->nama->toBe('Staff TU')
        ->hasFotoWajah()->toBeFalse();
});

test('perpustakaan can record non siswa visitor without face photo', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.store'), [
            'visitor_type' => 'non_siswa',
            'method' => 'manual',
            'nama' => 'Tamu Umum',
            'asal' => 'SMP Luar',
        ])
        ->assertCreated();
});

test('perpustakaan can record visitor via rfid for siswa', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.store'), [
            'visitor_type' => 'siswa',
            'method' => 'rfid',
            'rfid_uid' => 'RFID-SISWA-001',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('pengunjung_perpustakaan', [
        'visitor_type' => 'siswa',
        'siswa_id' => $this->siswa->id,
        'method' => 'rfid',
        'rfid_uid' => 'RFID-SISWA-001',
    ]);
});

test('perpustakaan can resolve rfid for guru', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.resolve-rfid'), [
            'rfid_uid' => 'RFID-GURU-001',
        ])
        ->assertOk()
        ->assertJsonPath('data.visitor.visitor_type', 'guru')
        ->assertJsonPath('data.visitor.nama', 'Guru Test');
});

test('perpustakaan scoped to sekolah can access guru without sekolah assignment', function () {
    $sharedGuru = Guru::create([
        'sekolah_id' => null,
        'nip' => 'GR-SHARED-001',
        'name' => 'Guru Shared',
        'jabatan' => 'Guru Umum',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->perpustakaan)
        ->getJson(route('portal.perpustakaan.rekap-pengunjung.guru-lookup.show', $sharedGuru))
        ->assertOk()
        ->assertJsonPath('id', $sharedGuru->id);

    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.store'), [
            'visitor_type' => 'guru',
            'method' => 'manual',
            'guru_id' => $sharedGuru->id,
        ])
        ->assertCreated();
});

test('perpustakaan user without sekolah can access rekap pengunjung page', function () {
    $perpustakaan = User::create([
        'username' => 'perpustakaan.all',
        'name' => 'Perpustakaan Semua Sekolah',
        'email' => 'perpustakaan-all@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $perpustakaan->assignRole('perpustakaan');

    $this->actingAs($perpustakaan)
        ->get(route('portal.perpustakaan.rekap-pengunjung.index'))
        ->assertOk()
        ->assertSee('Rekap Pengunjung');
});

test('perpustakaan without sekolah can record siswa visitor using entity sekolah', function () {
    $perpustakaan = User::create([
        'username' => 'perpustakaan.all',
        'name' => 'Perpustakaan Semua Sekolah',
        'email' => 'perpustakaan-all@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $perpustakaan->assignRole('perpustakaan');

    $this->actingAs($perpustakaan)
        ->postJson(route('portal.perpustakaan.rekap-pengunjung.store'), [
            'visitor_type' => 'siswa',
            'method' => 'manual',
            'siswa_id' => $this->siswa->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('pengunjung_perpustakaan', [
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
    ]);
});

test('perpustakaan lookup is not scoped to sekolah', function () {
    $otherSchool = Sekolah::create([
        'code' => 'OTH',
        'name' => 'Other School',
        'address' => 'Jl. Other',
    ]);

    Siswa::create([
        'sekolah_id' => $otherSchool->id,
        'nis' => '90001',
        'name' => 'Siswa Lain',
        'gender' => 'P',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->perpustakaan)
        ->getJson(route('portal.perpustakaan.rekap-pengunjung.lookup', ['term' => 'Siswa Lain']))
        ->assertOk()
        ->assertJsonPath('results.0.text', 'Siswa Lain · NIS 90001');
});
