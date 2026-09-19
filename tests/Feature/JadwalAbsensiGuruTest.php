<?php

use App\Models\Guru;
use App\Models\JadwalAbsensiGuru;
use App\Models\Sekolah;
use App\Models\User;
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

    $this->sekolahLain = Sekolah::create([
        'code' => 'OTH',
        'name' => 'Other School',
        'address' => 'Jl. Other',
    ]);
});

it('shows jadwal absensi guru index for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.absensi.jadwal-absensi-guru.index'))
        ->assertOk()
        ->assertSee('Jadwal Absensi Guru')
        ->assertSee('Tambah Jadwal');
});

it('creates jadwal absensi guru', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.absensi.jadwal-absensi-guru.store'), [
            'sekolah_id' => $this->sekolah->id,
            'name' => 'Reguler Pagi',
            'jam_masuk' => '06:45',
            'jam_pulang' => '14:30',
            'toleransi_menit' => 20,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJson(['success' => true]);

    $jadwal = JadwalAbsensiGuru::first();

    expect($jadwal)->not->toBeNull()
        ->and($jadwal->name)->toBe('Reguler Pagi')
        ->and($jadwal->jamMasukInput())->toBe('06:45')
        ->and($jadwal->jamPulangInput())->toBe('14:30')
        ->and($jadwal->toleransi_menit)->toBe(20);
});

it('assigns gurus to jadwal absensi guru', function () {
    $jadwal = JadwalAbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Shift Siang',
        'jam_masuk' => '07:00',
        'jam_pulang' => '15:00',
        'toleransi_menit' => 15,
        'is_active' => true,
    ]);

    $guru1 = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'G001',
        'name' => 'Guru Satu',
        'status' => 'aktif',
    ]);

    $guru2 = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'G002',
        'name' => 'Guru Dua',
        'status' => 'aktif',
    ]);

    Guru::create([
        'sekolah_id' => $this->sekolahLain->id,
        'nip' => 'G999',
        'name' => 'Guru Lain',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.absensi.jadwal-absensi-guru.assign', $jadwal), [
            'guru_ids' => [$guru1->id, $guru2->id],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($guru1->fresh()->jadwal_absensi_guru_id)->toBe($jadwal->id)
        ->and($guru2->fresh()->jadwal_absensi_guru_id)->toBe($jadwal->id);
});

it('filters jadwal absensi guru by sekolah in datatable', function () {
    JadwalAbsensiGuru::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'Jadwal A',
        'jam_masuk' => '07:00',
        'jam_pulang' => '15:00',
        'toleransi_menit' => 15,
        'is_active' => true,
    ]);

    JadwalAbsensiGuru::create([
        'sekolah_id' => $this->sekolahLain->id,
        'name' => 'Jadwal B',
        'jam_masuk' => '08:00',
        'jam_pulang' => '16:00',
        'toleransi_menit' => 10,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.absensi.jadwal-absensi-guru.data', ['sekolah_id' => $this->sekolah->id]))
        ->assertOk();

    $json = $response->json();

    expect(collect($json['data'])->pluck(1))->toContain('Jadwal A')
        ->and(collect($json['data'])->pluck(1))->not->toContain('Jadwal B');
});

it('redirects legacy absensi setting route to jadwal absensi guru', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.absensi.setting'))
        ->assertRedirect('/admin/absensi/hari-libur');
});
