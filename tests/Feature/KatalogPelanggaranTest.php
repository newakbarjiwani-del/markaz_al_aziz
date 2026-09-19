<?php

use App\Models\JenisPelanggaran;
use App\Models\Kelas;
use App\Models\PelanggaranSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
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
        'username' => 'admin.katalog',
        'name' => 'Admin Katalog',
        'email' => 'admin-katalog@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->admin->assignRole('admin');

    $this->guru = User::create([
        'username' => 'guru.katalog',
        'name' => 'Guru Katalog',
        'email' => 'guru-katalog@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->guru->assignRole('guru');

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

function makeCatalog(array $overrides = []): JenisPelanggaran
{
    return JenisPelanggaran::create(array_merge([
        'level' => 'ringan',
        'bidang' => 'Kedisiplinan',
        'nama' => 'Terlambat salat berjamaah',
        'point' => 10,
    ], $overrides));
}

function validCatalogPayload(array $overrides = []): array
{
    return array_merge([
        'level' => 'sedang',
        'bidang' => 'Administrasi',
        'nama' => 'Tidak membawa buku laporan',
        'point' => 25,
        'sanction' => 'SP1',
        'sort_order' => 3,
        'is_active' => '1',
    ], $overrides);
}

// ── Access control ──

test('unauthenticated user cannot access katalog pelanggaran data', function () {
    $this->getJson(route('admin.prestasi-pelanggaran.katalog-pelanggaran.data'))
        ->assertUnauthorized();
});

test('guru without permission cannot access katalog pelanggaran', function () {
    $this->actingAs($this->guru)->getJson(route('admin.prestasi-pelanggaran.katalog-pelanggaran.data'))
        ->assertForbidden();
});

test('admin can view katalog pelanggaran index', function () {
    $this->actingAs($this->admin)->get(route('admin.prestasi-pelanggaran.katalog-pelanggaran.index'))
        ->assertOk();
});

test('level column sorts semantically (ringan -> sedang -> berat)', function () {
    makeCatalog(['nama' => 'Berat A', 'level' => 'berat']);
    makeCatalog(['nama' => 'Ringan A', 'level' => 'ringan']);
    makeCatalog(['nama' => 'Sedang A', 'level' => 'sedang']);

    $response = $this->actingAs($this->admin)->getJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.data').'?order[0][column]=0&order[0][dir]=asc'
    )->assertOk();

    // Row cell 0 is the Level badge {display, raw, type}; `raw` is the label.
    $levels = array_map(fn ($row) => $row[0]['raw'], $response->json('data'));

    expect($levels)->toBe(['Ringan', 'Sedang', 'Berat']);
});

// ── Store ──

test('admin can store a catalog entry', function () {
    $response = $this->actingAs($this->admin)->postJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.store'),
        validCatalogPayload()
    );

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('jenis_pelanggaran', [
        'nama' => 'Tidak membawa buku laporan',
        'level' => 'sedang',
        'bidang' => 'Administrasi',
        'point' => 25,
        'sanction' => 'SP1',
        'sort_order' => 3,
        'is_active' => 1,
    ]);
});

test('store rejects duplicate nama', function () {
    makeCatalog(['nama' => 'Terlambat masuk kelas']);

    $this->actingAs($this->admin)->postJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.store'),
        validCatalogPayload(['nama' => 'Terlambat masuk kelas'])
    )->assertStatus(422)->assertJsonValidationErrors('nama');
});

// ── Update (not used) ──

test('admin can update catalog entry when not used (nama editable)', function () {
    $catalog = makeCatalog();

    $this->actingAs($this->admin)->putJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.update', $catalog),
        validCatalogPayload(['nama' => 'Nama baru tidak terpakai'])
    )->assertOk()->assertJsonPath('success', true);

    $this->assertDatabaseHas('jenis_pelanggaran', [
        'id' => $catalog->id,
        'nama' => 'Nama baru tidak terpakai',
        'point' => 25,
    ]);
});

// ── Update (used → partial edit, nama locked) ──

test('update is partial when catalog is used - nama stays, other fields change', function () {
    $catalog = makeCatalog(['point' => 10]);
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $catalog->id,
        'judul' => 'Terlambat salat berjamaah',
        'tanggal' => now()->subDay(),
        'point' => 10,
    ]);

    $this->actingAs($this->admin)->putJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.update', $catalog),
        validCatalogPayload(['nama' => 'SEHARUSNYA TIDAK BERUBAH', 'point' => 77, 'bidang' => 'Kedisiplinan'])
    )->assertOk()->assertJsonPath('success', true);

    $fresh = $catalog->fresh();
    expect($fresh->nama)->toBe('Terlambat salat berjamaah')
        ->and($fresh->point)->toBe(77)
        ->and($fresh->bidang)->toBe('Kedisiplinan');
});

// ── Destroy ──

test('destroy is blocked when catalog is used', function () {
    $catalog = makeCatalog();
    PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $catalog->id,
        'judul' => 'Terlambat salat berjamaah',
        'tanggal' => now()->subDay(),
        'point' => 10,
    ]);

    $this->actingAs($this->admin)->deleteJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.destroy', $catalog)
    )->assertStatus(422)->assertJsonPath('success', false);

    $this->assertDatabaseHas('jenis_pelanggaran', ['id' => $catalog->id]);
});

test('destroy works when catalog is unused', function () {
    $catalog = makeCatalog();

    $this->actingAs($this->admin)->deleteJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.destroy', $catalog)
    )->assertOk()->assertJsonPath('success', true);

    $this->assertSoftDeleted('jenis_pelanggaran', ['id' => $catalog->id]);
});

