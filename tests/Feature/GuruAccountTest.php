<?php

use App\Models\Guru;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\PortalUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Tigamaya',
    ]);

    $this->admin = User::create([
        'username' => 'admin.guru',
        'name' => 'Admin Guru',
        'email' => 'admin-guru@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->admin->assignRole('admin');

    $this->guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '198501012010',
        'name' => 'Guru Import Test',
        'jabatan' => 'Guru Mapel',
        'status' => 'aktif',
    ]);
});

test('admin can create guru account with custom password', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-guru.data-guru.create-account', $this->guru), [
            'password' => 'GuruBaru123',
            'password_confirmation' => 'GuruBaru123',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.username', '198501012010');

    $user = User::where('guru_id', $this->guru->id)->first();
    expect($user)->not->toBeNull();
    expect(Hash::check('GuruBaru123', $user->password))->toBeTrue();
});

test('guru account username preserves formatted nip segments', function () {
    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => 'GR-MA-002',
        'name' => 'Ustadz Formatted NIP',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-guru.data-guru.create-account', $guru), [
            'password' => 'GuruBaru123',
            'password_confirmation' => 'GuruBaru123',
        ])
        ->assertCreated()
        ->assertJsonPath('data.username', 'gr_ma_002');

    expect(User::where('guru_id', $guru->id)->value('username'))->toBe('gr_ma_002');
});

test('admin can view guru detail page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-guru.data-guru.show', $this->guru))
        ->assertOk()
        ->assertSee($this->guru->name)
        ->assertSee($this->guru->nip);
});

test('manual teacher create provisions active kartu guru', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-guru.data-guru.store'), [
            'nip' => 'GR-MA-010',
            'name' => 'Guru Baru Manual',
            'jabatan' => 'Guru Mapel',
            'status' => 'aktif',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $guru = Guru::where('nip', 'GR-MA-010')->first();
    expect($guru)->not->toBeNull();

    $kartu = \App\Models\KartuGuru::where('guru_id', $guru->id)->first();
    expect($kartu)->not->toBeNull()
        ->and($kartu->status)->toBe('aktif');
});

test('guru show page displays kartu guru preview', function () {
    \App\Models\KartuGuru::provisionFor($this->guru);

    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-guru.data-guru.show', $this->guru))
        ->assertOk()
        ->assertSee('Kartu Guru')
        ->assertSee($this->guru->name)
        ->assertSee('Cetak Kartu');
});

test('create guru account returns json for ajax requests not redirect', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.manajemen-guru.data-guru.create-account', $this->guru), [
            'password' => 'GuruBaru123',
            'password_confirmation' => 'GuruBaru123',
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertCreated()->assertJsonPath('success', true);
});

test('create guru account validation failure returns json for ajax requests', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.manajemen-guru.data-guru.create-account', $this->guru), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

test('admin can reset guru password with custom password', function () {
    PortalUserProvisioner::forGuru($this->guru, 'PasswordLama1');

    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-guru.data-guru.account.reset-password', $this->guru), [
            'password' => 'PasswordBaru2',
            'password_confirmation' => 'PasswordBaru2',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $user = User::where('guru_id', $this->guru->id)->first();
    expect(Hash::check('PasswordBaru2', $user->password))->toBeTrue();
});

test('teacher datatable shows account status column', function () {
    PortalUserProvisioner::forGuru($this->guru, 'PasswordLama1');

    $guruWithoutAccount = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '198501012011',
        'name' => 'Guru Tanpa Akun',
        'status' => 'aktif',
    ]);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-guru.data-guru.data'));

    $response->assertOk();

    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('Punya akun')
        ->and($html)->toContain('Belum ada akun')
        ->and($html)->toContain('198501012010')
        ->and($html)->toContain($guruWithoutAccount->name);
});
