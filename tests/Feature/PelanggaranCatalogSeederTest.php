<?php

use App\Models\JenisPelanggaran;
use App\Support\PelanggaranSanction;
use Database\Seeders\PelanggaranCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('catalog seeder imports all extracted violations', function () {
    $this->seed(PelanggaranCatalogSeeder::class);

    expect(JenisPelanggaran::count())->toBe(152);
});

test('catalog seeder splits levels matching the extraction', function () {
    $this->seed(PelanggaranCatalogSeeder::class);

    expect(JenisPelanggaran::where('level', 'ringan')->count())->toBe(46)
        ->and(JenisPelanggaran::where('level', 'sedang')->count())->toBe(51)
        ->and(JenisPelanggaran::where('level', 'berat')->count())->toBe(55);
});

test('catalog is universal by default', function () {
    $this->seed(PelanggaranCatalogSeeder::class);

    expect(JenisPelanggaran::whereNull('sekolah_id')->count())->toBe(152);
});

test('catalog keeps distinct same-name violations from different bidang', function () {
    $this->seed(PelanggaranCatalogSeeder::class);

    $base = JenisPelanggaran::where('nama', 'Tidak melapor setelah kembali dari izin')->first();
    $disambiguated = JenisPelanggaran::where('nama', 'Tidak melapor setelah kembali dari izin (Disiplin)')->first();

    expect($base)->not->toBeNull()
        ->and($base->level)->toBe('ringan')
        ->and($base->bidang)->toBe('Administrasi')
        ->and($base->point)->toBe(10)
        ->and($disambiguated)->not->toBeNull()
        ->and($disambiguated->level)->toBe('sedang')
        ->and($disambiguated->bidang)->toBe('Disiplin')
        ->and($disambiguated->point)->toBe(25);
});

test('catalog normalizes sanctions from the source', function () {
    $this->seed(PelanggaranCatalogSeeder::class);

    expect(JenisPelanggaran::where('sanction', PelanggaranSanction::DO)->count())->toBe(36)
        ->and(JenisPelanggaran::where('sanction', PelanggaranSanction::SP3)->count())->toBe(26)
        ->and(JenisPelanggaran::where('sanction', PelanggaranSanction::SP1)->count())->toBe(1)
        ->and(JenisPelanggaran::where('sanction', PelanggaranSanction::GANTI_RUGI)->count())->toBe(1);
});

test('catalog seeder is idempotent', function () {
    $this->seed(PelanggaranCatalogSeeder::class);
    $first = JenisPelanggaran::pluck('point', 'nama')->all();

    $this->seed(PelanggaranCatalogSeeder::class);

    expect(JenisPelanggaran::count())->toBe(152)
        ->and(JenisPelanggaran::pluck('point', 'nama')->all())->toBe($first);
});

test('berat sub-bidang become distinct bidang rows', function () {
    $this->seed(PelanggaranCatalogSeeder::class);

    expect(JenisPelanggaran::where('level', 'berat')->distinct()->count('bidang'))->toBe(10);
});
