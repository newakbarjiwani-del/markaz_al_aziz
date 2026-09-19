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
use App\Services\GuruTeachingScope;
use App\Support\Weekday;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->sekolah = Sekolah::create(['code' => 'ma', 'name' => 'MA Test', 'address' => 'A']);

    $this->kelasA = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'kelas' => 'X',
        'kelompok' => 'IPA 1',
        'is_active' => true,
    ]);
    $this->kelasB = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 2',
        'kelas' => 'X',
        'kelompok' => 'IPA 2',
        'is_active' => true,
    ]);
    $this->kelasOther = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'XI IPS 1',
        'kelas' => 'XI',
        'kelompok' => 'IPS 1',
        'is_active' => true,
    ]);

    $this->guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'GR-PORTAL-001',
        'name' => 'Guru Portal',
        'status' => 'aktif',
    ]);

    $this->otherGuru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'GR-PORTAL-002',
        'name' => 'Guru Lain',
        'status' => 'aktif',
    ]);

    $this->pelajaran = Pelajaran::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Matematika',
        'is_active' => true,
    ]);

    $this->siswaA = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelasA->id,
        'nis' => '9001',
        'name' => 'Siswa A',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    $this->siswaB = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelasB->id,
        'nis' => '9002',
        'name' => 'Siswa B',
        'gender' => 'P',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    $this->siswaOther = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelasOther->id,
        'nis' => '9003',
        'name' => 'Siswa Other',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->guruUser = User::create([
        'username' => 'guru.portal',
        'name' => 'Guru Portal User',
        'email' => 'guru-portal@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'guru_id' => $this->guru->id,
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->guruUser->assignRole('guru');

    $this->jadwal = JadwalAbsen::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Jadwal Multi Kelas',
        'assignment_type' => 'kelas',
        'is_active' => true,
    ]);
    $this->jadwal->kelas()->sync([$this->kelasA->id, $this->kelasB->id]);

    $hari = JadwalAbsenHari::create([
        'jadwal_absen_id' => $this->jadwal->id,
        'day_of_week' => Weekday::fromDate(now()),
        'is_active' => true,
    ]);

    $this->slot = JadwalAbsenSlot::create([
        'jadwal_absen_hari_id' => $hari->id,
        'pelajaran_id' => $this->pelajaran->id,
        'guru_id' => $this->guru->id,
        'time_start' => '07:00',
        'time_end' => '08:00',
        'tolerance_minutes' => 15,
        'sort_order' => 0,
    ]);

    AbsensiSiswa::create([
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $this->siswaA->id,
        'jadwal_absen_slot_id' => $this->slot->id,
        'date' => now()->toDateString(),
        'status' => 'hadir',
        'method' => 'manual',
        'time_in' => '07:05',
    ]);

    // Attendance for another teacher / class must stay out of portal guru scope.
    $otherHari = JadwalAbsenHari::create([
        'jadwal_absen_id' => $this->jadwal->id,
        'day_of_week' => Weekday::fromDate(now()->addDay()),
        'is_active' => true,
    ]);
    $otherSlot = JadwalAbsenSlot::create([
        'jadwal_absen_hari_id' => $otherHari->id,
        'pelajaran_id' => $this->pelajaran->id,
        'guru_id' => $this->otherGuru->id,
        'time_start' => '09:00',
        'time_end' => '10:00',
        'tolerance_minutes' => 15,
        'sort_order' => 0,
    ]);
    AbsensiSiswa::create([
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $this->siswaOther->id,
        'jadwal_absen_slot_id' => $otherSlot->id,
        'date' => now()->toDateString(),
        'status' => 'hadir',
        'method' => 'manual',
        'time_in' => '09:05',
    ]);
});

test('guru teaching scope lists all kelas from multi-kelas jadwal', function () {
    $labels = GuruTeachingScope::for($this->guru)->classLabels();

    expect($labels)->toContain('X IPA 1')
        ->and($labels)->toContain('X IPA 2')
        ->and($labels)->not->toContain('XI IPS 1');
});

test('guru dashboard uses jadwal-based kelas and today attendance count', function () {
    $this->actingAs($this->guruUser)
        ->get(route('portal.guru.dashboard'))
        ->assertOk()
        ->assertSee('X IPA 1')
        ->assertSee('X IPA 2')
        ->assertSee('Kelas & Penugasan Absensi');
});

test('guru absensi siswa lists students from all assigned kelas', function () {
    $this->actingAs($this->guruUser)
        ->getJson(route('portal.guru.absensi-siswa.students', ['slot_id' => $this->slot->id]))
        ->assertOk()
        ->assertJsonPath('data.summary.total', 2)
        ->assertJsonFragment(['nis' => '9001'])
        ->assertJsonFragment(['nis' => '9002'])
        ->assertJsonMissing(['nis' => '9003']);
});

test('guru students endpoint omits foto_wajah to avoid memory exhaustion', function () {
    assignFacePhoto($this->siswaA, str_repeat('A', 50000));
    assignFacePhoto($this->siswaB, str_repeat('B', 50000));

    $response = $this->actingAs($this->guruUser)
        ->getJson(route('portal.guru.absensi-siswa.students', ['slot_id' => $this->slot->id]))
        ->assertOk()
        ->assertJsonPath('data.summary.total', 2);

    $payload = json_encode($response->json());
    expect($payload)->not->toContain(str_repeat('A', 100))
        ->and($response->json('data.students.0'))->not->toHaveKey('foto_wajah');
});

test('guru absensi siswa lists students even when jadwal sekolah_id differs from siswa sekolah', function () {
    // Multi-kelas jadwal saved under sekolah A, but kelas/siswa belong to same classes
    // without requiring jadwal.sekolah_id match (regression: empty student list).
    $this->jadwal->update(['sekolah_id' => Sekolah::create([
        'code' => 'other',
        'name' => 'Other School',
        'address' => 'X',
    ])->id]);

    $response = $this->actingAs($this->guruUser)
        ->getJson(route('portal.guru.absensi-siswa.students', ['slot_id' => $this->slot->id]))
        ->assertOk()
        ->assertJsonPath('data.summary.total', 2);

    $kelasIds = $response->json('data.assignment.kelas_ids');
    expect($kelasIds)->toContain($this->kelasA->id)
        ->and($kelasIds)->toContain($this->kelasB->id);
});

test('guru rekap only shows attendance from own jadwal slots', function () {
    $response = $this->actingAs($this->guruUser)
        ->getJson(route('portal.guru.rekap-siswa.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
        ->assertOk();

    $html = collect($response->json('data'))->flatten()->implode(' ');

    expect($html)->toContain('Siswa A')
        ->and($html)->not->toContain('Siswa Other');
});
