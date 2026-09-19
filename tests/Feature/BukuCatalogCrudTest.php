<?php

use App\Models\Buku;
use App\Models\Kelas;
use App\Models\PeminjamanBuku;
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
        'username' => 'admin.buku',
        'name' => 'Admin Buku',
        'email' => 'admin-buku@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->admin->assignRole('admin');

    $this->perpustakaan = User::create([
        'username' => 'pustaka.buku',
        'name' => 'Petugas Pustaka',
        'email' => 'pustaka-buku@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
    ]);
    $this->perpustakaan->assignRole('perpustakaan');
});

function validBukuPayload(array $overrides = []): array
{
    return array_merge([
        'judul' => 'Buku Baru Katalog',
        'pengarang' => 'Penulis A',
        'penerbit' => 'Penerbit A',
        'kategori' => 'Umum',
        'tahun_terbit' => 2024,
        'cetak_ke' => 1,
        'jumlah' => 10,
        'keadaan_baik' => 7,
        'keadaan_rusak_ringan' => 2,
        'keadaan_rusak_berat' => 1,
        'tanggal_penerimaan' => '2024-01-15',
        'sumber_dana' => 'BOS',
        'keterangan' => 'Catatan uji',
    ], $overrides);
}

test('admin can open katalog buku page with create action', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.perpustakaan.katalog-buku.index'))
        ->assertOk()
        ->assertSee('Katalog Buku')
        ->assertSee('Tambah Buku');
});

test('admin can create book with inventory fields', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.katalog-buku.store'), validBukuPayload([
            'isbn' => '9786020000001',
            'kode_buku' => 'BK-001',
            'sekolah_id' => $this->sekolah->id,
        ]))
        ->assertCreated()
        ->assertJsonPath('success', true);

    $buku = Buku::query()->first();

    expect($buku)->not->toBeNull()
        ->and($buku->judul)->toBe('Buku Baru Katalog')
        ->and($buku->isbn)->toBe('9786020000001')
        ->and($buku->isbn_key)->toBe('9786020000001')
        ->and($buku->kode_buku)->toBe('BK-001')
        ->and($buku->sekolah_id)->toBe($this->sekolah->id)
        ->and($buku->jumlah)->toBe(10)
        ->and($buku->keadaan_baik)->toBe(7)
        ->and($buku->tersedia)->toBe(7)
        ->and($buku->cetak_ke)->toBe(1);
});

test('create rejects invalid keadaan sum', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.katalog-buku.store'), validBukuPayload([
            'jumlah' => 10,
            'keadaan_baik' => 5,
            'keadaan_rusak_ringan' => 2,
            'keadaan_rusak_berat' => 1,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['jumlah']);
});

test('isbn must be unique among active books', function () {
    Buku::create([
        'judul' => 'Existing',
        'isbn' => '9786029999999',
        'jumlah' => 1,
        'keadaan_baik' => 1,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 1,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.katalog-buku.store'), validBukuPayload([
            'isbn' => '9786029999999',
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['isbn']);
});

test('admin can update book and recalculate tersedia', function () {
    $buku = Buku::create([
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Lama',
        'jumlah' => 5,
        'keadaan_baik' => 5,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 5,
    ]);

    $this->actingAs($this->admin)
        ->putJson(route('admin.perpustakaan.katalog-buku.update', $buku), validBukuPayload([
            'judul' => 'Diperbarui',
            'jumlah' => 8,
            'keadaan_baik' => 6,
            'keadaan_rusak_ringan' => 1,
            'keadaan_rusak_berat' => 1,
        ]))
        ->assertOk()
        ->assertJsonPath('success', true);

    $buku->refresh();

    expect($buku->judul)->toBe('Diperbarui')
        ->and($buku->jumlah)->toBe(8)
        ->and($buku->keadaan_baik)->toBe(6)
        ->and($buku->tersedia)->toBe(6);
});

test('admin can soft delete book without active loans', function () {
    $buku = Buku::create([
        'judul' => 'Hapus Saya',
        'jumlah' => 2,
        'keadaan_baik' => 2,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 2,
    ]);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.perpustakaan.katalog-buku.destroy', $buku))
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Buku::query()->find($buku->id))->toBeNull()
        ->and(Buku::withTrashed()->find($buku->id))->not->toBeNull();
});

test('cannot delete book with active loan', function () {
    $kelas = Kelas::create([
        'sekolah_id' => $this->sekolah->id,
        'name' => 'X IPA 1',
        'level' => '10',
    ]);
    $siswa = Siswa::create([
        'sekolah_id' => $this->sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '90001',
        'name' => 'Siswa Pinjam',
        'status' => Siswa::STATUS_ACTIVE,
    ]);
    $buku = Buku::create([
        'sekolah_id' => $this->sekolah->id,
        'judul' => 'Sedang Dipinjam',
        'jumlah' => 2,
        'keadaan_baik' => 2,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 1,
    ]);
    $parent = \App\Models\Peminjaman::create([
        'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
        'siswa_id' => $siswa->id,
        'loan_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
    ]);

    PeminjamanBuku::create([
        'peminjaman_id' => $parent->id,
        'buku_id' => $buku->id,
        'status' => PeminjamanBuku::STATUS_DIPINJAM,
    ]);

    $this->actingAs($this->admin)
        ->deleteJson(route('admin.perpustakaan.katalog-buku.destroy', $buku))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(Buku::query()->find($buku->id))->not->toBeNull();
});

test('portal perpustakaan can create book scoped to operator school', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.katalog-buku.store'), validBukuPayload([
            'judul' => 'Buku Portal',
            'sekolah_id' => null,
        ]))
        ->assertCreated();

    $buku = Buku::query()->where('judul', 'Buku Portal')->first();

    expect($buku->sekolah_id)->toBe($this->sekolah->id);
});
