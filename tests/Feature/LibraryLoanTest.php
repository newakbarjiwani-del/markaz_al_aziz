<?php

use App\Models\Buku;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\User;
use App\Support\LibrarySettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

/**
 * Create a Peminjaman parent + one PeminjamanBuku child.
 * Returns the PeminjamanBuku child.
 */
function makeSiswaLoan(Buku $buku, Siswa $siswa, array $parentOverrides = [], array $childOverrides = []): PeminjamanBuku
{
    $parent = Peminjaman::create(array_merge([
        'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
        'siswa_id' => $siswa->id,
        'loan_date' => now()->subDays(3)->toDateString(),
        'due_date' => now()->addDays(4)->toDateString(),
    ], $parentOverrides));

    return PeminjamanBuku::create(array_merge([
        'peminjaman_id' => $parent->id,
        'buku_id' => $buku->id,
        'status' => PeminjamanBuku::STATUS_DIPINJAM,
    ], $childOverrides));
}

function makeGuruLoan(Buku $buku, Guru $guru, array $parentOverrides = [], array $childOverrides = []): PeminjamanBuku
{
    $parent = Peminjaman::create(array_merge([
        'borrower_type' => PeminjamanBuku::BORROWER_GURU,
        'guru_id' => $guru->id,
        'loan_date' => now()->subDays(1)->toDateString(),
        'due_date' => now()->addDays(6)->toDateString(),
    ], $parentOverrides));

    return PeminjamanBuku::create(array_merge([
        'peminjaman_id' => $parent->id,
        'buku_id' => $buku->id,
        'status' => PeminjamanBuku::STATUS_DIPINJAM,
    ], $childOverrides));
}

function makeTamuLoan(Buku $buku, string $nama, array $parentOverrides = [], array $childOverrides = []): PeminjamanBuku
{
    $parent = Peminjaman::create(array_merge([
        'borrower_type' => PeminjamanBuku::BORROWER_TAMU,
        'tamu_nama' => $nama,
        'loan_date' => now()->toDateString(),
        'due_date' => now()->addDays(7)->toDateString(),
    ], $parentOverrides));

    return PeminjamanBuku::create(array_merge([
        'peminjaman_id' => $parent->id,
        'buku_id' => $buku->id,
        'status' => PeminjamanBuku::STATUS_DIPINJAM,
    ], $childOverrides));
}

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);

    $sekolah = Sekolah::create([
        'code' => 'TST',
        'name' => 'Test School',
        'address' => 'Jl. Test',
    ]);

    $otherSekolah = Sekolah::create([
        'code' => 'OTH',
        'name' => 'Other School',
        'address' => 'Jl. Other',
    ]);

    $kelas = Kelas::create([
        'sekolah_id' => $sekolah->id,
        'name' => 'Kelas 7A',
        'level' => '7',
    ]);

    $this->sekolah = $sekolah;
    $this->siswa = Siswa::create([
        'sekolah_id' => $sekolah->id,
        'kelas_id' => $kelas->id,
        'nis' => '70001',
        'name' => 'Siswa Test',
        'gender' => 'L',
        'status' => Siswa::STATUS_ACTIVE,
    ]);

    $this->buku = Buku::create([
        'sekolah_id' => $sekolah->id,
        'isbn' => '978-TEST',
        'judul' => 'Buku Test',
        'pengarang' => 'Penulis Test',
        'kategori' => 'pelajaran',
        'jumlah' => 5,
        'keadaan_baik' => 5,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 3,
    ]);

    $this->otherBuku = Buku::create([
        'sekolah_id' => $otherSekolah->id,
        'isbn' => '978-OTHER',
        'judul' => 'Buku Other',
        'pengarang' => 'Penulis Other',
        'kategori' => 'pelajaran',
        'jumlah' => 5,
        'keadaan_baik' => 5,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 3,
    ]);

    $this->admin = User::create([
        'username' => 'admin.test',
        'name' => 'Admin Test',
        'email' => 'admin@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
    ]);
    $this->admin->assignRole('admin');

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

// ─── Tests ────────────────────────────────────────────────────────────────────

