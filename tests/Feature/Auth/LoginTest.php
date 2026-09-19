<?php

use App\Models\User;
use App\Support\UserStatus;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('guest can view login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('name="login"', false);
});

test('user can login with username', function () {
    $user = User::factory()->create([
        'username' => 'bendahara1',
        'email' => 'bendahara1@example.com',
        'status' => UserStatus::ACTIVE,
    ]);
    $user->assignRole('admin');

    $this->post(route('login'), [
        'login' => 'bendahara1',
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('user can login with email', function () {
    $user = User::factory()->create([
        'username' => 'adminemail',
        'email' => 'admin@example.com',
        'status' => UserStatus::ACTIVE,
    ]);
    $user->assignRole('admin');

    $this->post(route('login'), [
        'login' => 'admin@example.com',
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('invalid login fails', function () {
    $this->post(route('login'), [
        'login' => 'nobody',
        'password' => 'wrong',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('disabled user cannot login', function () {
    $user = User::factory()->disabled()->create([
        'username' => 'blocked',
    ]);
    $user->assignRole('admin');

    $this->post(route('login'), [
        'login' => 'blocked',
        'password' => 'password',
    ])->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('non admin cannot access admin dashboard', function () {
    $user = User::factory()->create(['username' => 'siswa1']);
    $user->assignRole('siswa');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('ui kit view exists for local-only komponen ui route', function () {
    expect(view()->exists('admin.komponen-ui.index'))->toBeTrue();
});

test('attendance terminal layout renders', function () {
    $user = User::factory()->create(['username' => 'kioskadmin']);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect(view()->exists('layouts.attendance-terminal'))->toBeTrue();
});
