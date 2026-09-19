<?php

use App\Models\JenisPrestasi;
use App\Models\Kelas;
use App\Models\PrestasiSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\PrestasiCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $this->admin = User::create([
        'username' => 'admin.katalog.prestasi',
        'name' => 'Admin Katalog Prestasi',
        'email' => 'admin-katalog-prestasi@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->admin->assignRole('admin');

    $this->kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'VII A',
        'kelas' => 'VII',
        'kelompok' => 'A',
        'unit' => 'MTs',
        'jenjang' => 'Menengah Pertama',
    ]);

    $this->siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $this->kelas->id,
        'nis' => '1000001',
        'name' => 'Ahmad Siswa',
        'gender' => 'L',
        'status' => 1,
    ]);
});

function makePrestasiCatalog(array $overrides = []): JenisPrestasi
{
    return JenisPrestasi::create(array_merge([
        'bidang' => 'Akademik',
        'nama' => 'Juara 1 Olimpiade',
        'point' => 50,
        'is_active' => true,
    ], $overrides));
}

test('prestasi catalog seeder inserts zero rows', function () {
    $this->seed(PrestasiCatalogSeeder::class);

    expect(JenisPrestasi::count())->toBe(0);
});

test('admin can view and store katalog prestasi', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.prestasi-pelanggaran.katalog-prestasi.index'))
        ->assertOk();

    $this->actingAs($this->admin)->postJson(
        route('admin.prestasi-pelanggaran.katalog-prestasi.store'),
        [
            'bidang' => 'Olahraga',
            'nama' => 'Juara Futsal',
            'point' => 25,
            'is_active' => '1',
        ]
    )->assertCreated()->assertJsonPath('success', true);

    $this->assertDatabaseHas('jenis_prestasi', [
        'nama' => 'Juara Futsal',
        'bidang' => 'Olahraga',
        'point' => 25,
    ]);
});

test('katalog prestasi name is locked when used by prestasi siswa', function () {
    $catalog = makePrestasiCatalog();

    PrestasiSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_prestasi_id' => $catalog->id,
        'judul' => $catalog->nama,
        'tanggal' => now(),
        'point' => $catalog->point,
        'reported_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->putJson(
        route('admin.prestasi-pelanggaran.katalog-prestasi.update', $catalog),
        [
            'bidang' => 'Akademik',
            'nama' => 'Nama Baru Tidak Boleh',
            'point' => 99,
            'is_active' => '1',
        ]
    )->assertOk();

    $catalog->refresh();
    expect($catalog->nama)->toBe('Juara 1 Olimpiade')
        ->and($catalog->point)->toBe(99);

    $this->actingAs($this->admin)->deleteJson(
        route('admin.prestasi-pelanggaran.katalog-prestasi.destroy', $catalog)
    )->assertStatus(422);

    $this->assertDatabaseHas('jenis_prestasi', ['id' => $catalog->id, 'deleted_at' => null]);
});

test('admin can store prestasi siswa with jenis_prestasi_id', function () {
    $catalog = makePrestasiCatalog(['nama' => 'Pidato Bahasa Arab', 'point' => 40]);

    $this->actingAs($this->admin)->postJson(
        route('admin.prestasi-pelanggaran.prestasi-siswa.store'),
        [
            'siswa_ids' => [$this->siswa->id],
            'jenis_prestasi_id' => $catalog->id,
            'judul' => $catalog->nama,
            'tanggal' => now()->format('Y-m-d'),
            'point' => $catalog->point,
        ]
    )->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('prestasi_siswa', [
        'siswa_id' => $this->siswa->id,
        'jenis_prestasi_id' => $catalog->id,
        'judul' => 'Pidato Bahasa Arab',
        'point' => 40,
    ]);
});
