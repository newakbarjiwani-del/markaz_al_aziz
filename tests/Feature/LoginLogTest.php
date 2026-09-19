<?php

use App\Models\LogLogin;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

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

    $this->superAdmin = User::create([
        'username' => 'superadmin.test',
        'name' => 'Super Admin Test',
        'email' => 'superadmin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);
    $this->superAdmin->assignRole('super_admin');
});

test('successful web login is recorded in log_login', function () {
    $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
    ])->post('/login', [
        'login' => 'admin.test',
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $log = LogLogin::query()->sole();

    expect($log->user_id)->toBe($this->admin->id)
        ->and($log->method)->toBe('credentials_web')
        ->and($log->status)->toBe('success')
        ->and($log->identifier)->toBe('admin.test')
        ->and($log->browser)->toBe('Chrome 125')
        ->and($log->platform)->toBe('Windows')
        ->and($log->device)->toBe('Desktop');
});

test('failed web login is recorded in log_login', function () {
    $this->post('/login', [
        'login' => 'admin.test',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('login');

    $log = LogLogin::query()->sole();

    expect($log->user_id)->toBeNull()
        ->and($log->method)->toBe('credentials_web')
        ->and($log->status)->toBe('failed')
        ->and($log->identifier)->toBe('admin.test');
});

test('super admin can view login logs but admin cannot', function () {
    LogLogin::create([
        'user_id' => $this->admin->id,
        'method' => 'credentials_web',
        'status' => 'success',
        'identifier' => 'admin.test',
    ]);

    $this->actingAs($this->superAdmin)
        ->get(route('super-admin.login-logs.index'))
        ->assertOk()
        ->assertSee('Riwayat Login');

    $this->actingAs($this->superAdmin)
        ->getJson(route('super-admin.login-logs.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);

    $this->actingAs($this->admin)
        ->get(route('super-admin.login-logs.index'))
        ->assertForbidden();
});

test('super admin can fetch login log detail', function () {
    $log = LogLogin::create([
        'user_id' => $this->admin->id,
        'method' => 'credentials_web',
        'status' => 'success',
        'identifier' => 'admin.test',
        'ip_address' => '127.0.0.1',
        'browser' => 'Chrome 125',
        'platform' => 'Windows',
        'device' => 'Desktop',
        'user_agent' => 'Mozilla/5.0 Test',
    ]);

    $this->actingAs($this->superAdmin)
        ->getJson(route('super-admin.login-logs.show', $log))
        ->assertOk()
        ->assertJsonPath('data.identifier', 'admin.test')
        ->assertJsonPath('data.browser', 'Chrome 125');

    $this->actingAs($this->admin)
        ->getJson(route('super-admin.login-logs.show', $log))
        ->assertForbidden();
});
