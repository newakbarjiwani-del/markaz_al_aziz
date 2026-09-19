<?php

use App\Models\OrangTua;
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

    $this->superAdmin = User::factory()->create([
        'username' => 'superadmin_test',
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('orang tua lookup requires at least three characters', function () {
    OrangTua::create([
        'sekolah_id' => $this->admin->sekolah_id,
        'nama_ayah' => 'Bapak Hartono',
        'telepon_ayah' => '081234567890',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.orang-tua.lookup', ['term' => 'ba']))
        ->assertOk()
        ->assertJsonPath('results', []);

    $this->actingAs($this->admin)
        ->getJson(route('admin.orang-tua.lookup', ['term' => 'har']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.text', fn ($text) => str_contains($text, 'Hartono'));
});

test('orang tua lookup can resolve selected record', function () {
    $orangTua = OrangTua::create([
        'sekolah_id' => $this->admin->sekolah_id,
        'nama_ayah' => 'Bapak Joko',
        'telepon_ayah' => '081111111111',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->superAdmin)
        ->getJson(route('admin.orang-tua.lookup.show', $orangTua))
        ->assertOk()
        ->assertJsonPath('id', $orangTua->id)
        ->assertJsonPath('text', fn ($text) => str_contains($text, 'Joko'));
});
