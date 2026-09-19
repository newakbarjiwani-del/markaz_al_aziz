<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admin user seeder creates admin and kantin accounts', function () {
    $this->seed(AdminUserSeeder::class);

    foreach (['superadmin' => 'super_admin', 'admin' => 'admin', 'admin2' => 'admin', 'kantin' => 'kantin', 'kantin2' => 'kantin'] as $username => $role) {
        $user = User::query()->where('username', $username)->first();
        expect($user)->not->toBeNull();
        expect($user->hasRole($role))->toBeTrue();
        expect($user->canLogin())->toBeTrue();
    }

    $this->assertTrue(auth()->attempt([
        'username' => 'admin',
        'password' => 'password',
    ]));
    auth()->logout();

    $this->assertTrue(auth()->attempt([
        'username' => 'kantin',
        'password' => 'password',
    ]));
});
