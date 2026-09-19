<?php

use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

    $this->sekolahA = Sekolah::create(['code' => 'ma', 'name' => 'MA', 'address' => 'A']);
    $this->sekolahB = Sekolah::create(['code' => 'mts', 'name' => 'MTs', 'address' => 'B']);

    $this->superAdmin = User::create([
        'username' => 'super.pelajaran',
        'name' => 'Super Pelajaran',
        'email' => 'super-pelajaran@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('super admin can create pelajaran for all schools', function () {
    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.absensi.pelajaran.store'), [
            'sekolah_id' => 'all',
            'name' => 'Upacara',
            'code' => 'UPC',
            'is_active' => '1',
        ])
        ->assertCreated();

    $pelajaran = Pelajaran::first();

    expect($pelajaran?->sekolah_id)->toBeNull()
        ->and($pelajaran?->name)->toBe('Upacara')
        ->and($pelajaran?->coversAllSchools())->toBeTrue();
});

test('pelajaran name is unique per scope including global', function () {
    Pelajaran::create([
        'sekolah_id' => null,
        'name' => 'Upacara',
        'is_active' => true,
    ]);

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.absensi.pelajaran.store'), [
            'sekolah_id' => 'all',
            'name' => 'Upacara',
            'is_active' => '1',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    $this->actingAs($this->superAdmin)
        ->postJson(route('admin.absensi.pelajaran.store'), [
            'sekolah_id' => $this->sekolahA->id,
            'name' => 'Upacara',
            'is_active' => '1',
        ])
        ->assertCreated();
});

test('filtering pelajaran by sekolah includes global pelajaran', function () {
    Pelajaran::create([
        'sekolah_id' => null,
        'name' => 'Upacara',
        'is_active' => true,
    ]);
    Pelajaran::create([
        'sekolah_id' => $this->sekolahA->id,
        'name' => 'Matematika',
        'is_active' => true,
    ]);
    Pelajaran::create([
        'sekolah_id' => $this->sekolahB->id,
        'name' => 'Fisika',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('admin.absensi.pelajaran.data', [
            'sekolah_id' => $this->sekolahA->id,
            'length' => 100,
        ]));

    $response->assertOk();

    $names = collect($response->json('data'))->pluck(2)->all();

    expect($names)->toContain('Upacara', 'Matematika')
        ->not->toContain('Fisika');
});
