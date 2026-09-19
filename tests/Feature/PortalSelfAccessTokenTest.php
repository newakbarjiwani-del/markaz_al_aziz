<?php

use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\PortalAccessToken;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PortalAccessTokenService;
use App\Services\PortalUserProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $kelas = Kelas::create([
        'sekolah_id' => $sekolah->id,
        'name' => 'X IPA 1',
        'unit' => 'MA',
        'jenjang' => 'X',
        'is_active' => true,
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '1000001',
        'name' => 'Siswa Portal',
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $sekolah->id,
        'nama_ayah' => 'Bapak Portal',
        'telepon_ayah' => '081234567890',
        'nama_ibu' => 'Ibu Portal',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);
    $this->orangTua->siswa()->attach($this->siswa->id);

    $this->siswaUser = PortalUserProvisioner::forSiswa($this->siswa);
    $this->ortuUser = PortalUserProvisioner::forOrangTua($this->orangTua);
});

test('siswa portal can view and refresh own access token', function () {
    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.akses-token.index'))
        ->assertOk()
        ->assertSee('Link Login Portal');

    $response = $this->actingAs($this->siswaUser)
        ->postJson(route('portal.siswa.akses-token.refresh'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['access_url', 'expires_at']]);

    expect(PortalAccessToken::query()
        ->where('user_id', $this->siswaUser->id)
        ->where('role', 'siswa')
        ->whereNull('revoked_at')
        ->count())->toBe(1);

    $this->actingAs($this->siswaUser)
        ->get(route('portal.siswa.akses-token.index'))
        ->assertOk()
        ->assertSee('/access?token=');
});

test('ortu portal can view and refresh own access token', function () {
    $this->actingAs($this->ortuUser)
        ->get(route('portal.ortu.akses-token.index'))
        ->assertOk()
        ->assertSee('Link Login Portal');

    $this->actingAs($this->ortuUser)
        ->postJson(route('portal.ortu.akses-token.refresh'))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(PortalAccessToken::query()
        ->where('user_id', $this->ortuUser->id)
        ->where('role', 'orang_tua')
        ->whereNull('revoked_at')
        ->count())->toBe(1);
});

test('refreshing portal token revokes previous active token', function () {
    $service = app(PortalAccessTokenService::class);
    $service->refreshForUser($this->siswaUser, 'siswa', $this->siswaUser->id);
    $firstId = PortalAccessToken::query()->where('user_id', $this->siswaUser->id)->latest('id')->value('id');

    $this->actingAs($this->siswaUser)
        ->postJson(route('portal.siswa.akses-token.refresh'))
        ->assertOk();

    expect(PortalAccessToken::find($firstId)?->revoked_at)->not->toBeNull();
    expect(PortalAccessToken::query()
        ->where('user_id', $this->siswaUser->id)
        ->whereNull('revoked_at')
        ->count())->toBe(1);
});

test('other roles cannot access siswa token page', function () {
    $admin = User::create([
        'username' => 'admin.token',
        'name' => 'Admin Token',
        'email' => 'admin.token@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('portal.siswa.akses-token.index'))
        ->assertForbidden();
});
