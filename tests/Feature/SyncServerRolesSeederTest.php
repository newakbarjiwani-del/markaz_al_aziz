<?php

use Database\Seeders\SyncServerRolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('sync server roles seeder creates roles with stable live ids', function () {
    $this->seed(SyncServerRolesSeeder::class);

    expect(Role::findByName('super_admin')->id)->toBe(1)
        ->and(Role::findByName('admin')->id)->toBe(2)
        ->and(Role::findByName('guru')->id)->toBe(3)
        ->and(Role::findByName('orang_tua')->id)->toBe(4)
        ->and(Role::findByName('siswa')->id)->toBe(5)
        ->and(Role::findByName('kantin')->id)->toBe(6)
        ->and(Role::findByName('pimpinan')->id)->toBe(7)
        ->and(Role::findByName('perpustakaan')->id)->toBe(8)
        ->and(Role::findByName('bendahara')->id)->toBe(9)
        ->and(Role::findByName('cashless')->id)->toBe(10)
        ->and(Role::findByName('perizinan')->id)->toBe(11)
        ->and(Role::findByName('prestasi_pelanggaran')->id)->toBe(12);

    // Idempotent — existing ids are never rewritten / shifted.
    $this->seed(SyncServerRolesSeeder::class);
    expect(Role::findByName('pimpinan')->id)->toBe(7)
        ->and(Role::findByName('bendahara')->id)->toBe(9)
        ->and(Role::findByName('cashless')->id)->toBe(10)
        ->and(Role::findByName('perizinan')->id)->toBe(11);
});

test('sync server roles seeder does not shift existing role ids when re-run', function () {
    $this->seed(SyncServerRolesSeeder::class);

    $before = Role::query()->orderBy('id')->pluck('id', 'name')->all();

    $this->seed(SyncServerRolesSeeder::class);

    expect(Role::query()->orderBy('id')->pluck('id', 'name')->all())->toBe($before);
});
