<?php

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

    $this->user = User::create([
        'username' => 'admin.test',
        'name' => 'Admin Test',
        'email' => 'admin@test.local',
        'phone' => '081234567890',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);

    $this->user->assignRole('admin');
});

test('authenticated user can view profile page', function () {
    $response = $this->actingAs($this->user)->get(route('profile.show'));

    $response->assertOk();
    $response->assertSee('Profil Akun');
    $response->assertSee('admin.test');
    $response->assertSee('Admin Test');
});

test('guest cannot view profile page', function () {
    $response = $this->get(route('profile.show'));

    $response->assertRedirect(route('login'));
});

test('user can update profile data', function () {
    $response = $this->actingAs($this->user)->putJson(route('profile.update'), [
        'name' => 'Admin Updated',
        'email' => 'updated@test.local',
        'phone' => '081111111111',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->user->refresh();
    expect($this->user->name)->toBe('Admin Updated');
    expect($this->user->email)->toBe('updated@test.local');
    expect($this->user->phone)->toBe('081111111111');
});

test('user can change password with correct current password', function () {
    $response = $this->actingAs($this->user)->putJson(route('profile.password'), [
        'current_password' => 'password',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $this->user->refresh();
    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});

test('user cannot change password with wrong current password', function () {
    $response = $this->actingAs($this->user)->putJson(route('profile.password'), [
        'current_password' => 'wrong-password',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['current_password']);
});
