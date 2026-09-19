<?php

use App\Models\Guru;
use App\Models\Sekolah;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->sekolahA = Sekolah::create(['code' => 'ma', 'name' => 'MA Test', 'address' => 'A']);
    $this->sekolahB = Sekolah::create(['code' => 'mts', 'name' => 'MTs Test', 'address' => 'B']);

    $this->scopedAdmin = User::create([
        'username' => 'admin.guru',
        'name' => 'Admin Guru',
        'email' => 'admin-guru@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolahA->id,
    ]);
    $this->scopedAdmin->assignRole('admin');

    $this->unscopedAdmin = User::create([
        'username' => 'admin.lintas',
        'name' => 'Admin Lintas',
        'email' => 'admin-lintas@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $this->unscopedAdmin->assignRole('admin');
});

test('scoped admin assigns guru to their school when sekolah is omitted', function () {
    $this->actingAs($this->scopedAdmin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'nip' => 'GR-LINTAS-001',
            'name' => 'Guru MA',
            'status' => 'aktif',
        ])
        ->assertCreated();

    expect(Guru::where('nip', 'GR-LINTAS-001')->value('sekolah_id'))->toBe($this->sekolahA->id);
});

test('scoped admin cannot assign guru to another school', function () {
    $this->actingAs($this->scopedAdmin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'sekolah_id' => $this->sekolahB->id,
            'nip' => 'GR-MA-020',
            'name' => 'Guru MTs',
            'status' => 'aktif',
        ])
        ->assertCreated();

    expect(Guru::where('nip', 'GR-MA-020')->value('sekolah_id'))->toBe($this->sekolahA->id);
});

test('unscoped admin can create guru without sekolah assignment', function () {
    $this->actingAs($this->unscopedAdmin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'nip' => 'GR-LINTAS-001',
            'name' => 'Guru Lintas Sekolah',
            'status' => 'aktif',
        ])
        ->assertCreated();

    expect(Guru::where('nip', 'GR-LINTAS-001')->value('sekolah_id'))->toBeNull();
});

test('unscoped admin can create guru with selected sekolah', function () {
    $this->actingAs($this->unscopedAdmin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'sekolah_id' => $this->sekolahB->id,
            'nip' => 'GR-MA-020',
            'name' => 'Guru MTs',
            'status' => 'aktif',
        ])
        ->assertCreated();

    expect(Guru::where('nip', 'GR-MA-020')->value('sekolah_id'))->toBe($this->sekolahB->id);
});

test('scoped admin teacher datatable only shows assigned school', function () {
    Guru::create([
        'sekolah_id' => $this->sekolahA->id,
        'nip' => 'GR-A-001',
        'name' => 'Guru A',
        'status' => 'aktif',
    ]);
    Guru::create([
        'sekolah_id' => $this->sekolahB->id,
        'nip' => 'GR-B-001',
        'name' => 'Guru B',
        'status' => 'aktif',
    ]);
    Guru::create([
        'sekolah_id' => null,
        'nip' => 'GR-X-001',
        'name' => 'Guru Lintas',
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->scopedAdmin)
        ->getJson(route('admin.manajemen-guru.data-guru.data'));

    $response->assertOk();
    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('Guru A')
        ->and($html)->not->toContain('Guru B')
        ->and($html)->not->toContain('Guru Lintas');
});

test('unscoped admin teacher datatable can filter by sekolah', function () {
    Guru::create([
        'sekolah_id' => $this->sekolahA->id,
        'nip' => 'GR-A-001',
        'name' => 'Guru A',
        'status' => 'aktif',
    ]);
    Guru::create([
        'sekolah_id' => $this->sekolahB->id,
        'nip' => 'GR-B-001',
        'name' => 'Guru B',
        'status' => 'aktif',
    ]);
    Guru::create([
        'sekolah_id' => null,
        'nip' => 'GR-X-001',
        'name' => 'Guru Lintas',
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->unscopedAdmin)
        ->getJson(route('admin.manajemen-guru.data-guru.data', ['sekolah_id' => $this->sekolahA->id]));

    $response->assertOk();
    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('Guru A')
        ->and($html)->not->toContain('Guru B')
        ->and($html)->not->toContain('Guru Lintas');
});

test('unscoped admin data guru page includes sekolah filter', function () {
    $this->actingAs($this->unscopedAdmin)
        ->get(route('admin.manajemen-guru.data-guru.index'))
        ->assertOk()
        ->assertSee('Semua Sekolah')
        ->assertSee('Tanpa sekolah / lintas sekolah');
});

test('scoped admin can store and update guru rfid uid', function () {
    $this->actingAs($this->scopedAdmin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'nip' => 'GR-RFID-001',
            'name' => 'Guru RFID',
            'rfid_uid' => 'RFID-GURU-TEST-001',
            'status' => 'aktif',
        ])
        ->assertCreated();

    $guru = Guru::where('nip', 'GR-RFID-001')->first();
    expect($guru->rfidUid())->toBe('RFID-GURU-TEST-001');

    $this->actingAs($this->scopedAdmin)
        ->putJson(route('admin.manajemen-guru.data-guru.update', $guru), [
            'nip' => 'GR-RFID-001',
            'name' => 'Guru RFID',
            'rfid_uid' => 'RFID-GURU-TEST-002',
            'status' => 'aktif',
        ])
        ->assertOk();

    expect($guru->fresh()->rfidUid())->toBe('RFID-GURU-TEST-002');
});

test('guru rfid uid must be unique', function () {
    $existing = Guru::create([
        'sekolah_id' => $this->sekolahA->id,
        'nip' => 'GR-RFID-EXIST',
        'name' => 'Guru Existing',
        'status' => 'aktif',
    ]);
    assignRfid($existing, 'RFID-DUPLICATE');

    $this->actingAs($this->scopedAdmin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'nip' => 'GR-RFID-NEW',
            'name' => 'Guru Baru',
            'rfid_uid' => 'RFID-DUPLICATE',
            'status' => 'aktif',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rfid_uid']);
});
