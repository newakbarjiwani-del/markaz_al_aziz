<?php

use App\Models\Buku;
use App\Models\Sekolah;
use App\Services\BukuSpreadsheetImporter;
use App\Support\BukuSpreadsheetTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);

    $this->sekolah = Sekolah::create([
        'code' => 'ma',
        'name' => 'MA Test',
        'address' => 'Jl. Test',
    ]);

    $this->importer = app(BukuSpreadsheetImporter::class);
});

test('import stores kemenag sample row with nullable isbn and default cetak_ke', function () {
    $record = [
        'KODE_BUKU' => '',
        'ISBN' => '',
        'JUDUL' => 'Pedoman Pengelolaan Perpustakaan Masjid',
        'PENGARANG' => 'Direktorat URAIS',
        'PENERBIT' => 'Kemenag RI',
        'TAHUN_TERBIT' => '2023',
        'CETAK_KE' => '3',
        'JUMLAH' => '10',
        'KEADAAN_BAIK' => '5',
        'KEADAAN_RUSAK_RINGAN' => '3',
        'KEADAAN_RUSAK_BERAT' => '2',
        'TANGGAL_PENERIMAAN' => '10-01-2024',
        'SUMBER_DANA' => 'Bantuan Kemenag RI',
        'KETERANGAN' => '',
    ];

    $result = $this->importer->import(null, [$record]);

    expect($result['created'])->toBe(1)
        ->and($result['errors'])->toBe([]);

    $buku = Buku::query()->first();

    expect($buku->sekolah_id)->toBeNull()
        ->and($buku->isbn)->toBeNull()
        ->and($buku->isbn_key)->toBeNull()
        ->and($buku->judul)->toBe('Pedoman Pengelolaan Perpustakaan Masjid')
        ->and($buku->pengarang)->toBe('Direktorat URAIS')
        ->and($buku->penerbit)->toBe('Kemenag RI')
        ->and($buku->tahun_terbit)->toBe(2023)
        ->and($buku->cetak_ke)->toBe(3)
        ->and($buku->jumlah)->toBe(10)
        ->and($buku->keadaan_baik)->toBe(5)
        ->and($buku->tersedia)->toBe(5)
        ->and($buku->tanggal_penerimaan?->format('Y-m-d'))->toBe('2024-01-10')
        ->and($buku->sumber_dana)->toBe('Bantuan Kemenag RI');
});

test('import defaults cetak_ke to 1 when empty', function () {
    $this->importer->import(null, [[
        'JUDUL' => 'Buku Tanpa Cetakan',
        'JUMLAH' => '2',
    ]]);

    expect(Buku::query()->value('cetak_ke'))->toBe(1);
});

test('import rejects invalid keadaan sum in preview', function () {
    $preview = $this->importer->preview(null, [[
        'JUDUL' => 'Buku Invalid',
        'JUMLAH' => '10',
        'KEADAAN_BAIK' => '5',
        'KEADAAN_RUSAK_RINGAN' => '2',
        'KEADAAN_RUSAK_BERAT' => '1',
    ]]);

    expect($preview['summary']['invalid'])->toBe(1);
});

test('import upserts by global isbn and allows reuse after soft delete', function () {
    $this->importer->import(null, [[
        'ISBN' => '9789990001',
        'JUDUL' => 'Buku ISBN',
        'JUMLAH' => '4',
    ]]);

    $this->importer->import(null, [[
        'ISBN' => '9789990001',
        'JUDUL' => 'Buku ISBN',
        'JUMLAH' => '6',
        'KEADAAN_BAIK' => '6',
    ]]);

    expect(Buku::query()->count())->toBe(1)
        ->and(Buku::query()->value('jumlah'))->toBe(6);

    Buku::query()->first()->delete();

    $this->importer->import(null, [[
        'ISBN' => '9789990001',
        'JUDUL' => 'Buku ISBN Baru',
        'JUMLAH' => '2',
    ]]);

    expect(Buku::withTrashed()->count())->toBe(2)
        ->and(Buku::query()->value('judul'))->toBe('Buku ISBN Baru');
});

test('siswa can borrow global buku from any school', function () {
    $global = Buku::create([
        'sekolah_id' => null,
        'judul' => 'Buku Global',
        'jumlah' => 3,
        'keadaan_baik' => 3,
        'tersedia' => 3,
    ]);

    $siswa = \App\Models\Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => \App\Models\Kelas::create([
            'sekolah_id' => $this->sekolah->id,
            'name' => 'VII A',
        ])->id,
        'nis' => '90001',
        'name' => 'Siswa Global',
        'status' => \App\Models\Siswa::STATUS_ACTIVE,
    ]);

    $loan = app(\App\Services\LibraryLoanService::class)->borrow([
        'borrower_type' => \App\Models\PeminjamanBuku::BORROWER_SISWA,
        'siswa_id' => $siswa->id,
    ], $global->id);

    expect($loan)->toBeInstanceOf(\App\Models\PeminjamanBuku::class)
        ->and($global->fresh()->tersedia)->toBe(2);
});

test('template parses kemenag headers', function () {
    expect(BukuSpreadsheetTemplate::normalizeHeader('JUDUL BUKU'))->toBe('JUDUL')
        ->and(BukuSpreadsheetTemplate::normalizeHeader('No. KODE BUKU'))->toBe('KODE_BUKU')
        ->and(BukuSpreadsheetTemplate::normalizeHeader('Tanggal Penerimaan'))->toBe('TANGGAL_PENERIMAAN');
});
