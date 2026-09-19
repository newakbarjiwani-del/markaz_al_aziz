<?php

use App\Models\User;
use App\Support\PortalGreeting;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('admin dashboard shows quick access launcher', function () {
    $user = User::factory()->create(['username' => 'dashadmin']);
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Modul Akademik', false)
        ->assertSee('dashboard-launcher', false)
        ->assertSee('Manajemen Siswa', false)
        ->assertSee('Absensi', false)
        ->assertSee('Dompet Digital', false)
        ->assertSee('Keuangan', false);
});

test('portal greeting returns time-based salutation', function () {
    $this->travelTo(now()->setTime(9, 0));

    expect(PortalGreeting::timeOfDay())->toBe('pagi')
        ->and(PortalGreeting::salutation())->toBe('Selamat pagi')
        ->and(PortalGreeting::icon())->toBe('sun');
});