test('admin can borrow book with notes and custom dates', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'siswa_id' => $this->siswa->id,
            'buku_id' => $this->buku->id,
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'catatan_pinjam' => 'Untuk tugas sejarah',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $loan = PeminjamanBuku::query()->first();
    expect($loan)->not->toBeNull()
        ->and($loan->status)->toBe('dipinjam')
        ->and($loan->peminjaman?->catatan_pinjam)->toBe('Untuk tugas sejarah')
        ->and($loan->due_date?->toDateString())->toBe(now()->addDays(10)->toDateString());

    expect($this->buku->fresh()->tersedia)->toBe(2);
});

test('admin cannot borrow when stock empty', function () {
    $this->buku->update(['tersedia' => 0]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'siswa_id' => $this->siswa->id,
            'buku_id' => $this->buku->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['buku_ids']);
});

test('admin can return borrowed book and restore stock', function () {
    $loan = makeSiswaLoan($this->buku, $this->siswa, [], ['status' => 'dipinjam']);
    $this->buku->decrement('tersedia');

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.pengembalian-buku.store'), [
            'peminjaman_id' => $loan->peminjaman_id,
            'kondisi_kembali' => PeminjamanBuku::KONDISI_BAIK,
            'catatan_kembali' => 'Buku dalam kondisi baik',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $loan->refresh();
    expect($loan->status)->toBe('dikembalikan')
        ->and($loan->return_date)->not->toBeNull()
        ->and($loan->kondisi_kembali)->toBe(PeminjamanBuku::KONDISI_BAIK)
        ->and($this->buku->fresh()->tersedia)->toBe(3);
});

test('admin return marks lost book without restoring stock', function () {
    $loan = makeSiswaLoan($this->buku, $this->siswa, [], ['status' => 'dipinjam']);
    $this->buku->update(['tersedia' => 2]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.pengembalian-buku.store'), [
            'peminjaman_id' => $loan->peminjaman_id,
            'kondisi_kembali' => PeminjamanBuku::KONDISI_HILANG,
        ])
        ->assertOk();

    $loan->refresh();
    expect($loan->status)->toBe('hilang')
        ->and((float) $loan->fine_amount)->toBe(50000.0)
        ->and($this->buku->fresh()->tersedia)->toBe(2);
});

test('admin can extend active loan with custom due date', function () {
    $loan = makeSiswaLoan($this->buku, $this->siswa, [
        'loan_date' => now()->subDays(2)->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
    ]);

    $newDueDate = now()->addDays(12)->toDateString();

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.extend', $loan), [
            'due_date' => $newDueDate,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $loan->refresh();
    $loan->load('peminjaman');
    expect($loan->perpanjangan_count)->toBe(1)
        ->and($loan->due_date?->toDateString())->toBe($newDueDate);
});

test('portal perpustakaan can borrow book for own school', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.peminjaman.store'), [
            'siswa_id' => $this->siswa->id,
            'buku_id' => $this->buku->id,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', route('portal.perpustakaan.peminjaman.index'));

    expect(PeminjamanBuku::query()->count())->toBe(1);
});

