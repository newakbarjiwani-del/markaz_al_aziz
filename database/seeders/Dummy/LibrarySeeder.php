<?php

namespace Database\Seeders\Dummy;

use App\Models\Buku;
use App\Models\Guru;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\UlasanBuku;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // ─── Import 121 books from buku.json ──────────────────────────────
        $jsonPath = base_path('buku.json');
        if (! file_exists($jsonPath)) {
            $this->command->warn('buku.json not found, skipping library seed.');

            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (empty($data)) {
            return;
        }

        $agamaKeywords = ['FIKIH', 'TAFSIR', 'HADIS', "QUR'AN", 'AKHLAK', 'SKI', 'AL-QUR\'AN'];

        foreach ($data as $book) {
            $kodeBuku = $book['kode_buku'] ?? null;
            if (! $kodeBuku) {
                continue;
            }

            $kategori = $book['kategori'];
            if (! $kategori) {
                $upper = strtoupper($book['judul'] ?? '');
                $kategori = collect($agamaKeywords)->first(fn ($kw) => str_contains($upper, $kw))
                    ? 'agama'
                    : 'pelajaran';
            }

            $tanggalPenerimaan = null;
            if (! empty($book['tanggal_penerimaan'])) {
                try {
                    $tanggalPenerimaan = Carbon::createFromFormat('d/m/Y', $book['tanggal_penerimaan'])->format('Y-m-d');
                } catch (\Exception $e) {
                    $tanggalPenerimaan = $book['tanggal_penerimaan'];
                }
            }

            Buku::firstOrCreate(
                ['isbn_key' => 'kode_'.$kodeBuku],
                [
                    'sekolah_id'            => $book['sekolah_id'] ?? null,
                    'isbn'                  => $book['isbn'],
                    'kode_buku'             => $kodeBuku,
                    'judul'                 => $book['judul'],
                    'pengarang'             => $book['pengarang'] ?? null,
                    'penerbit'              => $book['penerbit'] ?? null,
                    'kategori'              => $kategori,
                    'tahun_terbit'          => $book['tahun_terbit'],
                    'cetak_ke'              => $book['cetak_ke'] ?? 1,
                    'jumlah'                => $book['jumlah'] ?? 1,
                    'keadaan_baik'          => $book['keadaan_baik'] ?? 0,
                    'keadaan_rusak_ringan'  => $book['keadaan_rusak_ringan'] ?? 0,
                    'keadaan_rusak_berat'   => $book['keadaan_rusak_berat'] ?? 0,
                    'tersedia'              => $book['tersedia'] ?? 0,
                    'nilai_rata'            => $book['nilai_rata'],
                    'tanggal_penerimaan'    => $tanggalPenerimaan,
                    'sumber_dana'           => $book['sumber_dana'] ?? null,
                    'keterangan'            => $book['keterangan'] ?? null,
                ]
            );
        }

        // ─── Global book (cross-school) ───────────────────────────────────
        $globalBook = Buku::firstOrCreate(
            ['isbn_key' => '978-GLOBAL'],
            [
                'sekolah_id' => null,
                'isbn' => '978-GLOBAL',
                'judul' => 'Pedoman Pengelolaan Perpustakaan Masjid',
                'pengarang' => 'Direktorat URAIS',
                'penerbit' => 'Kemenag RI',
                'kategori' => 'agama',
                'tahun_terbit' => 2023,
                'cetak_ke' => 3,
                'jumlah' => 10,
                'keadaan_baik' => 5,
                'keadaan_rusak_ringan' => 3,
                'keadaan_rusak_berat' => 2,
                'tersedia' => 5,
                'nilai_rata' => 4.5,
                'tanggal_penerimaan' => '2024-01-10',
                'sumber_dana' => 'Bantuan Kemenag RI',
            ]
        );

        // ─── Dummy loan data per school ──────────────────────────────────
        $allBuku = Buku::whereNotNull('kode_buku')->get();

        foreach (Sekolah::query()->orderBy('id')->get() as $sekolah) {
            $siswaSample = Siswa::query()
                ->where('sekolah_id', $sekolah->id)
                ->orderBy('id')
                ->limit(2)
                ->get();

            if ($siswaSample->isEmpty()) {
                continue;
            }

            $first  = $siswaSample->first();
            $second = $siswaSample->get(1) ?? $first;

            $schoolBooks = $allBuku->where('sekolah_id', $sekolah->id)->take(5);

            foreach ($schoolBooks as $bookIndex => $buku) {
                // Active loan — 1st student
                $parent1 = Peminjaman::create([
                    'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
                    'siswa_id'      => $first->id,
                    'loan_date'     => now()->subDays(3 + $bookIndex),
                    'due_date'      => now()->addDays(7),
                ]);
                PeminjamanBuku::create([
                    'peminjaman_id' => $parent1->id,
                    'buku_id'       => $buku->id,
                    'qty'           => 1,
                    'status'        => 'dipinjam',
                ]);
                $buku->decrement('tersedia');

                // Returned loan — 2nd student
                $parent2 = Peminjaman::create([
                    'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
                    'siswa_id'      => $second->id,
                    'loan_date'     => now()->subDays(14),
                    'due_date'      => now()->subDays(7),
                ]);
                PeminjamanBuku::create([
                    'peminjaman_id' => $parent2->id,
                    'buku_id'       => $buku->id,
                    'qty'           => 1,
                    'return_date'   => now()->subDays(5),
                    'status'        => 'dikembalikan',
                    'fine_amount'   => 4000,
                ]);

                UlasanBuku::create([
                    'buku_id'  => $buku->id,
                    'siswa_id' => $first->id,
                    'rating'   => rand(3, 5),
                    'review'   => 'Buku sangat membantu untuk belajar.',
                ]);
            }

            // Global book loan (qty=2 to demo multi-copy) — only on first school
            if ($sekolah->is(Sekolah::orderBy('id')->first())) {
                $this->borrowIfAvailable($globalBook, 2, $first->id);
            }

            // ─── Guru loan ────────────────────────────────────────────────
            $guru = Guru::query()
                ->where('sekolah_id', $sekolah->id)
                ->inRandomOrder()
                ->first();

            if ($guru) {
                $guruBuku = $schoolBooks->first();
                if ($guruBuku) {
                    $this->borrowIfAvailable($guruBuku, 1, null, [
                        'borrower_type' => PeminjamanBuku::BORROWER_GURU,
                        'guru_id'       => $guru->id,
                        'loan_date'     => now()->subDays(5),
                        'due_date'      => now()->addDays(9),
                    ]);
                }
            }

            // ─── Tamu (guest) loan ────────────────────────────────────────
            $tamuBuku = $schoolBooks->skip(1)->first() ?? $schoolBooks->first();
            if ($tamuBuku) {
                $this->borrowIfAvailable($tamuBuku, 1, null, [
                    'borrower_type' => PeminjamanBuku::BORROWER_TAMU,
                    'tamu_nama'     => 'Ahmad Tamu',
                    'tamu_asal'     => 'Perpustakaan Umum Kota',
                    'tamu_telepon'  => '62811223344',
                    'loan_date'     => now()->subDays(1),
                    'due_date'      => now()->addDays(6),
                ]);
            }

            // ─── Multi-books loan (2 different books in one transaction) ──
            $multiBooks = $schoolBooks->skip(2)->take(2);
            if ($multiBooks->count() >= 2) {
                $parentMulti = Peminjaman::create([
                    'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
                    'siswa_id'      => $second->id,
                    'loan_date'     => now()->subDays(4),
                    'due_date'      => now()->addDays(10),
                    'catatan_pinjam'=> 'Peminjaman 2 buku sekaligus untuk tugas',
                ]);
                foreach ($multiBooks as $mb) {
                    if ($mb->tersedia >= 1) {
                        PeminjamanBuku::create([
                            'peminjaman_id' => $parentMulti->id,
                            'buku_id'       => $mb->id,
                            'qty'           => 1,
                            'status'        => 'dipinjam',
                        ]);
                        $mb->decrement('tersedia');
                    }
                }
            }
        }
    }

    /**
     * Create a single-book loan only if buku has enough stock.
     * When $siswaId is provided, borrower_type = siswa.
     */
    private function borrowIfAvailable(Buku $buku, int $qty, ?int $siswaId = null, array $overrides = []): void
    {
        if ($buku->tersedia < $qty) {
            return;
        }

        $parentData = array_merge([
            'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
            'siswa_id'      => $siswaId,
            'loan_date'     => now()->subDays(2),
            'due_date'      => now()->addDays(5),
        ], $overrides);

        $parent = Peminjaman::create($parentData);

        PeminjamanBuku::create([
            'peminjaman_id' => $parent->id,
            'buku_id'       => $buku->id,
            'qty'           => $qty,
            'status'        => 'dipinjam',
        ]);

        $buku->decrement('tersedia', $qty);
    }
}
