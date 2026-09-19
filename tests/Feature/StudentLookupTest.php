<?php

use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'TST',
        'name' => 'Test School',
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'sekolah_id' => $sekolah->id,
        'username' => 'admin_test',
    ]);
    $this->admin->assignRole('admin');
    $this->sekolahId = $sekolah->id;
});

test('student lookup requires at least three characters', function () {
    Siswa::create([
        'sekolah_id' => $this->sekolahId,
        'nis' => '2024000001',
        'name' => 'Ahmad Zaki',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.siswa.lookup', ['term' => 'ah']))
        ->assertOk()
        ->assertJsonPath('results', []);

    $this->actingAs($this->admin)
        ->getJson(route('admin.siswa.lookup', ['term' => 'ahm']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.text', fn ($text) => str_contains($text, 'Ahmad Zaki'));
});

test('student lookup can resolve selected record', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolahId,
        'nis' => '2024000002',
        'name' => 'Budi Santoso',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.siswa.lookup.show', $siswa))
        ->assertOk()
        ->assertJsonPath('id', $siswa->id)
        ->assertJsonPath('text', fn ($text) => str_contains($text, 'Budi Santoso'));
});