test('portal perpustakaan can borrow book from other school', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.peminjaman.store'), [
            'siswa_id' => $this->siswa->id,
            'buku_id' => $this->otherBuku->id,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('portal perpustakaan can return book with late fine', function () {
    $loan = makeSiswaLoan($this->buku, $this->siswa, [
        'loan_date' => now()->subDays(10)->toDateString(),
        'due_date' => now()->subDays(3)->toDateString(),
    ]);
    $this->buku->update(['tersedia' => 2]);

    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.pengembalian-buku.store'), [
            'peminjaman_id' => $loan->peminjaman_id,
            'kondisi_kembali' => PeminjamanBuku::KONDISI_BAIK,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $loan->refresh();
    expect($loan->status)->toBe('dikembalikan')
        ->and((float) $loan->fine_amount)->toBe(6000.0)
        ->and($loan->late_days)->toBe(3);
});

test('siswa ringkasan endpoint returns active loan quota', function () {
    makeSiswaLoan($this->buku, $this->siswa, [
        'loan_date' => now()->subDays(1)->toDateString(),
        'due_date' => now()->addDays(6)->toDateString(),
    ]);

    $this->actingAs($this->perpustakaan)
        ->getJson(route('portal.perpustakaan.peminjaman.siswa-summary', $this->siswa))
        ->assertOk()
        ->assertJsonPath('data.active_count', 1)
        ->assertJsonPath('data.remaining_quota', 2);
});

test('return preview endpoint calculates fine breakdown', function () {
    $loan = makeSiswaLoan($this->buku, $this->siswa, [
        'loan_date' => now()->subDays(10)->toDateString(),
        'due_date' => now()->subDays(2)->toDateString(),
    ]);

    $this->actingAs($this->perpustakaan)
        ->getJson(route('portal.perpustakaan.pengembalian-buku.preview', [
            'peminjaman' => $loan->peminjaman_id,
            'kondisi_kembali' => PeminjamanBuku::KONDISI_RUSAK_BERAT,
        ]))
        ->assertOk()
        ->assertJsonPath('data.fine_preview.late_days', 2)
        ->assertJsonPath('data.fine_preview.damage_fine', 25000);
});

test('peminjaman index shows create button', function () {
    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.peminjaman.index'))
        ->assertOk()
        ->assertSee('Pinjam Buku')
        ->assertSee('Setting Denda')
        ->assertDontSee('Pinjam Buku Baru');
});

test('peminjaman create page shows form', function () {
    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.peminjaman.create'))
        ->assertOk()
        ->assertSee('Pinjam Buku Baru')
        ->assertSee('Ringkasan Peminjam')
        ->assertSee('Tipe peminjam')
        ->assertSee('per peminjam');
});

test('portal can update fine settings', function () {
    $this->actingAs($this->perpustakaan)
        ->putJson(route('portal.perpustakaan.setting-denda.update'), [
            'fine_per_day' => 3000,
            'kondisi_fines' => [
                [
                    'kondisi' => PeminjamanBuku::KONDISI_RUSAK_BERAT,
                    'label' => 'Rusak berat',
                    'amount' => 30000,
                ],
                [
                    'kondisi' => PeminjamanBuku::KONDISI_HILANG,
                    'label' => 'Hilang',
                    'amount' => 60000,
                ],
                [
                    'kondisi' => PeminjamanBuku::KONDISI_RUSAK_RINGAN,
                    'label' => 'Rusak ringan',
                    'amount' => 7500,
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(LibrarySettings::finePerDay())->toBe(3000)
        ->and(LibrarySettings::fineDamagedBook())->toBe(30000)
        ->and(LibrarySettings::fineLostBook())->toBe(60000)
        ->and(LibrarySettings::fineForKondisi(PeminjamanBuku::KONDISI_RUSAK_RINGAN))->toBe(7500);
});

test('pengembalian page uses active loan table and return modal', function () {
    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.pengembalian-buku'))
        ->assertOk()
        ->assertSee('Pengembalian Buku')
        ->assertSee('Buku Sedang Dipinjam')
        ->assertSee('loan-return-modal', false)
        ->assertSee('Kondisi Buku')
        ->assertDontSee('Peminjaman Aktif')
        ->assertDontSee('Preview Pengembalian');
});

test('admin can borrow book for guru', function () {
    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '1980010101',
        'name' => 'Guru Test',
        'jabatan' => 'Guru Mapel',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_GURU,
            'guru_id' => $guru->id,
            'buku_id' => $this->buku->id,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $loan = PeminjamanBuku::query()->with('peminjaman')->first();
    expect($loan)->not->toBeNull()
        ->and($loan->borrower_type)->toBe(PeminjamanBuku::BORROWER_GURU)
        ->and($loan->peminjaman?->guru_id)->toBe($guru->id)
        ->and($loan->peminjaman?->siswa_id)->toBeNull()
        ->and($this->buku->fresh()->tersedia)->toBe(2);
});

test('admin can return guru loan', function () {
    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '1980010102',
        'name' => 'Guru Return',
        'status' => 'aktif',
    ]);

    $loan = makeGuruLoan($this->buku, $guru);
    $this->buku->decrement('tersedia');

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.pengembalian-buku.store'), [
            'peminjaman_id' => $loan->peminjaman_id,
            'kondisi_kembali' => PeminjamanBuku::KONDISI_BAIK,
        ])
        ->assertOk();

    expect($loan->fresh()->status)->toBe('dikembalikan')
        ->and($this->buku->fresh()->tersedia)->toBe(3);
});

test('inactive guru cannot borrow', function () {
    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '1980010199',
        'name' => 'Guru Nonaktif',
        'status' => 'nonaktif',
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_GURU,
            'guru_id' => $guru->id,
            'buku_id' => $this->buku->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['guru_id']);
});

