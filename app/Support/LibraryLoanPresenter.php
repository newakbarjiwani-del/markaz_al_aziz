<?php

namespace App\Support;

use App\Models\Guru;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Models\Siswa;
use Carbon\CarbonInterface;

final class LibraryLoanPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function loan(
        PeminjamanBuku $loan,
        ?string $returnPreviewKondisi = null,
        ?CarbonInterface $returnPreviewDate = null,
    ): array {
        $loan->loadMissing(['peminjaman.siswa.kelas', 'peminjaman.guru', 'peminjaman.processedBy', 'buku']);

        $kondisi = $returnPreviewKondisi ?? PeminjamanBuku::KONDISI_BAIK;
        $finePreview = $loan->status === PeminjamanBuku::STATUS_DIPINJAM
            ? $loan->calculateFine($returnPreviewDate, $kondisi)
            : [
                'late_days' => (int) $loan->late_days,
                'late_fine' => max(0, (int) $loan->late_days * LibrarySettings::finePerDay()),
                'damage_fine' => max(0, (int) $loan->fine_amount - max(0, (int) $loan->late_days * LibrarySettings::finePerDay())),
                'total_fine' => (int) $loan->fine_amount,
            ];

        return [
            'id' => $loan->id,
            'peminjaman_id' => $loan->peminjaman_id,
            'borrower_type' => $loan->borrower_type,
            'borrower_type_label' => $loan->borrowerTypeLabel(),
            'qty' => max(1, (int) $loan->qty),
            'qty_label' => ((int) $loan->qty > 1 ? $loan->qty.'x' : ''),
            'borrower_name' => $loan->borrowerName(),
            'borrower_identifier' => $loan->borrowerIdentifier(),
            'borrower_meta' => $loan->borrowerMeta(),
            'borrower_label' => $loan->borrowerDisplayLabel(),
            'status' => $loan->status,
            'status_label' => $loan->statusLabel(),
            'loan_date' => $loan->loan_date?->format('Y-m-d'),
            'due_date' => $loan->due_date?->format('Y-m-d'),
            'return_date' => $loan->return_date?->format('Y-m-d'),
            'days_late' => $loan->daysLate(),
            'days_remaining' => $loan->daysRemaining(),
            'is_overdue' => $loan->isOverdue(),
            'perpanjangan_count' => $loan->perpanjangan_count,
            'can_extend' => $loan->canExtend(),
            'catatan_pinjam' => $loan->peminjaman?->catatan_pinjam,
            'catatan_kembali' => $loan->catatan_kembali,
            'kondisi_kembali' => $loan->kondisi_kembali,
            'kondisi_kembali_label' => $loan->kondisiKembaliLabel(),
            'fine_amount' => (float) $loan->fine_amount,
            'fine_amount_label' => self::rupiah((float) $loan->fine_amount),
            'fine_preview' => $finePreview,
            'fine_preview_label' => self::rupiah($finePreview['total_fine']),
            'fine_lines' => self::fineLines($finePreview, $kondisi),
            'siswa' => self::siswa($loan->peminjaman?->siswa),
            'guru' => self::guru($loan->peminjaman?->guru),
            'tamu' => $loan->borrower_type === PeminjamanBuku::BORROWER_TAMU ? [
                'nama' => $loan->peminjaman?->tamu_nama,
                'asal' => $loan->peminjaman?->tamu_asal,
                'telepon' => $loan->peminjaman?->tamu_telepon,
            ] : null,
            'buku' => self::buku($loan->buku),
            'processed_by' => $loan->peminjaman?->processedBy?->name,
        ];
    }

    /**
     * Present a full Peminjaman (parent) transaction for the detail modal.
     *
     * @return array<string, mixed>
     */
    public static function transaction(Peminjaman $peminjaman): array
    {
        $peminjaman->loadMissing(['items.buku', 'siswa.kelas', 'guru']);

        $books = $peminjaman->items->map(function (PeminjamanBuku $item) {
            $fine = $item->status === PeminjamanBuku::STATUS_DIPINJAM
                ? $item->calculateFine()
                : [
                    'late_days' => (int) $item->late_days,
                    'late_fine' => (int) $item->late_days * LibrarySettings::finePerDay(),
                    'damage_fine' => max(0, (int) $item->fine_amount - (int) $item->late_days * LibrarySettings::finePerDay()),
                    'total_fine' => (int) $item->fine_amount,
                ];

            return [
                'id' => $item->id,
                'qty' => max(1, (int) $item->qty),
                'status' => $item->status,
                'status_label' => $item->statusLabel(),
                'return_date' => $item->return_date?->format('Y-m-d'),
                'late_days' => $item->daysLate(),
                'is_overdue' => $item->isOverdue(),
                'fine_amount' => (float) $item->fine_amount,
                'fine_preview' => $fine,
                'fine_preview_label' => self::rupiah($fine['total_fine']),
                'fine_lines' => self::fineLines($fine, $item->kondisi_kembali ?? PeminjamanBuku::KONDISI_BAIK),
                'catatan_kembali' => $item->catatan_kembali,
                'kondisi_kembali' => $item->kondisi_kembali,
                'kondisi_kembali_label' => $item->kondisiKembaliLabel(),
                'perpanjangan_count' => $item->perpanjangan_count,
                'can_extend' => $item->canExtend(),
                'buku' => self::buku($item->buku),
            ];
        })->values()->all();

        $totalQty = (int) $peminjaman->items->sum('qty');
        $totalFine = (float) $peminjaman->items->sum('fine_amount');

        return [
            'id' => $peminjaman->id,
            'borrower_type' => $peminjaman->borrower_type,
            'borrower_type_label' => $peminjaman->borrowerTypeLabel(),
            'borrower_name' => $peminjaman->borrowerName(),
            'borrower_identifier' => $peminjaman->borrowerIdentifier(),
            'borrower_meta' => $peminjaman->borrowerMeta(),
            'borrower_label' => $peminjaman->borrowerDisplayLabel(),
            'status' => $peminjaman->latestStatus(),
            'status_label' => ucfirst($peminjaman->latestStatus()),
            'loan_date' => $peminjaman->loan_date?->format('Y-m-d'),
            'due_date' => $peminjaman->due_date?->format('Y-m-d'),
            'catatan_pinjam' => $peminjaman->catatan_pinjam,
            'total_books' => $totalQty,
            'total_fine' => $totalFine,
            'total_fine_label' => self::rupiah($totalFine),
            'books' => $books,
            'siswa' => self::siswa($peminjaman->siswa),
            'guru' => self::guru($peminjaman->guru),
            'tamu' => $peminjaman->borrower_type === PeminjamanBuku::BORROWER_TAMU ? [
                'nama' => $peminjaman->tamu_nama,
                'asal' => $peminjaman->tamu_asal,
                'telepon' => $peminjaman->tamu_telepon,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function siswaSummary(Siswa $siswa): array
    {
        $siswa->loadMissing('kelas:id,name');

        return self::borrowerSummaryPayload(
            type: PeminjamanBuku::BORROWER_SISWA,
            name: $siswa->name,
            meta: $siswa->kelas?->name,
            identifier: $siswa->nis ? 'NIS '.$siswa->nis : null,
            entity: ['siswa' => self::siswa($siswa)],
            activeQuery: PeminjamanBuku::query()
                ->whereHas('peminjaman', fn ($q) => $q
                    ->where('borrower_type', PeminjamanBuku::BORROWER_SISWA)
                    ->where('siswa_id', $siswa->id)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function guruSummary(Guru $guru): array
    {
        return self::borrowerSummaryPayload(
            type: PeminjamanBuku::BORROWER_GURU,
            name: $guru->name,
            meta: $guru->jabatan,
            identifier: $guru->nip ? 'NIP '.$guru->nip : null,
            entity: ['guru' => self::guru($guru)],
            activeQuery: PeminjamanBuku::query()
                ->whereHas('peminjaman', fn ($q) => $q
                    ->where('borrower_type', PeminjamanBuku::BORROWER_GURU)
                    ->where('guru_id', $guru->id)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function tamuSummary(string $nama, ?string $asal = null, ?string $telepon = null): array
    {
        $nama = trim($nama);
        $asal = filled($asal) ? trim($asal) : null;
        $telepon = filled($telepon) ? trim($telepon) : null;

        $activeQuery = PeminjamanBuku::query()
            ->whereHas('peminjaman', function ($q) use ($nama, $telepon) {
                $q->where('borrower_type', PeminjamanBuku::BORROWER_TAMU)
                  ->where('tamu_nama', $nama)
                  ->when(
                      $telepon !== null,
                      fn ($inner) => $inner->where('tamu_telepon', $telepon),
                      fn ($inner) => $inner->where(function ($deep) {
                          $deep->whereNull('tamu_telepon')->orWhere('tamu_telepon', '');
                      })
                  );
            });

        return self::borrowerSummaryPayload(
            type: PeminjamanBuku::BORROWER_TAMU,
            name: $nama !== '' ? $nama : '-',
            meta: $asal,
            identifier: $telepon,
            entity: ['tamu' => [
                'nama' => $nama !== '' ? $nama : null,
                'asal' => $asal,
                'telepon' => $telepon,
            ]],
            activeQuery: $activeQuery,
        );
    }

    /**
     * @param  array<string, mixed>  $entity
     * @return array<string, mixed>
     */
    private static function borrowerSummaryPayload(
        string $type,
        string $name,
        ?string $meta,
        ?string $identifier,
        array $entity,
        $activeQuery,
    ): array {
        $activeLoans = (clone $activeQuery)
            ->with(['peminjaman', 'buku:id,judul,isbn,pengarang'])
            ->where('status', PeminjamanBuku::STATUS_DIPINJAM)
            ->orderByRaw('(SELECT due_date FROM peminjaman WHERE peminjaman.id = peminjaman_buku.peminjaman_id LIMIT 1)')
            ->get();

        $activeCount = (int) $activeLoans->sum('qty');
        $maxBooks = LibrarySettings::maxBooks();

        return array_merge($entity, [
            'borrower_type' => $type,
            'borrower_type_label' => PeminjamanBuku::borrowerTypeOptions()[$type] ?? $type,
            'name' => $name,
            'meta' => $meta,
            'identifier' => $identifier,
            'active_count' => $activeCount,
            'max_books' => $maxBooks,
            'remaining_quota' => max(0, $maxBooks - $activeCount),
            'can_borrow' => $activeCount < $maxBooks,
            'overdue_count' => (int) $activeLoans->filter(fn (PeminjamanBuku $loan) => $loan->isOverdue())->sum('qty'),
            'active_loans' => $activeLoans->map(fn (PeminjamanBuku $loan) => [
                'id' => $loan->id,
                'qty' => max(1, (int) $loan->qty),
                'buku_judul' => $loan->buku?->judul,
                'due_date' => $loan->due_date?->format('Y-m-d'),
                'is_overdue' => $loan->isOverdue(),
                'days_late' => $loan->daysLate(),
                'days_remaining' => $loan->daysRemaining(),
            ])->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function siswa(?Siswa $siswa): ?array
    {
        if (! $siswa) {
            return null;
        }

        $siswa->loadMissing('kelas:id,name');

        return [
            'id' => $siswa->id,
            'nis' => $siswa->nis,
            'name' => $siswa->name,
            'kelas' => $siswa->kelas?->name,
            'status' => $siswa->statusLabel(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function guru(?Guru $guru): ?array
    {
        if (! $guru) {
            return null;
        }

        return [
            'id' => $guru->id,
            'nip' => $guru->nip,
            'name' => $guru->name,
            'jabatan' => $guru->jabatan,
            'status' => $guru->status,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function buku(?\App\Models\Buku $buku): ?array
    {
        if (! $buku) {
            return null;
        }

        return [
            'id' => $buku->id,
            'judul' => $buku->judul,
            'pengarang' => $buku->pengarang,
            'penerbit' => $buku->penerbit,
            'kode_buku' => $buku->kode_buku,
            'isbn' => $buku->isbn,
            'kategori' => $buku->kategori,
            'tersedia' => $buku->tersedia,
            'jumlah' => $buku->jumlah,
        ];
    }

    public static function rupiah(float|int $amount): string
    {
        if ($amount <= 0) {
            return 'Rp 0';
        }

        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    /**
     * @param  array{late_days: int, late_fine: int, damage_fine: int, total_fine: int}  $finePreview
     * @return array<int, array{label: string, amount: int, amount_label: string}>
     */
    private static function fineLines(array $finePreview, string $kondisi): array
    {
        $lines = [];

        if (($finePreview['late_fine'] ?? 0) > 0) {
            $lines[] = [
                'label' => 'Denda keterlambatan ('.$finePreview['late_days'].' hari × '.self::rupiah(LibrarySettings::finePerDay()).')',
                'amount' => (int) $finePreview['late_fine'],
                'amount_label' => self::rupiah($finePreview['late_fine']),
            ];
        }

        if (($finePreview['damage_fine'] ?? 0) > 0) {
            $lines[] = [
                'label' => LibrarySettings::fineLabelForKondisi($kondisi) ?? 'Denda kondisi buku',
                'amount' => (int) $finePreview['damage_fine'],
                'amount_label' => self::rupiah($finePreview['damage_fine']),
            ];
        }

        return $lines;
    }
}
