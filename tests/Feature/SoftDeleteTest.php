<?php

use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'TST',
        'name' => 'Test School',
        'is_active' => true,
    ]);

    $this->admin = User::factory()->create([
        'sekolah_id' => $sekolah->id,
        'username' => 'admin_test',
    ]);
    $this->admin->assignRole('admin');
    $this->sekolahId = $sekolah->id;
});

test('deleting siswa sets deleted_at instead of removing row', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolahId,
        'nis' => '2024000099',
        'name' => 'Siswa Soft Delete',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $siswa->delete();

    expect(Siswa::count())->toBe(0)
        ->and(Siswa::withTrashed()->count())->toBe(1)
        ->and(Siswa::withTrashed()->first()?->deleted_at)->not->toBeNull();
});

test('same nis can be reused after soft delete', function () {
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolahId,
        'nis' => '2024000088',
        'name' => 'Siswa Lama',
        'status' => \App\Models\Siswa::STATUS_INACTIVE,
    ]);

    $siswa->delete();

    $replacement = Siswa::create([
        'sekolah_id' => $this->sekolahId,
        'nis' => '2024000088',
        'name' => 'Siswa Baru',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    expect($replacement->exists)->toBeTrue()
        ->and(Siswa::where('nis', '2024000088')->count())->toBe(1);
});
