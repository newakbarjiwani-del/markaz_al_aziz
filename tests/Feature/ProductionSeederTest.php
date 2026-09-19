<?php

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\KelasJsonCatalog;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'seed.mode' => 'production',
        'seed.production.superadmin' => [
            'username' => 'prod.superadmin',
            'name' => 'Production Super Admin',
            'email' => 'prod-superadmin@test.local',
            'phone' => '628110000101',
            'password' => 'ProdSuperSecret1!',
        ],
        'seed.production.admin' => [
            'enabled' => true,
            'username' => 'prod.admin',
            'name' => 'Production Admin',
            'email' => 'prod-admin@test.local',
            'phone' => '628110000102',
            'password' => 'ProdAdminSecret1!',
            'sekolah_code' => 'ma',
        ],
    ]);
});

test('production seeder creates schools, classes, and admin users', function () {
    $this->seed(ProductionSeeder::class);

    $expectedClassCount = KelasJsonCatalog::totalSeededClassCount();

    expect(Sekolah::count())->toBe(4)
        ->and(Kelas::count())->toBe($expectedClassCount)
        ->and(User::count())->toBe(2)
        ->and(User::where('username', 'prod.superadmin')->first())
        ->not->toBeNull()
        ->and(User::where('username', 'prod.superadmin')->first()->hasRole('super_admin'))->toBeTrue()
        ->and(User::where('username', 'prod.admin')->first()?->sekolah?->code)->toBe('ma');
});

test('production seeder is idempotent for schools and users', function () {
    $this->seed(ProductionSeeder::class);

    $schoolCount = Sekolah::count();
    $classCount = Kelas::count();
    $userCount = User::count();

    $this->seed(ProductionSeeder::class);

    expect(Sekolah::count())->toBe($schoolCount)
        ->and(Kelas::count())->toBe($classCount)
        ->and(User::count())->toBe($userCount);
});

test('production user seeder requires super admin password', function () {
    config(['seed.production.superadmin.password' => '']);

    expect(fn () => $this->seed(ProductionSeeder::class))
        ->toThrow(InvalidArgumentException::class, 'SEED_SUPERADMIN_PASSWORD');
});

test('database seeder uses production mode from config', function () {
    config(['seed.mode' => 'production']);

    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(Sekolah::count())->toBe(4)
        ->and(User::count())->toBe(2)
        ->and(Siswa::count())->toBe(0);
});