// ── Lookup ──

test('lookup returns active catalog entries with nama + point payload', function () {
    makeCatalog(['nama' => 'Membuang sampah sembarangan', 'level' => 'ringan', 'point' => 5]);
    makeCatalog(['nama' => 'Merokok di lingkungan sekolah', 'level' => 'berat', 'point' => 50, 'is_active' => false]);

    $response = $this->actingAs($this->admin)->getJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.lookup').'?term=sampah'
    );

    $response->assertOk();
    $results = $response->json('results');
    expect(count($results))->toBe(1)
        ->and($results[0]['nama'])->toBe('Membuang sampah sembarangan')
        ->and($results[0]['point'])->toBe(5);

    // inactive excluded
    $all = $this->actingAs($this->admin)->getJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.lookup')
    )->json('results');
    expect(collect($all)->pluck('nama'))->not->toContain('Merokok di lingkungan sekolah');
});

test('lookupShow returns a single catalog entry', function () {
    $catalog = makeCatalog(['point' => 40]);

    $response = $this->actingAs($this->admin)->getJson(
        route('admin.prestasi-pelanggaran.katalog-pelanggaran.lookup.show', $catalog)
    );

    $response->assertOk()
        ->assertJsonPath('id', $catalog->id)
        ->assertJsonPath('nama', 'Terlambat salat berjamaah')
        ->assertJsonPath('point', 40);
});

// ── Sub-data wiring (pelanggaran siswa) ──

test('admin can store pelanggaran siswa referencing a catalog entry', function () {
    $catalog = makeCatalog(['point' => 10]);

    $response = $this->actingAs($this->admin)->postJson(
        route('admin.prestasi-pelanggaran.pelanggaran-siswa.store'),
        [
            'siswa_id' => $this->siswa->id,
            'jenis_pelanggaran_id' => $catalog->id,
            'judul' => 'Terlambat salat berjamaah',
            'tanggal' => now()->subDay()->format('Y-m-d'),
            'point' => 10,
        ]
    );

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('pelanggaran_siswa', [
        'siswa_id' => $this->siswa->id,
        'jenis_pelanggaran_id' => $catalog->id,
        'judul' => 'Terlambat salat berjamaah',
    ]);
});

test('pelanggaran siswa show returns catalog info', function () {
    $catalog = makeCatalog(['point' => 10, 'level' => 'ringan', 'sanction' => 'sp1']);

    $record = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $catalog->id,
        'judul' => 'Terlambat salat berjamaah',
        'tanggal' => now()->subDay(),
        'point' => 10,
    ]);

    $response = $this->actingAs($this->admin)->getJson(
        route('admin.prestasi-pelanggaran.pelanggaran-siswa.show', $record)
    );

    $response->assertOk()
        ->assertJsonPath('data.jenis_pelanggaran_id', $catalog->id)
        ->assertJsonPath('data.jenis_nama', 'Terlambat salat berjamaah')
        ->assertJsonPath('data.jenis_level', 'Ringan')
        ->assertJsonPath('data.jenis_sanction', 'Surat Peringatan 1')
        ->assertJsonPath('data.jenis_point', 10);
});

test('pelanggaran siswa store rejects invalid jenis_pelanggaran_id', function () {
    $this->actingAs($this->admin)->postJson(
        route('admin.prestasi-pelanggaran.pelanggaran-siswa.store'),
        [
            'siswa_id' => $this->siswa->id,
            'jenis_pelanggaran_id' => 99999,
            'judul' => 'Terlambat salat berjamaah',
            'tanggal' => now()->subDay()->format('Y-m-d'),
            'point' => 10,
        ]
    )->assertStatus(422)->assertJsonValidationErrors('jenis_pelanggaran_id');
});

test('pelanggaran siswa update can change the catalog reference', function () {
    $catalog = makeCatalog(['point' => 10]);
    $other = makeCatalog(['nama' => 'Berkelahi', 'point' => 20]);

    $record = PelanggaranSiswa::create([
        'siswa_id' => $this->siswa->id,
        'sekolah_id' => $this->sekolah->id,
        'jenis_pelanggaran_id' => $catalog->id,
        'judul' => 'Terlambat salat berjamaah',
        'tanggal' => now()->subDay(),
        'point' => 10,
    ]);

    $this->actingAs($this->admin)->putJson(
        route('admin.prestasi-pelanggaran.pelanggaran-siswa.update', $record),
        [
            'siswa_id' => $this->siswa->id,
            'jenis_pelanggaran_id' => $other->id,
            'judul' => 'Berkelahi',
            'tanggal' => now()->subDay()->format('Y-m-d'),
            'point' => 20,
        ]
    )->assertOk()->assertJsonPath('success', true);

    $fresh = $record->fresh();
    expect($fresh->jenis_pelanggaran_id)->toBe($other->id)
        ->and($fresh->judul)->toBe('Berkelahi')
        ->and($fresh->point)->toBe(20);
});

// ── Portal guru lookup route exists and is guru-accessible ──

test('guru can access portal katalog lookup', function () {
    makeCatalog(['nama' => 'Berkelahi', 'level' => 'berat']);

    $response = $this->actingAs($this->guru)->getJson(
        route('portal.guru.pelanggaran-siswa.katalog-lookup').'?term=berkelahi'
    );

    $response->assertOk();
    expect($response->json('results')[0]['nama'])->toBe('Berkelahi');
});