test('admin can borrow book for tamu', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_TAMU,
            'tamu_nama' => 'Budi Tamu',
            'tamu_asal' => 'SMA Negeri 1',
            'tamu_telepon' => '08123456789',
            'buku_id' => $this->buku->id,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $loan = PeminjamanBuku::query()->with('peminjaman')->first();
    expect($loan)->not->toBeNull()
        ->and($loan->borrower_type)->toBe(PeminjamanBuku::BORROWER_TAMU)
        ->and($loan->peminjaman?->tamu_nama)->toBe('Budi Tamu')
        ->and($loan->peminjaman?->siswa_id)->toBeNull()
        ->and($loan->peminjaman?->guru_id)->toBeNull();
});

test('tamu quota is keyed by nama and telepon', function () {
    Storage::disk('local')->put('settings/library.json', json_encode(['max_books' => 1]));

    makeTamuLoan($this->buku, 'Budi Tamu', ['tamu_telepon' => '081111']);
    $this->buku->decrement('tersedia');

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_TAMU,
            'tamu_nama' => 'Budi Tamu',
            'tamu_telepon' => '081111',
            'buku_id' => $this->buku->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tamu_nama']);

    // Different phone = different guest identity
    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_TAMU,
            'tamu_nama' => 'Budi Tamu',
            'tamu_telepon' => '082222',
            'buku_id' => $this->buku->id,
        ])
        ->assertOk();
});

test('portal allows guru from other school', function () {
    $otherSekolah = Sekolah::where('code', 'OTH')->first();
    $otherGuru = Guru::create([
        'sekolah_id' => $otherSekolah->id,
        'nip' => '1990010101',
        'name' => 'Guru Other',
        'status' => 'aktif',
    ]);

    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_GURU,
            'guru_id' => $otherGuru->id,
            'buku_id' => $this->buku->id,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('portal can borrow for tamu with school-scoped book', function () {
    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_TAMU,
            'tamu_nama' => 'Tamu Portal',
            'buku_id' => $this->buku->id,
        ])
        ->assertOk();

    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_TAMU,
            'tamu_nama' => 'Tamu Portal',
            'buku_id' => $this->otherBuku->id,
        ])
        ->assertOk();
});

test('rfid resolve returns guru when siswa not found', function () {
    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '1980010155',
        'name' => 'Guru RFID',
        'status' => 'aktif',
    ]);
    assignRfid($guru, 'RFID-GURU-LOAN');

    $this->actingAs($this->perpustakaan)
        ->postJson(route('portal.perpustakaan.peminjaman.resolve-rfid'), [
            'rfid_uid' => 'RFID-GURU-LOAN',
        ])
        ->assertOk()
        ->assertJsonPath('data.borrower_type', PeminjamanBuku::BORROWER_GURU)
        ->assertJsonPath('data.guru.id', $guru->id);
});

test('guru summary endpoint returns active loan quota', function () {
    $guru = Guru::create([
        'sekolah_id' => $this->sekolah->id,
        'nip' => '1980010166',
        'name' => 'Guru Summary',
        'status' => 'aktif',
    ]);

    makeGuruLoan($this->buku, $guru);

    $this->actingAs($this->perpustakaan)
        ->getJson(route('portal.perpustakaan.peminjaman.guru-summary', $guru))
        ->assertOk()
        ->assertJsonPath('data.active_count', 1)
        ->assertJsonPath('data.remaining_quota', 2);
});

test('tamu summary endpoint returns active loan quota', function () {
    makeTamuLoan($this->buku, 'Tamu Ringkas', ['tamu_telepon' => '08999']);

    $this->actingAs($this->perpustakaan)
        ->getJson(route('portal.perpustakaan.peminjaman.tamu-summary', [
            'tamu_nama' => 'Tamu Ringkas',
            'tamu_telepon' => '08999',
        ]))
        ->assertOk()
        ->assertJsonPath('data.active_count', 1)
        ->assertJsonPath('data.borrower_type', PeminjamanBuku::BORROWER_TAMU);
});

