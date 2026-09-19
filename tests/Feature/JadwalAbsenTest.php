<?php

use App\Models\JadwalAbsen;
use App\Models\Kelas;
use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Guru;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolahA = Sekolah::create(['code' => 'ma', 'name' => 'MA', 'address' => 'A']);
    $this->sekolahB = Sekolah::create(['code' => 'mts', 'name' => 'MTs', 'address' => 'B']);

    $this->superAdmin = User::create([
        'username' => 'super.jadwal',
        'name' => 'Super Jadwal',
        'email' => 'super-jadwal@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->superAdmin->assignRole('super_admin');

    $this->kelasA = Kelas::create([
        'sekolah_id' => $this->sekolahA->id,
        'name' => 'X A',
        'unit' => 'MA',
        'jenjang' => 'X',
        'is_active' => true,
    ]);
    $this->kelasB = Kelas::create([
        'sekolah_id' => $this->sekolahB->id,
        'name' => 'IX B',
        'unit' => 'MTs',
        'jenjang' => 'IX',
        'is_active' => true,
    ]);

    $this->pelajaran = Pelajaran::create([
        'sekolah_id' => $this->sekolahA->id,
        'name' => 'Matematika',
        'is_active' => true,
    ]);
    $this->guru = Guru::create([
        'sekolah_id' => $this->sekolahA->id,
        'nip' => 'GURU-001',
        'name' => 'Guru Satu',
        'status' => 'aktif',
    ]);

    Siswa::create([
        'sekolah_id' => $this->sekolahA->id,
        'kelas_id' => $this->kelasA->id,
        'nis' => '1001',
        'name' => 'Siswa MA',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);
    Siswa::create([
        'sekolah_id' => $this->sekolahB->id,
        'kelas_id' => $this->kelasB->id,
        'nis' => '2001',
        'name' => 'Siswa MTs',
        'gender' => 'P',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);
});

function jadwalDayPayload(): array
{
    return [
        1 => [
            'active' => '1',
            'slots' => [[
                'pelajaran_id' => test()->pelajaran->id,
                'guru_id' => test()->guru->id,
                'time_start' => '07:00',
                'time_end' => '08:00',
                'tolerance_minutes' => '15',
            ]],
        ],
    ];
}

test('super admin can create sekolah assignment for all schools when sekolah is all', function () {
    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.absensi.jadwal-absen.store'), [
            'sekolah_id' => 'all',
            'name' => 'Jadwal Semua Sekolah',
            'assignment_type' => 'sekolah',
            'is_active' => '1',
            'days' => jadwalDayPayload(),
        ])
        ->assertCreated();

    $jadwal = JadwalAbsen::first();

    expect($jadwal?->sekolah_id)->toBeNull()
        ->and($jadwal?->assignment_type)->toBe('sekolah')
        ->and($jadwal?->resolvedStudentCount())->toBe(2)
        ->and($jadwal?->assignmentLabel())->toBe('Semua siswa (semua sekolah)');
});

test('super admin can create cross school kelas jadwal absen', function () {
    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.absensi.jadwal-absen.store'), [
            'sekolah_id' => 'all',
            'name' => 'Jadwal Lintas Kelas',
            'assignment_type' => 'kelas',
            'kelas_ids' => [$this->kelasA->id, $this->kelasB->id],
            'is_active' => '1',
            'days' => jadwalDayPayload(),
        ])
        ->assertCreated();

    $jadwal = JadwalAbsen::with('kelas')->first();

    expect($jadwal?->sekolah_id)->toBeNull()
        ->and($jadwal?->kelas)->toHaveCount(2)
        ->and($jadwal?->resolvedStudentCount())->toBe(2);
});

test('preview students counts all students when sekolah all and assignment sekolah', function () {
    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.absensi.jadwal-absen.preview-students'), [
            'sekolah_id' => 'all',
            'assignment_type' => 'sekolah',
        ])
        ->assertOk()
        ->assertJsonPath('data.count', 2);
});
