<?php

use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\PortalAccessToken;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PortalAccessTokenService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

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
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->orangTua = OrangTua::create([
        'sekolah_id' => $sekolah->id,
        'nama_ayah' => 'Bapak Portal',
        'telepon_ayah' => '081234567890',
        'nama_ibu' => 'Ibu Portal',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    $this->orangTua->siswa()->attach($this->siswa->id);

    $this->admin = User::create([
        'username' => 'admin.portal',
        'name' => 'Admin Portal',
        'email' => 'admin.portal@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);
    $this->admin->assignRole('admin');
});

test('admin can generate ortu whatsapp link from student management', function () {
    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.portal-access.ortu', $this->siswa));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['access_url', 'whatsapp_url', 'expires_at']]);

    expect($response->json('data.whatsapp_url'))->toContain('wa.me/6281234567890');
    expect(PortalAccessToken::count())->toBe(1);
});

test('admin can copy siswa portal login link without whatsapp phone', function () {
    app(PortalAccessTokenService::class)->issueForSiswa($this->siswa, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.portal-access.siswa.link.copy', $this->siswa));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', fn ($message) => str_contains($message, 'disalin'))
        ->assertJsonStructure(['data' => ['access_url', 'expires_at']]);

    expect($response->json('data.access_url'))->toContain('/access?token=');
    expect(PortalAccessToken::query()->whereNull('revoked_at')->count())->toBe(1);
});

test('admin can copy ortu portal login link from student row', function () {
    app(PortalAccessTokenService::class)->issueForOrangTua($this->orangTua, $this->siswa, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.portal-access.ortu.link.copy', $this->siswa));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', fn ($message) => str_contains($message, 'disalin'))
        ->assertJsonStructure(['data' => ['access_url', 'expires_at']]);
});

test('admin issue siswa link creates token with create message', function () {
    $response = $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.portal-access.siswa.link', $this->siswa));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', fn ($message) => str_contains($message, 'dibuat'));
});

test('admin can view student and parent detail pages', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.data-siswa.show', $this->siswa))
        ->assertOk()
        ->assertSee($this->siswa->name)
        ->assertSee('Edit')
        ->assertSee('Rekam Wajah')
        ->assertSee('Hapus')
        ->assertSee('student-form')
        ->assertSee('data-open-face-capture');

    $this->actingAs($this->admin)
        ->get(route('admin.manajemen-siswa.orang-tua.show', $this->orangTua))
        ->assertOk()
        ->assertSee($this->orangTua->nama_ayah);
});

test('admin can revoke only siswa portal token when role is specified', function () {
    $service = app(PortalAccessTokenService::class);
    $service->issueForOrangTua($this->orangTua, $this->siswa, $this->admin->id);
    $service->issueForSiswa($this->siswa, $this->admin->id);

    expect(PortalAccessToken::query()->whereNull('revoked_at')->count())->toBe(2);

    $this->actingAs($this->admin)
        ->deleteJson(
            route('admin.manajemen-siswa.data-siswa.portal-access.revoke', $this->siswa),
            ['role' => 'siswa']
        )
        ->assertOk();

    expect(PortalAccessToken::query()->where('role', 'siswa')->whereNull('revoked_at')->count())->toBe(0);
    expect(PortalAccessToken::query()->where('role', 'orang_tua')->whereNull('revoked_at')->count())->toBe(1);
});

test('admin can revoke portal tokens for student household', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.manajemen-siswa.data-siswa.portal-access.siswa', $this->siswa))
        ->assertOk();

    $user = User::query()->where('siswa_id', $this->siswa->id)->first();
    $user->createToken('mobile', ['siswa']);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.manajemen-siswa.data-siswa.portal-access.revoke', $this->siswa))
        ->assertOk();

    expect(PortalAccessToken::query()->whereNull('revoked_at')->count())->toBe(0);
    expect($user->fresh()->tokens)->toBeEmpty();
});

test('portal access link logs user into ortu dashboard', function () {
    $issue = app(PortalAccessTokenService::class)
        ->issueForOrangTua($this->orangTua, $this->siswa, $this->admin->id);

    $token = parse_url($issue['access_url'], PHP_URL_QUERY);
    parse_str((string) $token, $query);

    $response = $this->get(route('access.show', [
        'token' => $query['token'],
        'role' => $query['role'],
    ]));

    $response->assertRedirect(route('portal.ortu.dashboard'));
    $this->assertAuthenticated();
});