test('admin can borrow multiple books at once', function () {
    $buku2 = Buku::create([
        'sekolah_id' => $this->sekolah->id,
        'isbn' => '978-MULTI-2',
        'judul' => 'Buku Kedua',
        'pengarang' => 'Penulis',
        'kategori' => 'pelajaran',
        'jumlah' => 3,
        'keadaan_baik' => 3,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 2,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
            'siswa_id' => $this->siswa->id,
            'buku_ids' => [$this->buku->id, $buku2->id],
            'catatan_pinjam' => 'Pinjam dua sekaligus',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.count', 2);

    // 2 child PeminjamanBuku rows linked to the same Peminjaman parent.
    $peminjamanIds = PeminjamanBuku::query()
        ->pluck('peminjaman_id')
        ->unique();
    expect($peminjamanIds->count())->toBe(1);
    expect(PeminjamanBuku::query()->count())->toBe(2);
    expect($this->buku->fresh()->tersedia)->toBe(2);
    expect($buku2->fresh()->tersedia)->toBe(1);
});

test('multi book borrow rejects when quota exceeded', function () {
    Storage::disk('local')->put('settings/library.json', json_encode(['max_books' => 1]));

    $buku2 = Buku::create([
        'sekolah_id' => $this->sekolah->id,
        'isbn' => '978-MULTI-Q',
        'judul' => 'Buku Kuota',
        'pengarang' => 'Penulis',
        'kategori' => 'pelajaran',
        'jumlah' => 3,
        'keadaan_baik' => 3,
        'keadaan_rusak_ringan' => 0,
        'keadaan_rusak_berat' => 0,
        'tersedia' => 2,
    ]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'siswa_id' => $this->siswa->id,
            'buku_ids' => [$this->buku->id, $buku2->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['buku_ids']);

    expect(PeminjamanBuku::query()->count())->toBe(0)
        ->and($this->buku->fresh()->tersedia)->toBe(3);
});

test('create page shows multi book picker', function () {
    $this->actingAs($this->perpustakaan)
        ->get(route('portal.perpustakaan.peminjaman.create'))
        ->assertOk()
        ->assertSee('loan-buku-add', false)
        ->assertSee('Tambahkan satu atau lebih buku');
});

test('admin can borrow multiple copies of the same book', function () {
    $this->actingAs($this->admin)
        ->postJson(route('admin.perpustakaan.peminjaman.store'), [
            'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
            'siswa_id' => $this->siswa->id,
            'buku_ids' => [$this->buku->id, $this->buku->id],
            'catatan_pinjam' => 'Pinjam dua kopi sekaligus',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(PeminjamanBuku::query()->count())->toBe(1)
        ->and(PeminjamanBuku::query()->sum('qty'))->toBe(2)
        ->and($this->buku->fresh()->tersedia)->toBe(1);
});

test('portal ortu can view perpustakaan data for linked child', function () {
    $ortu = OrangTua::create([
        'sekolah_id' => $this->sekolah->id,
        'nama_ayah' => 'Bapak Lib',
        'nama_ibu' => 'Ibu Lib',
        'status' => 'aktif',
    ]);
    $ortu->siswa()->attach($this->siswa->id);

    $ortuUser = User::create([
        'username' => 'ortu.lib',
        'name' => $ortu->displayName(),
        'email' => 'ortu-lib@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'orang_tua_id' => $ortu->id,
    ]);
    $ortuUser->assignRole('orang_tua');

    $loan = makeSiswaLoan($this->buku, $this->siswa);

    $this->actingAs($ortuUser)
        ->getJson(route('portal.ortu.perpustakaan.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});

test('portal siswa can view perpustakaan data', function () {
    $siswaUser = User::create([
        'username' => 'siswa.lib',
        'name' => $this->siswa->name,
        'email' => 'siswa-lib@test.local',
        'password' => Hash::make('password'),
        'status' => 'aktif',
        'sekolah_id' => $this->sekolah->id,
        'siswa_id' => $this->siswa->id,
    ]);
    $siswaUser->assignRole('siswa');

    $loan = makeSiswaLoan($this->buku, $this->siswa);

    $this->actingAs($siswaUser)
        ->getJson(route('portal.siswa.perpustakaan.data'))
        ->assertOk()
        ->assertJsonPath('recordsTotal', 1);
});
