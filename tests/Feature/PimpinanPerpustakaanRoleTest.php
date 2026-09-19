<?php

use App\Models\Sekolah;
use App\Models\User;
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

    $this->sekolah = $sekolah;

    $this->pimpinan = User::create([
        'username' => 'pimpinan.test',
        'name' => 'Pimpinan Test',
        'email' => 'pimpinan@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);
    $this->pimpinan->assignRole('pimpinan');

    $this->perpustakaan = User::create([
        'username' => 'perpustakaan.test',
        'name' => 'Perpustakaan Test',
        'email' => 'perpustakaan@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $sekolah->id,
    ]);
    $this->perpustakaan->assignRole('perpustakaan');
});

test('pimpinan home redirect goes to pimpinan dashboard', function () {
    $this->actingAs($this->pimpinan)
        ->get('/')
        ->assertRedirect(route('portal.pimpinan.dashboard'));
});

test('pimpinan can access dashboard and reports', function () {
    $this->actingAs($this->pimpinan)
        ->get(route('portal.pimpinan.dashboard'))
        ->assertOk()
        ->assertSee('ringkasan absensi');

    $this->actingAs($this->pimpinan)
        ->get(route('portal.pimpinan.laporan-keuangan'))
        ->assertOk();

    $this->actingAs($this->pimpinan)
        ->get(route('portal.pimpinan.laporan-kantin'))
        ->assertOk();
});

test('pimpinan can access admin dashboard for prestasi shell', function () {
    $this->actingAs($this->pimpinan)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('perpustakaan home redirect goes to perpustakaan dashboard', function () {
    $this->actingAs($this->perpustakaan)
        ->get('/')
        ->assertRedirect(route('portal.perpustakaan.dashboard'));
});

test('perpustakaan can access library pages', function () {
    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.dashboard'))
        ->assertOk()
        ->assertSee('Portal Perpustakaan');

    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.katalog-buku.index'))
        ->assertOk();

    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.peminjaman.index'))
        ->assertOk();

    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.rekap-pengunjung.index'))
        ->assertOk();
});

test('perpustakaan cannot access admin dashboard', function () {
    $this->actingAs($this->perpustakaan)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('perpustakaan without sekolah can access library loan pages', function () {
    $perpustakaan = User::create([
        'username' => 'perpustakaan.all',
        'name' => 'Perpustakaan All Schools',
        'email' => 'perpustakaan-all@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => null,
    ]);
    $perpustakaan->assignRole('perpustakaan');

    $this->actingAs($perpustakaan)
        ->get(route('portal.perpustakaan.peminjaman.index'))
        ->assertOk();

    $this->actingAs($perpustakaan)
        ->get(route('portal.perpustakaan.peminjaman.create'))
        ->assertOk();

    $this->actingAs($perpustakaan)
        ->get(route('portal.perpustakaan.pengembalian-buku'))
        ->assertOk();

    $this->actingAs($perpustakaan)
        ->get(route('portal.perpustakaan.setting-denda.index'))
        ->assertOk();
});
