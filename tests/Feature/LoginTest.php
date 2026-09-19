<?php

use App\Models\Sekolah;
use App\Models\User;
use App\Support\LoginIdentifier;
use App\Support\UserStatus;
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

    $this->user = User::create([
        'username' => 'admin.test',
        'name' => 'Admin Test',
        'email' => 'admin@test.local',
        'password' => Hash::make('password'),
        'status' => UserStatus::ACTIVE,
        'sekolah_id' => $sekolah->id,
    ]);

    $this->user->assignRole('admin');
});

test('user can login with username', function () {
    $response = $this->post('/login', [
        'login' => 'admin.test',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($this->user);
});

test('user can login with email', function () {
    $response = $this->post('/login', [
        'login' => 'admin@test.local',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($this->user);
});

test('login fails with invalid credentials', function () {
    $response = $this->post('/login', [
        'login' => 'admin.test',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('login');
    $this->assertGuest();
});

test('disabled user cannot login', function () {
    $this->user->update(['status' => UserStatus::DISABLED]);

    $response = $this->post('/login', [
        'login' => 'admin.test',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('login');
    $this->assertGuest();
});

test('web login is throttled after too many attempts', function () {
    $login = 'throttle.victim';

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'login' => $login,
            'password' => 'wrong-password',
        ]);
    }

    $this->post('/login', [
        'login' => $login,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

test('login identifier resolves email and username fields', function () {
    expect(LoginIdentifier::field('admin@test.local'))->toBe('email');
    expect(LoginIdentifier::field('admin.test'))->toBe('username');
});

test('renders a blurred logo behind the login brand panel', function () {
    $this->get('/login')
        ->assertSee('login-page__brand-bg-logo', false)
        ->assertSee('logo.png', false);
});
