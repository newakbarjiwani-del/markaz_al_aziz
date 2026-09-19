<?php

use App\Models\Guru;
use App\Models\Sekolah;
use App\Models\User;
use App\Services\MenuService;
use App\Support\AdminModuleAccess;
use App\Support\HomeRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $this->superAdmin = User::create([
        'username' => 'superadmin',
        'name' => 'Super Admin',
        'email' => 'superadmin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('guru with students module can open admin student pages and sidebar', function () {
    $guru = Guru::create([
        'nip' => '2222222222',
        'name' => 'Guru Extra Access',
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'guru_students',
        'name' => 'Guru Students',
        'email' => 'guru_students@test.local',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['guru'],
        'guru_id' => $guru->id,
        'modules' => ['students'],
    ]);

    $response->assertCreated();

    $user = User::where('username', 'guru_students')->firstOrFail();
    expect($user->hasRole('guru'))->toBeTrue()
        ->and($user->can('students.view'))->toBeTrue()
        ->and(AdminModuleAccess::modulesFromDirectPermissions($user))->toBe(['students'])
        ->and(HomeRedirect::routeName($user))->toBe('admin.dashboard');

    $this->actingAs($user)
        ->get(route('admin.manajemen-siswa.data-siswa.index'))
        ->assertOk();

    expect($user->can('finance.view'))->toBeFalse();

    $this->actingAs($user)
        ->get(route('admin.keuangan.kirim-tagihan-wa.index'))
        ->assertForbidden();

    $menu = app(MenuService::class)->menuFor($user);
    $labels = collect($menu)->pluck('label')->all();

    expect($labels)->toContain('Manajemen Siswa')
        ->and($labels)->toContain('Beranda')
        ->and($labels)->not->toContain('Keuangan');
});

test('guru without modules cannot enter admin', function () {
    $guru = Guru::create([
        'nip' => '3333333333',
        'name' => 'Guru Portal Only',
        'sekolah_id' => $this->sekolah->id,
    ]);

    $user = User::create([
        'username' => 'guru_only',
        'name' => 'Guru Only',
        'email' => 'guru_only@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'guru_id' => $guru->id,
        'sekolah_id' => $this->sekolah->id,
    ]);
    $user->assignRole('guru');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('bendahara role path still opens keuangan without modules', function () {
    $user = User::create([
        'username' => 'bendahara_only',
        'name' => 'Bendahara Only',
        'email' => 'bendahara_only@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole('bendahara');

    $this->actingAs($user)
        ->get(route('admin.keuangan.dashboard'))
        ->assertOk();

    $menu = app(MenuService::class)->menuFor($user);
    $labels = collect($menu)->pluck('label')->all();

    expect($labels)->toContain('Keuangan')
        ->and($labels)->toContain('Absensi')
        ->and(AdminModuleAccess::modulesFromDirectPermissions($user))->toBe([]);
});

test('siswa role rejects modules when entity is valid', function () {
    $siswa = \App\Models\Siswa::create([
        'nis' => '1000001',
        'name' => 'Siswa Test',
        'sekolah_id' => $this->sekolah->id,
        'gender' => 'L',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'siswa_modules',
        'name' => 'Siswa Modules',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['siswa'],
        'siswa_id' => $siswa->id,
        'modules' => ['students'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['modules']);
});

test('unchecking modules removes directs but bendahara keeps finance via role', function () {
    $user = User::create([
        'username' => 'bendahara_extra',
        'name' => 'Bendahara Extra',
        'email' => 'bendahara_extra@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole('bendahara');
    $user->syncPermissions(AdminModuleAccess::permissionsForModules(['cashless', 'students']));

    expect(AdminModuleAccess::modulesFromDirectPermissions($user))->toEqualCanonicalizing(['cashless', 'students']);

    $response = $this->actingAs($this->superAdmin)->putJson(route('super-admin.users.update', $user), [
        'username' => $user->username,
        'name' => $user->name,
        'email' => $user->email,
        'status' => 'aktif',
        'role' => ['bendahara'],
        'modules' => [],
    ]);

    $response->assertOk();

    $user->refresh();
    expect(AdminModuleAccess::modulesFromDirectPermissions($user))->toBe([])
        ->and($user->can('finance.view'))->toBeTrue()
        ->and($user->can('cashless.view'))->toBeFalse()
        ->and($user->can('students.view'))->toBeFalse();
});

test('role-implied modules are not stored as direct permissions', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'cashless_implied',
        'name' => 'Cashless Implied',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['cashless'],
        'modules' => ['cashless', 'library'],
    ]);

    $response->assertCreated();

    $user = User::where('username', 'cashless_implied')->firstOrFail();
    expect(AdminModuleAccess::modulesFromDirectPermissions($user))->toBe(['library'])
        ->and($user->can('cashless.view'))->toBeTrue()
        ->and($user->can('library.view'))->toBeTrue();
});

test('datatable edit payload includes direct modules', function () {
    $user = User::create([
        'username' => 'modules_payload',
        'name' => 'Modules Payload',
        'email' => 'modules_payload@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole('guru');
    $guru = Guru::create([
        'nip' => '4444444444',
        'name' => 'Guru Payload',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $user->update(['guru_id' => $guru->id]);
    $user->syncPermissions(AdminModuleAccess::permissionsForModules(['teachers']));

    $response = $this->actingAs($this->superAdmin)->getJson(route('super-admin.users.data', [
        'draw' => 1,
        'start' => 0,
        'length' => 50,
        'search' => ['value' => 'modules_payload'],
    ]));

    $response->assertOk();
    $row = collect($response->json('data'))->first(fn ($r) => ($r[0] ?? null) === 'modules_payload');
    expect($row)->not->toBeNull();

    $actions = $row[6]['actions'] ?? $row[6] ?? null;
    // DataTables cell may be structured; fall back to scanning JSON.
    $json = $response->getContent();
    expect($json)->toContain('"modules":["teachers"]');
});
