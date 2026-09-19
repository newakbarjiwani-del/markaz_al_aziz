<?php

use App\Models\Guru;
use App\Models\Sekolah;
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

    $this->superAdmin = User::factory()->create([
        'username' => 'superadmin_test',
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('teacher lookup requires at least three characters', function () {
    Guru::create([
        'sekolah_id' => $this->sekolahId,
        'nip' => '198501019999',
        'name' => 'Ustadz Ahmad',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.guru.lookup', ['term' => 'us']))
        ->assertOk()
        ->assertJsonPath('results', []);

    $this->actingAs($this->admin)
        ->getJson(route('admin.guru.lookup', ['term' => 'ust']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.text', fn ($text) => str_contains($text, 'Ustadz Ahmad'));
});

test('teacher lookup can resolve selected record', function () {
    $guru = Guru::create([
        'sekolah_id' => $this->sekolahId,
        'nip' => '198501018888',
        'name' => 'Ustadz Budi',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->superAdmin)
        ->getJson(route('admin.guru.lookup.show', $guru))
        ->assertOk()
        ->assertJsonPath('id', $guru->id)
        ->assertJsonPath('text', fn ($text) => str_contains($text, 'Ustadz Budi'));
});