test('portal magic-link login extends session lifetime and sets remember cookie', function () {
    config([
        'portal.access_token_ttl_days' => 365,
        'portal.session_lifetime_minutes' => 365 * 24 * 60,
        'session.lifetime' => 120,
    ]);

    $issue = app(PortalAccessTokenService::class)
        ->issueForSiswa($this->siswa, $this->admin->id);

    $token = parse_url($issue['access_url'], PHP_URL_QUERY);
    parse_str((string) $token, $query);

    $response = $this->get(route('access.show', [
        'token' => $query['token'],
        'role' => $query['role'],
    ]));

    $response->assertRedirect(route('portal.siswa.dashboard'));
    $this->assertAuthenticated();
    expect((int) config('session.lifetime'))->toBe(365 * 24 * 60);
    expect(session('auth.portal_access_token_id'))->toBeInt();

    $recaller = collect($response->headers->getCookies())
        ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));
    expect($recaller)->not->toBeNull();
});

test('issued portal access token expires according to PORTAL_ACCESS_TOKEN_TTL_DAYS', function () {
    config(['portal.access_token_ttl_days' => 365]);

    $before = now();
    app(PortalAccessTokenService::class)->issueForSiswa($this->siswa, $this->admin->id);
    $token = PortalAccessToken::query()->latest('id')->first();

    expect($token)->not->toBeNull();
    expect($token->expires_at->between(
        $before->copy()->addDays(365)->subMinute(),
        $before->copy()->addDays(365)->addMinute(),
    ))->toBeTrue();
});

test('revoked portal access link is rejected', function () {
    $issue = app(PortalAccessTokenService::class)
        ->issueForSiswa($this->siswa, $this->admin->id);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.manajemen-siswa.data-siswa.portal-access.revoke', $this->siswa))
        ->assertOk();

    $token = parse_url($issue['access_url'], PHP_URL_QUERY);
    parse_str((string) $token, $query);

    $this->get(route('access.show', [
        'token' => $query['token'],
        'role' => $query['role'],
    ]))->assertOk()->assertSee('Link Tidak Valid');
});

test('student datatable shows generate token buttons when portal token inactive', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.data'));

    $response->assertOk();

    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('data-portal-generate')
        ->and($html)->toContain('Buat token login')
        ->and($html)->not->toContain('data-portal-copy');
});

test('student datatable shows send and copy after portal token is active', function () {
    app(PortalAccessTokenService::class)->issueForSiswa($this->siswa, $this->admin->id);
    app(PortalAccessTokenService::class)->issueForOrangTua($this->orangTua, $this->siswa, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.data'));

    $response->assertOk();

    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('data-portal-copy')
        ->and($html)->toContain('data-portal-wa')
        ->and($html)->not->toContain('data-portal-generate');
});

test('student datatable shows portal token login status', function () {
    Siswa::create([
        'sekolah_id' => $this->siswa->sekolah_id,
        'kelas_id' => $this->siswa->kelas_id,
        'nis' => '1000002',
        'name' => 'Siswa Tanpa Token',
        'gender' => 'P',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    app(PortalAccessTokenService::class)->issueForSiswa($this->siswa, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.data-siswa.data'));

    $response->assertOk();

    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('Aktif')
        ->and($html)->toContain('Tidak aktif');
});

test('parent datatable shows generate token when portal token inactive', function () {
    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data'));

    $response->assertOk();

    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('data-portal-generate')
        ->and($html)->not->toContain('data-portal-copy');
});

test('parent datatable shows portal token login status', function () {
    OrangTua::create([
        'sekolah_id' => $this->orangTua->sekolah_id,
        'nama_ayah' => 'Bapak Tanpa Token',
        'telepon_ayah' => '081111111111',
        'nama_ibu' => 'Ibu Tanpa Token',
        'status' => 'aktif',
    ]);

    app(PortalAccessTokenService::class)->issueForOrangTua($this->orangTua, $this->siswa, $this->admin->id);

    $response = $this->actingAs($this->admin)
        ->getJson(route('admin.manajemen-siswa.orang-tua.data'));

    $response->assertOk();

    $html = collect($response->json('data'))->flatten()->implode(' ');
    expect($html)->toContain('Aktif')
        ->and($html)->toContain('Tidak aktif');
});
