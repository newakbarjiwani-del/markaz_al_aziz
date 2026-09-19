<?php

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\AttendanceStatus;
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

    $this->guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'GR-001',
        'name' => 'Guru Rekap',
        'jabatan' => 'Guru Mapel',
        'status' => 'aktif',
    ]);
});

it('shows rekap presensi guru page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.absensi.rekap-presensi-guru'))
        ->assertOk()
        ->assertSee('Rekap Presensi Guru');
});

it('returns monthly guru attendance summary', function () {
    AbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $this->guru->id,
        'date' => now()->toDateString(),
        'status' => AttendanceStatus::HADIR,
        'method' => 'manual',
        'jam_masuk' => '07:05',
    ]);

    AbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'guru_id' => $this->guru->id,
        'date' => now()->subDay()->toDateString(),
        'status' => AttendanceStatus::ALPHA,
        'method' => 'manual',
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.rekap-presensi-guru.data', [
            'month' => now()->format('Y-m'),
            'sekolah_id' => $this->sekolah->id,
        ]));

    $response->assertOk();
    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('Guru Rekap')
        ->and($html)->toContain('GR-001');
});
