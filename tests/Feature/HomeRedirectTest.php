<?php

use App\Models\Sekolah;
use App\Models\User;
use App\Services\MenuService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'TST',
        'name' => 'Test School',
        'address' => 'Jl. Test',
    ]);

    $this->admin = User::create([
        'username' => 'admin.test',
        'name' => 'Admin Test',
        'email' => 'admin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);

    $this->admin->assignRole('admin');
});

test('guest visiting home is redirected to spmb landing', function () {
    $this->get('/')
        ->assertRedirect(route('spmb.home'));
});

test('authenticated admin visiting home is redirected to admin dashboard', function () {
    $this->actingAs($this->admin)
        ->get('/')
        ->assertRedirect(route('admin.dashboard'));
});

test('authenticated admin visiting login is redirected to admin dashboard', function () {
    $this->actingAs($this->admin)
        ->get('/login')
        ->assertRedirect(route('admin.dashboard'));
});

test('authenticated admin visiting admin root is redirected to admin dashboard', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertRedirect('/admin/dashboard');
});

test('bendahara login is redirected to keuangan dashboard', function () {
    $bendahara = User::create([
        'username' => 'bendahara.test',
        'name' => 'Bendahara Test',
        'email' => 'bendahara@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->admin->sekolah_id,
    ]);
    $bendahara->assignRole('bendahara');

    $this->post('/login', [
        'login' => 'bendahara.test',
        'password' => 'password',
    ])->assertRedirect(route('admin.keuangan.dashboard'));

    $this->assertAuthenticatedAs($bendahara);
});

test('authenticated bendahara visiting home is redirected to keuangan dashboard', function () {
    $bendahara = User::create([
        'username' => 'bendahara.home',
        'name' => 'Bendahara Home',
        'email' => 'bendahara-home@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->admin->sekolah_id,
    ]);
    $bendahara->assignRole('bendahara');

    $this->actingAs($bendahara)
        ->get('/')
        ->assertRedirect(route('admin.keuangan.dashboard'));
});

test('cashless login is redirected to dompet digital dashboard', function () {
    $cashless = User::create([
        'username' => 'cashless.test',
        'name' => 'Cashless Test',
        'email' => 'cashless@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->admin->sekolah_id,
    ]);
    $cashless->assignRole('cashless');

    $this->post('/login', [
        'login' => 'cashless.test',
        'password' => 'password',
    ])->assertRedirect(route('admin.dompet-digital.dashboard'));

    $this->assertAuthenticatedAs($cashless);
});

test('authenticated cashless visiting home is redirected to dompet digital dashboard', function () {
    $cashless = User::create([
        'username' => 'cashless.home',
        'name' => 'Cashless Home',
        'email' => 'cashless-home@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->admin->sekolah_id,
    ]);
    $cashless->assignRole('cashless');

    $this->actingAs($cashless)
        ->get('/')
        ->assertRedirect(route('admin.dompet-digital.dashboard'));
});

test('cashless role sidebar only includes cashless menu and can open dompet digital', function () {
    $cashless = User::create([
        'username' => 'cashless.menu',
        'name' => 'Cashless Menu',
        'email' => 'cashless-menu@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->admin->sekolah_id,
    ]);
    $cashless->assignRole('cashless');

    $menu = app(MenuService::class)->menuFor($cashless);
    $labels = collect($menu)->pluck('label')->all();

    expect($labels)->toContain('Cashless')
        ->and($labels)->not->toContain('Keuangan')
        ->and($labels)->not->toContain('Absensi')
        ->and($labels)->toContain('Profil Akun');

    $this->actingAs($cashless)
        ->get(route('admin.dompet-digital.dashboard'))
        ->assertOk();
});
