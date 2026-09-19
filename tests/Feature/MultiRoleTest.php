<?php

use App\Models\Sekolah;
use App\Models\User;
use App\Services\MenuService;
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

test('super admin can create user with multiple roles', function () {
    $guru = \App\Models\Guru::create([
        'nip' => '1111111111',
        'name' => 'Guru Multi Test',
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'multi_user',
        'name' => 'Multi Role User',
        'email' => 'multi@test.local',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['admin', 'guru'],
        'guru_id' => $guru->id,
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $user = User::where('username', 'multi_user')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('guru'))->toBeTrue()
        ->and($user->roles()->count())->toBe(2)
        ->and($user->guru_id)->toBe($guru->id);
});

test('single role super_admin cannot be combined with other roles', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'bad_combo',
        'name' => 'Bad Combo',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['super_admin', 'admin'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('single role orang_tua cannot be combined with other roles', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'bad_ortu',
        'name' => 'Bad Ortu',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['orang_tua', 'guru'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('single role siswa cannot be combined with other roles', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'bad_siswa',
        'name' => 'Bad Siswa',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['siswa', 'kantin'],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('empty role array is rejected', function () {
    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'no_role',
        'name' => 'No Role',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('multi-role user can update roles', function () {
    $guru = \App\Models\Guru::create([
        'nip' => '2222222222',
        'name' => 'Guru Update Test',
        'sekolah_id' => $this->sekolah->id,
    ]);

    $user = User::create([
        'username' => 'updatable',
        'name' => 'Updatable User',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['admin', 'kantin']);

    $response = $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $user), [
            'username' => 'updatable',
            'name' => 'Updatable User Updated',
            'status' => 'aktif',
            'role' => ['admin', 'guru', 'kantin'],
            'guru_id' => $guru->id,
            'sekolah_id' => $this->sekolah->id,
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $user->refresh();
    expect($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('guru'))->toBeTrue()
        ->and($user->hasRole('kantin'))->toBeTrue()
        ->and($user->roles()->count())->toBe(3);
});

test('self-edit locks roles array', function () {
    $response = $this->actingAs($this->superAdmin)
        ->putJson(route('super-admin.users.update', $this->superAdmin), [
            'username' => 'superadmin',
            'name' => 'Super Admin Updated',
            'email' => 'superadmin@test.local',
            'status' => 'aktif',
            'role' => ['admin'],
            'current_password' => 'password',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($this->superAdmin->fresh()->hasRole('super_admin'))->toBeTrue()
        ->and($this->superAdmin->fresh()->hasRole('admin'))->toBeFalse();
});

test('data endpoint returns comma-separated roles', function () {
    $user = User::create([
        'username' => 'multi_data',
        'name' => 'Multi Data User',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['admin', 'guru']);

    $response = $this->actingAs($this->superAdmin)
        ->getJson(route('super-admin.users.data'));

    $response->assertOk();

    $data = $response->json('data');
    $found = collect($data)->firstWhere('0', 'multi_data');
    expect($found)->not->toBeNull()
        ->and($found[3])->toContain('admin')
        ->and($found[3])->toContain('guru');
});

test('extractUserPayload sets guru_id when guru role present', function () {
    $guru = \App\Models\Guru::create([
        'nip' => '1234567890',
        'name' => 'Guru Test',
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'guru_multi',
        'name' => 'Guru Multi',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['guru', 'kantin'],
        'guru_id' => $guru->id,
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response->assertCreated();

    $user = User::where('username', 'guru_multi')->first();
    expect($user->guru_id)->toBe($guru->id)
        ->and($user->hasRole('guru'))->toBeTrue()
        ->and($user->hasRole('kantin'))->toBeTrue();
});

test('menuService merges admin and portal menus with dividers', function () {
    $user = User::create([
        'username' => 'menu_test',
        'name' => 'Menu Test',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['admin', 'guru']);

    $menuService = new MenuService();
    $menu = $menuService->menuFor($user);

    $hasAdminMenu = collect($menu)->contains(fn ($item) => ($item['label'] ?? '') === 'Manajemen Siswa');
    $hasGuruDivider = collect($menu)->contains(fn ($item) => ($item['label'] ?? '') === 'Portal: Guru' && ($item['type'] ?? '') === 'divider');
    $hasGuruMenu = collect($menu)->contains(fn ($item) => ($item['label'] ?? '') === 'Beranda' && ($item['route'] ?? '') === 'portal.guru.dashboard');
    $hasProfileLink = collect($menu)->contains(fn ($item) => ($item['route'] ?? '') === 'profile.show');

    expect($hasAdminMenu)->toBeTrue('Admin menus present')
        ->and($hasGuruDivider)->toBeTrue('Guru divider present')
        ->and($hasGuruMenu)->toBeTrue('Guru portal menu present')
        ->and($hasProfileLink)->toBeTrue('Profile link present');
});

test('menuService orders portal sections by roles.id', function () {
    $user = User::create([
        'username' => 'order_test',
        'name' => 'Order Test',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['kantin', 'guru', 'perpustakaan']);

    $menuService = new MenuService();
    $menu = $menuService->menuFor($user);

    $dividers = collect($menu)->filter(fn ($item) => ($item['type'] ?? '') === 'divider')->values();
    expect($dividers->count())->toBe(3)
        ->and($dividers[0]['label'])->toBe('Portal: Guru')
        ->and($dividers[1]['label'])->toBe('Portal: Kantin')
        ->and($dividers[2]['label'])->toBe('Portal: Perpustakaan');
});

test('bottomNavFor returns admin nav when user has admin role', function () {
    $user = User::create([
        'username' => 'nav_test',
        'name' => 'Nav Test',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['admin', 'guru']);

    $menuService = new MenuService();
    $nav = $menuService->bottomNavFor($user);

    expect($nav)->not->toBeEmpty()
        ->and(collect($nav)->contains(fn ($item) => ($item['label'] ?? '') === 'Beranda' && ($item['route'] ?? '') === 'admin.dashboard'))->toBeTrue();
});

test('bottomNavFor returns first portal role by roles.id when no admin', function () {
    $user = User::create([
        'username' => 'portal_nav',
        'name' => 'Portal Nav',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['kantin', 'guru']);

    $menuService = new MenuService();
    $nav = $menuService->bottomNavFor($user);

    expect($nav)->not->toBeEmpty()
        ->and(collect($nav)->contains(fn ($item) => ($item['route'] ?? '') === 'portal.guru.dashboard'))->toBeTrue();
});

test('homeRouteFor prioritises admin over any portal role', function () {
    $user = User::create([
        'username' => 'home_admin',
        'name' => 'Home Admin',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['admin', 'guru']);

    $menuService = new MenuService();
    expect($menuService->homeRouteFor($user))->toBe('admin.dashboard');
});

test('homeRouteFor falls back to lowest roles.id when no admin', function () {
    $user = User::create([
        'username' => 'home_ortu',
        'name' => 'Home Ortu',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    // guru (id 3) < bendahara (id 9) → guru wins.
    $user->assignRole(['bendahara', 'guru']);

    $menuService = new MenuService();
    expect($menuService->homeRouteFor($user))->toBe('portal.guru.dashboard');
});

test('HomeRedirect::for returns a resolvable url for multi-role user', function () {
    $user = User::create([
        'username' => 'home_redirect',
        'name' => 'Home Redirect',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $user->assignRole(['kantin', 'cashless']);

    // kantin (id 6) < cashless (id 10) → kantin dashboard.
    $url = \App\Support\HomeRedirect::for($user);

    expect($url)->toContain(route('portal.kantin.dashboard'));
});

test('canEditUser allows admin to edit multi-role user containing siswa', function () {
    $admin = User::create([
        'username' => 'admin_edit',
        'name' => 'Admin Edit',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $admin->assignRole('admin');

    $target = User::create([
        'username' => 'target_multi',
        'name' => 'Target Multi',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $siswa = \App\Models\Siswa::create([
        'nis' => '999999',
        'name' => 'Siswa Test',
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => \App\Models\Kelas::create(['name' => 'X', 'sekolah_id' => $this->sekolah->id, 'tingkat' => 10])->id,
        'angkatan' => 2025,
    ]);
    $target->update(['siswa_id' => $siswa->id]);
    $target->assignRole(['siswa', 'kantin']);

    $this->actingAs($admin)
        ->putJson(route('admin.manajemen-user.update', $target), [
            'username' => 'target_multi',
            'name' => 'Target Updated',
            'status' => 'aktif',
            'role' => ['siswa'],
            'siswa_id' => $siswa->id,
        ])
        ->assertOk();
});

test('canResetPassword allows admin to reset multi-role user containing guru', function () {
    $admin = User::create([
        'username' => 'admin_reset',
        'name' => 'Admin Reset',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $admin->assignRole('admin');

    $target = User::create([
        'username' => 'target_guru_multi',
        'name' => 'Target Guru Multi',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $target->assignRole(['guru', 'admin']);

    $this->actingAs($admin)
        ->postJson(route('admin.manajemen-user.reset-password', $target))
        ->assertOk();

    expect(Hash::check('target_guru_multi', $target->fresh()->password))->toBeTrue();
});

test('admin cannot edit multi-role user without portal role', function () {
    $admin = User::create([
        'username' => 'admin_cant',
        'name' => 'Admin Cant',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $admin->assignRole('admin');

    $target = User::create([
        'username' => 'kantin_only',
        'name' => 'Kantin Only',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $target->assignRole('kantin');

    $this->actingAs($admin)
        ->putJson(route('admin.manajemen-user.update', $target), [
            'username' => 'kantin_only',
            'name' => 'Kantin Only Updated',
            'status' => 'aktif',
            'role' => ['kantin'],
        ])
        ->assertStatus(422);
});

test('prestasi_pelanggaran role can combine with guru', function () {
    $guru = \App\Models\Guru::create([
        'nip' => '2222222222',
        'name' => 'Guru Prestasi',
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response = $this->actingAs($this->superAdmin)->postJson(route('super-admin.users.store'), [
        'username' => 'prestasi_guru',
        'name' => 'Prestasi Guru',
        'email' => 'prestasi-guru@test.local',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'status' => 'aktif',
        'role' => ['prestasi_pelanggaran', 'guru'],
        'guru_id' => $guru->id,
        'sekolah_id' => $this->sekolah->id,
    ]);

    $response->assertCreated()->assertJsonPath('success', true);

    $user = User::where('username', 'prestasi_guru')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('prestasi_pelanggaran'))->toBeTrue()
        ->and($user->hasRole('guru'))->toBeTrue();
});
