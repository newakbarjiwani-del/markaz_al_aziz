<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Guru;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Models\Scopes\OperatorSekolahScope;
use App\Models\Siswa;
use App\Support\GuruSekolahFilter;
use App\Support\LibrarySettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryLoanService
{
    /**
     * @param  array{
     *     borrower_type: string,
     *     siswa_id?: int|null,
     *     guru_id?: int|null,
     *     tamu_nama?: string|null,
     *     tamu_asal?: string|null,
     *     tamu_telepon?: string|null
     * }  $borrower
     * @param  array{
     *     loan_date?: string|null,
     *     due_date?: string|null,
     *     catatan_pinjam?: string|null,
     *     processed_by_user_id?: int|null
     * }  $options
     */
    public function borrow(array $borrower, int $bukuId, ?int $sekolahScope = null, array $options = []): PeminjamanBuku
    {
        return $this->borrowMany($borrower, [$bukuId], $sekolahScope, $options)->firstOrFail();
    }

    /**
     * @param  array{
     *     borrower_type: string,
     *     siswa_id?: int|null,
     *     guru_id?: int|null,
     *     tamu_nama?: string|null,
     *     tamu_asal?: string|null,
     *     tamu_telepon?: string|null
     * }  $borrower
     * @param  array<int, int|string>  $bukuIds  IDs may be duplicated to indicate quantity
     * @param  array{
     *     loan_date?: string|null,
     *     due_date?: string|null,
     *     catatan_pinjam?: string|null,
     *     processed_by_user_id?: int|null
     * }  $options
     * @return Collection<int, PeminjamanBuku>
     */
    public function borrowMany(array $borrower, array $bukuIds, ?int $sekolahScope = null, array $options = []): Collection
    {
        $bukuIds = collect($bukuIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($bukuIds === []) {
            $this->fail('buku_ids', 'Pilih minimal satu buku.');
        }

        // Deduplicate buku_ids: count occurrences to build qty per unique book.
        // This keeps backward compatibility (JS sends buku_ids[] with duplicates for qty)
        // while storing qty in a single row.
        $bukuEntries = collect($bukuIds)
            ->countBy()
            ->map(fn (int $count, int $bukuId) => ['id' => $bukuId, 'qty' => $count])
            ->values()
            ->all();

        return DB::transaction(function () use ($borrower, $bukuEntries, $sekolahScope, $options) {
            $type = (string) ($borrower['borrower_type'] ?? '');
            if (! in_array($type, [
                PeminjamanBuku::BORROWER_SISWA,
                PeminjamanBuku::BORROWER_GURU,
                PeminjamanBuku::BORROWER_TAMU,
            ], true)) {
                $this->fail('borrower_type', 'Tipe peminjam tidak valid.');
            }

            $loanDate = filled($options['loan_date'] ?? null)
                ? Carbon::parse($options['loan_date'])->toDateString()
                : now()->toDateString();

            $dueDate = filled($options['due_date'] ?? null)
                ? Carbon::parse($options['due_date'])->toDateString()
                : Carbon::parse($loanDate)->addDays(LibrarySettings::loanDays())->toDateString();

            if (Carbon::parse($dueDate)->lt(Carbon::parse($loanDate))) {
                $this->fail('due_date', 'Jatuh tempo tidak boleh sebelum tanggal pinjam.');
            }

            $catatan     = filled($options['catatan_pinjam'] ?? null) ? trim((string) $options['catatan_pinjam']) : null;
            $processedBy = $options['processed_by_user_id'] ?? auth()->id();

            $firstEntry  = $bukuEntries[0];
            $firstBuku   = Buku::query()->lockForUpdate()->find($firstEntry['id']);
            if (! $firstBuku) {
                $this->fail('buku_ids', 'Buku tidak ditemukan.');
            }

            $borrowerPayload = match ($type) {
                PeminjamanBuku::BORROWER_SISWA => $this->prepareSiswaBorrower($borrower, $firstBuku, $sekolahScope),
                PeminjamanBuku::BORROWER_GURU  => $this->prepareGuruBorrower($borrower, $firstBuku, $sekolahScope),
                default                        => $this->prepareTamuBorrower($borrower, $firstBuku, $sekolahScope),
            };

            // Check quota: sum of qty, not count of rows.
            $totalRequested = collect($bukuEntries)->sum('qty');
            $activeLoans    = (int) $this->activeLoanQueryFor($type, $borrowerPayload)->sum('qty');
            $maxBooks       = LibrarySettings::maxBooks();
            $remaining      = max(0, $maxBooks - $activeLoans);

            if ($remaining <= 0) {
                $this->fail(
                    $this->borrowerFieldForType($type),
                    'Peminjam sudah meminjam maksimal '.$maxBooks.' buku.'
                );
            }

            if ($totalRequested > $remaining) {
                $this->fail(
                    'buku_ids',
                    'Kuota tidak cukup. Sisa kuota '.$remaining.' buku, diminta '.$totalRequested.' buku.'
                );
            }

            $uniqueBukuIds = array_map(fn ($e) => $e['id'], $bukuEntries);
            $alreadyBorrowed = $this->activeLoanQueryFor($type, $borrowerPayload)
                ->whereHas('buku', fn (Builder $q) => $q->whereIn('id', $uniqueBukuIds))
                ->pluck('peminjaman_buku.buku_id');

            if ($alreadyBorrowed->isNotEmpty()) {
                $this->fail('buku_ids', 'Peminjam masih meminjam salah satu buku yang dipilih.');
            }

            // Create the parent transaction row.
            $peminjamanParent = Peminjaman::create([
                'borrower_type'        => $type,
                'siswa_id'             => $borrowerPayload['siswa_id'] ?? null,
                'guru_id'              => $borrowerPayload['guru_id'] ?? null,
                'tamu_nama'            => $borrowerPayload['tamu_nama'] ?? null,
                'tamu_asal'            => $borrowerPayload['tamu_asal'] ?? null,
                'tamu_telepon'         => $borrowerPayload['tamu_telepon'] ?? null,
                'loan_date'            => $loanDate,
                'due_date'             => $dueDate,
                'catatan_pinjam'       => $catatan,
                'processed_by_user_id' => $processedBy,
            ]);

            // Create one child row per unique book, with qty.
            $loans = collect();
            $loadedBuku = [];

            foreach ($bukuEntries as $bukuEntry) {
                $bukuId = $bukuEntry['id'];
                $qty    = $bukuEntry['qty'];

                $buku = $loadedBuku[$bukuId] ?? Buku::query()->lockForUpdate()->find($bukuId);

                if (! $buku) {
                    $this->fail('buku_ids', 'Buku tidak ditemukan.');
                }

                if ($buku->tersedia < $qty) {
                    $this->fail('buku_ids', 'Stok buku "'.$buku->judul.'" tidak mencukupi. Tersedia '.$buku->tersedia.', diminta '.$qty.'.');
                }

                $loadedBuku[$bukuId] = $buku;

                // Re-validate school access for each book.
                match ($type) {
                    PeminjamanBuku::BORROWER_SISWA => $this->prepareSiswaBorrower($borrower, $buku, $sekolahScope),
                    PeminjamanBuku::BORROWER_GURU  => $this->prepareGuruBorrower($borrower, $buku, $sekolahScope),
                    default                        => $this->prepareTamuBorrower($borrower, $buku, $sekolahScope),
                };

                $item = PeminjamanBuku::create([
                    'peminjaman_id' => $peminjamanParent->id,
                    'buku_id'       => $buku->id,
                    'qty'           => $qty,
                    'status'        => PeminjamanBuku::STATUS_DIPINJAM,
                    'fine_amount'   => 0,
                ]);

                $buku->decrement('tersedia', $qty);

                $item->setRelation('peminjaman', $peminjamanParent);
                $item->load(['buku', 'processedBy']);
                $peminjamanParent->loadMissing(['siswa.kelas', 'guru']);

                $loans->push($item);
            }

            return $loans;
        });
    }

    /**
     * @param  array{
     *     return_date?: string|null,
     *     kondisi_kembali?: string|null,
     *     catatan_kembali?: string|null,
     *     processed_by_user_id?: int|null
     * }  $options
     */
    public function return(int $peminjamanId, ?int $sekolahScope = null, array $options = []): PeminjamanBuku
    {
        return DB::transaction(function () use ($peminjamanId, $sekolahScope, $options) {
            $loan = PeminjamanBuku::query()
                ->with(['peminjaman.siswa', 'peminjaman.guru', 'buku'])
                ->lockForUpdate()
                ->find($peminjamanId);

            if (! $loan) {
                $this->fail('peminjaman_id', 'Peminjaman tidak ditemukan.');
            }

            if ($loan->status !== PeminjamanBuku::STATUS_DIPINJAM) {
                $this->fail('peminjaman_id', 'Buku sudah dikembalikan.');
            }

            $this->assertLoanInScope($loan, $sekolahScope);

            $kondisi = $options['kondisi_kembali'] ?? PeminjamanBuku::KONDISI_BAIK;
            if (! array_key_exists($kondisi, PeminjamanBuku::kondisiOptions())) {
                $this->fail('kondisi_kembali', 'Kondisi buku tidak valid.');
            }

            $returnDate = filled($options['return_date'] ?? null)
                ? Carbon::parse($options['return_date'])->startOfDay()
                : now()->startOfDay();

            if ($loan->loan_date && $returnDate->lt($loan->loan_date)) {
                $this->fail('return_date', 'Tanggal kembali tidak boleh sebelum tanggal pinjam.');
            }

            $fineBreakdown = $loan->calculateFine($returnDate, $kondisi);
            $status        = $kondisi === PeminjamanBuku::KONDISI_HILANG
                ? PeminjamanBuku::STATUS_HILANG
                : PeminjamanBuku::STATUS_DIKEMBALIKAN;

            $loan->update([
                'return_date'          => $returnDate->toDateString(),
                'status'               => $status,
                'fine_amount'          => $fineBreakdown['total_fine'],
                'late_days'            => $fineBreakdown['late_days'],
                'kondisi_kembali'      => $kondisi,
                'catatan_kembali'      => filled($options['catatan_kembali'] ?? null) ? trim((string) $options['catatan_kembali']) : null,
                'processed_by_user_id' => $options['processed_by_user_id'] ?? auth()->id(),
            ]);

            if ($loan->buku && $kondisi !== PeminjamanBuku::KONDISI_HILANG) {
                $loan->buku->increment('tersedia', $loan->qty ?: 1);
            }

            return $loan->fresh(['peminjaman.siswa.kelas', 'peminjaman.guru', 'buku', 'processedBy']);
        });
    }

    /**
     * Return ALL active items in a Peminjaman (parent) transaction at once.
     *
     * @param  array{
     *     return_date?: string|null,
     *     kondisi_kembali?: string|null,
     *     catatan_kembali?: string|null,
     *     processed_by_user_id?: int|null
     * }  $options
     * @return Collection<int, PeminjamanBuku>
     */
    public function returnTransaction(int $peminjamanId, ?int $sekolahScope = null, array $options = []): Collection
    {
        return DB::transaction(function () use ($peminjamanId, $sekolahScope, $options) {
            $parent = Peminjaman::query()
                ->with(['items.buku', 'items.peminjaman.siswa', 'items.peminjaman.guru'])
                ->lockForUpdate()
                ->find($peminjamanId);

            if (! $parent) {
                $this->fail('peminjaman_id', 'Transaksi peminjaman tidak ditemukan.');
            }

            $activeItems = $parent->items->where('status', PeminjamanBuku::STATUS_DIPINJAM);
            if ($activeItems->isEmpty()) {
                $this->fail('peminjaman_id', 'Tidak ada buku aktif yang perlu dikembalikan.');
            }

            $kondisi = $options['kondisi_kembali'] ?? PeminjamanBuku::KONDISI_BAIK;
            if (! array_key_exists($kondisi, PeminjamanBuku::kondisiOptions())) {
                $this->fail('kondisi_kembali', 'Kondisi buku tidak valid.');
            }

            $returnDate = filled($options['return_date'] ?? null)
                ? Carbon::parse($options['return_date'])->startOfDay()
                : now()->startOfDay();

            $updated = collect();

            foreach ($activeItems as $item) {
                $this->assertLoanInScope($item, $sekolahScope);

                if ($item->loan_date && $returnDate->lt($item->loan_date)) {
                    $this->fail('return_date', 'Tanggal kembali tidak boleh sebelum tanggal pinjam.');
                }

                $fineBreakdown = $item->calculateFine($returnDate, $kondisi);
                $status        = $kondisi === PeminjamanBuku::KONDISI_HILANG
                    ? PeminjamanBuku::STATUS_HILANG
                    : PeminjamanBuku::STATUS_DIKEMBALIKAN;

                $item->update([
                    'return_date'          => $returnDate->toDateString(),
                    'status'               => $status,
                    'fine_amount'          => $fineBreakdown['total_fine'],
                    'late_days'            => $fineBreakdown['late_days'],
                    'kondisi_kembali'      => $kondisi,
                    'catatan_kembali'      => filled($options['catatan_kembali'] ?? null) ? trim((string) $options['catatan_kembali']) : null,
                    'processed_by_user_id' => $options['processed_by_user_id'] ?? auth()->id(),
                ]);

                if ($item->buku && $kondisi !== PeminjamanBuku::KONDISI_HILANG) {
                    $item->buku->increment('tersedia', $item->qty ?: 1);
                }

                $updated->push($item->fresh(['buku', 'peminjaman.siswa.kelas', 'peminjaman.guru', 'processedBy']));
            }

            return $updated;
        });
    }

    public function extend(int $peminjamanId, ?int $sekolahScope = null, array $options = []): PeminjamanBuku
    {
        return DB::transaction(function () use ($peminjamanId, $sekolahScope, $options) {
            $loan = PeminjamanBuku::query()
                ->with(['peminjaman.siswa', 'peminjaman.guru', 'buku'])
                ->lockForUpdate()
                ->find($peminjamanId);

            if (! $loan) {
                $this->fail('peminjaman_id', 'Peminjaman tidak ditemukan.');
            }

            $this->assertLoanInScope($loan, $sekolahScope);

            if (! $loan->canExtend()) {
                $this->fail('peminjaman_id', 'Peminjaman tidak dapat diperpanjang.');
            }

            if (! filled($options['due_date'] ?? null)) {
                $this->fail('due_date', 'Jatuh tempo baru wajib diisi.');
            }

            $newDueDate = Carbon::parse($options['due_date'])->toDateString();

            if (Carbon::parse($newDueDate)->lte($loan->due_date)) {
                $this->fail('due_date', 'Jatuh tempo baru harus setelah jatuh tempo saat ini.');
            }

            // Update due_date on the parent Peminjaman and increment perpanjangan_count on the item.
            $loan->peminjaman->update(['due_date' => $newDueDate]);

            $loan->update([
                'perpanjangan_count'   => $loan->perpanjangan_count + 1,
                'processed_by_user_id' => auth()->id(),
            ]);

            return $loan->fresh(['peminjaman.siswa.kelas', 'peminjaman.guru', 'buku', 'processedBy']);
        });
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $borrower
     * @return array{siswa_id: int, guru_id: null, tamu_nama: null, tamu_asal: null, tamu_telepon: null}
     */
    private function prepareSiswaBorrower(array $borrower, Buku $buku, ?int $sekolahScope): array
    {
        $siswaId = (int) ($borrower['siswa_id'] ?? 0);
        $siswa   = Siswa::query()->withoutGlobalScope(OperatorSekolahScope::class)->find($siswaId);
        if (! $siswa) {
            $this->fail('siswa_id', 'Siswa tidak ditemukan.');
        }

        if (! $siswa->canTransact()) {
            $this->fail('siswa_id', 'Siswa tidak aktif.');
        }

        // Shared library allows cross-school access.

        return [
            'siswa_id'     => $siswa->id,
            'guru_id'      => null,
            'tamu_nama'    => null,
            'tamu_asal'    => null,
            'tamu_telepon' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $borrower
     * @return array{siswa_id: null, guru_id: int, tamu_nama: null, tamu_asal: null, tamu_telepon: null}
     */
    private function prepareGuruBorrower(array $borrower, Buku $buku, ?int $sekolahScope): array
    {
        $guruId    = (int) ($borrower['guru_id'] ?? 0);
        $guruQuery = Guru::query()->withoutGlobalScope(OperatorSekolahScope::class)->where('status', 'aktif');
        if ($sekolahScope !== null) {
            GuruSekolahFilter::applyAccessibleScope($guruQuery, $sekolahScope);
        }

        $guru = $guruQuery->find($guruId);
        if (! $guru) {
            $this->fail('guru_id', 'Guru tidak ditemukan atau tidak aktif.');
        }

        $this->assertBookAccessibleForEntity($buku, $guru->sekolah_id, $sekolahScope);

        return [
            'siswa_id'     => null,
            'guru_id'      => $guru->id,
            'tamu_nama'    => null,
            'tamu_asal'    => null,
            'tamu_telepon' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $borrower
     * @return array{siswa_id: null, guru_id: null, tamu_nama: string, tamu_asal: ?string, tamu_telepon: ?string}
     */
    private function prepareTamuBorrower(array $borrower, Buku $buku, ?int $sekolahScope): array
    {
        $nama = trim((string) ($borrower['tamu_nama'] ?? ''));
        if ($nama === '') {
            $this->fail('tamu_nama', 'Nama tamu wajib diisi.');
        }

        // Shared library allows cross-school access.

        $asal    = filled($borrower['tamu_asal'] ?? null) ? trim((string) $borrower['tamu_asal']) : null;
        $telepon = filled($borrower['tamu_telepon'] ?? null) ? trim((string) $borrower['tamu_telepon']) : null;

        return [
            'siswa_id'     => null,
            'guru_id'      => null,
            'tamu_nama'    => $nama,
            'tamu_asal'    => $asal,
            'tamu_telepon' => $telepon,
        ];
    }

    /**
     * Query active PeminjamanBuku for a given borrower via the peminjaman parent.
     *
     * @param  array<string, mixed>  $payload
     */
    private function activeLoanQueryFor(string $type, array $payload): Builder
    {
        $query = PeminjamanBuku::query()
            ->where('status', PeminjamanBuku::STATUS_DIPINJAM)
            ->whereHas('peminjaman', function (Builder $q) use ($type, $payload) {
                $q->where('borrower_type', $type);

                match ($type) {
                    PeminjamanBuku::BORROWER_SISWA => $q->where('siswa_id', $payload['siswa_id']),
                    PeminjamanBuku::BORROWER_GURU  => $q->where('guru_id', $payload['guru_id']),
                    default                        => $q
                        ->where('tamu_nama', $payload['tamu_nama'])
                        ->when(
                            filled($payload['tamu_telepon'] ?? null),
                            fn (Builder $inner) => $inner->where('tamu_telepon', $payload['tamu_telepon']),
                            fn (Builder $inner) => $inner->where(function (Builder $deep) {
                                $deep->whereNull('tamu_telepon')->orWhere('tamu_telepon', '');
                            })
                        ),
                };
            });

        return $query;
    }

    private function assertBookAccessibleForEntity(
        Buku $buku,
        ?int $entitySekolahId,
        ?int $sekolahScope,
    ): void {
        // Shared library allows cross-school access.
    }

    private function assertLoanInScope(PeminjamanBuku $loan, ?int $sekolahScope): void
    {
        if ($sekolahScope === null) {
            return;
        }

        $parent  = $loan->peminjaman;
        $inScope = match ($parent?->borrower_type) {
            PeminjamanBuku::BORROWER_SISWA => $parent->siswa?->sekolah_id === $sekolahScope,
            PeminjamanBuku::BORROWER_GURU  => $parent->guru !== null
                && ($parent->guru->sekolah_id === null || $parent->guru->sekolah_id === $sekolahScope),
            PeminjamanBuku::BORROWER_TAMU  => $loan->buku?->sekolah_id === null
                || $loan->buku?->sekolah_id === $sekolahScope,
            default => false,
        };

        if (! $inScope) {
            $this->fail('peminjaman_id', 'Peminjaman tidak ditemukan.');
        }
    }

    private function borrowerFieldForType(string $type): string
    {
        return match ($type) {
            PeminjamanBuku::BORROWER_GURU => 'guru_id',
            PeminjamanBuku::BORROWER_TAMU => 'tamu_nama',
            default                       => 'siswa_id',
        };
    }

    /**
     * @return never
     */
    private function fail(string $field, string $message): void
    {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}
