<?php

use App\Models\Guru;
use App\Models\OrangTua;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $kelas = \App\Models\Kelas::create([
        'sekolah_id' => $sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'jenjang' => 'X',
        'is_active' => true,
    ]);

    $siswa = Siswa::create([
        'sekolah_id' => $sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '512336041084210013',
        'name' => 'Siswa API Test',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $orangTua = OrangTua::create([
        'sekolah_id' => $sekolah->id,
        'nama_ayah' => 'Bapak Test',
        'nama_ibu' => 'Ibu Test',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);
    $orangTua->siswa()->attach($siswa->id);

    $this->siswaUser = User::create([
        'username' => 'siswa.api',
        'name' => $siswa->name,
        'email' => 'siswa.api@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
        'siswa_id' => $siswa->id,
    ]);
    $this->siswaUser->assignRole('siswa');

    $this->ortuUser = User::create([
        'username' => 'ortu.api',
        'name' => $orangTua->displayName(),
        'email' => 'ortu.api@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
        'orang_tua_id' => $orangTua->id,
    ]);
    $this->ortuUser->assignRole('orang_tua');

    $this->guru = Guru::create([
        'sekolah_id' => $sekolah->id,
        'nip' => '19800101001',
        'name' => 'Guru API Test',
        'status' => 'aktif',
    ]);

    $this->guruUser = User::create([
        'username' => 'guru.api',
        'name' => $this->guru->name,
        'email' => 'guru.api@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
        'guru_id' => $this->guru->id,
    ]);
    $this->guruUser->assignRole('guru');
});

test('orang tua can login with username and receive sanctum token', function () {
    $response = $this->postJson('/api/auth/ortu/login', [
        'login' => 'ortu.api',
        'password' => 'password',
        'device_name' => 'ortu-mobile',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.role', 'orang_tua')
        ->assertJsonPath('data.profile.orang_tua_id', $this->ortuUser->orang_tua_id)
        ->assertJsonCount(1, 'data.profile.children');

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
});

test('siswa can login with email and receive profile payload', function () {
    $response = $this->postJson('/api/auth/siswa/login', [
        'email' => 'siswa.api@test.local',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.role', 'siswa')
        ->assertJsonPath('data.profile.nis', '512336041084210013')
        ->assertJsonPath('data.profile.kelas.name', 'X IPA 1');
});

test('role specific login rejects wrong portal role', function () {
    $this->postJson('/api/auth/siswa/login', [
        'login' => 'ortu.api',
        'password' => 'password',
    ])->assertForbidden();

    $this->postJson('/api/auth/ortu/login', [
        'login' => 'siswa.api',
        'password' => 'password',
    ])->assertForbidden();
});

test('authenticated ortu can access dashboard api', function () {
    Sanctum::actingAs($this->ortuUser, ['orang_tua']);

    $this->getJson('/api/ortu/dashboard')
        ->assertOk()
        ->assertJsonPath('data.stats.jumlah_anak', 1);

    $this->getJson('/api/ortu/children')
        ->assertOk()
        ->assertJsonCount(1, 'data.children');
});

test('authenticated siswa can access profil api', function () {
    Sanctum::actingAs($this->siswaUser, ['siswa']);

    $this->getJson('/api/siswa/profil')
        ->assertOk()
        ->assertJsonPath('data.profile.name', 'Siswa API Test');
});

test('siswa token cannot access ortu routes', function () {
    Sanctum::actingAs($this->siswaUser, ['siswa']);

    $this->getJson('/api/ortu/dashboard')->assertForbidden();
});

test('auth me returns current portal profile', function () {
    Sanctum::actingAs($this->ortuUser, ['orang_tua']);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.user.username', 'ortu.api')
        ->assertJsonPath('data.profile.display_name', 'Bapak Test · Ibu Test');
});

test('logout revokes current access token', function () {
    $token = $this->ortuUser->createToken('test-device', ['orang_tua'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/auth/logout')
        ->assertOk();

    expect(\Laravel\Sanctum\PersonalAccessToken::count())->toBe(0);

    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/auth/me')
        ->assertUnauthorized();
});

test('disabled portal user cannot login via api', function () {
    $this->ortuUser->update(['status' => \App\Support\UserStatus::DISABLED]);

    $this->postJson('/api/auth/ortu/login', [
        'login' => 'ortu.api',
        'password' => 'password',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Akun tidak aktif.');
});
