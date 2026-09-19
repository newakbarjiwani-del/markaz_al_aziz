<?php

namespace App\Http\Controllers\Admin\Library\Concerns;

use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Support\GuruSekolahFilter;
use App\Support\LibrarySettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait BuildsLibraryLoanQueries
{
    /**
     * Apply GET-parameter filters on a Peminjaman (parent) query.
     */
    protected function applyPeminjamanFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when(
                $request->filled('status'),
                fn (Builder $q) => $q->whereHas('items', fn ($iq) => $iq->where('status', $request->string('status')))
            )
            ->when(
                $request->filled('borrower_type'),
                fn (Builder $q) => $q->where('borrower_type', $request->string('borrower_type'))
            )
            ->when(
                $request->boolean('overdue'),
                fn (Builder $q) => $q
                    ->whereHas('items', fn ($iq) => $iq->where('status', 'dipinjam'))
                    ->whereDate('due_date', '<', now()->toDateString())
            )
            ->when(
                $request->filled('date_from'),
                fn (Builder $q) => $q->whereDate('loan_date', '>=', $request->string('date_from'))
            )
            ->when(
                $request->filled('date_to'),
                fn (Builder $q) => $q->whereDate('loan_date', '<=', $request->string('date_to'))
            )
            ->when(
                $request->filled('due_from'),
                fn (Builder $q) => $q->whereDate('due_date', '>=', $request->string('due_from'))
            )
            ->when(
                $request->filled('due_to'),
                fn (Builder $q) => $q->whereDate('due_date', '<=', $request->string('due_to'))
            )
            ->when(
                $request->filled('kelas_id'),
                fn (Builder $q) => $q->whereHas('siswa', fn ($sq) => $sq->where('kelas_id', $request->integer('kelas_id')))
            );
    }

    /**
     * Scope loans visible to a school-bound perpustakaan operator.
     */
    protected function applyLibraryLoanSchoolScope(Builder $query, ?int $sekolahId): Builder
    {
        if ($sekolahId === null) {
            return $query;
        }

        return $query->where(function (Builder $outer) use ($sekolahId) {
            $outer
                ->where(function (Builder $siswaLoan) use ($sekolahId) {
                    $siswaLoan->where('borrower_type', PeminjamanBuku::BORROWER_SISWA)
                        ->whereHas('siswa', fn (Builder $sq) => $sq->where('sekolah_id', $sekolahId));
                })
                ->orWhere(function (Builder $guruLoan) use ($sekolahId) {
                    $guruLoan->where('borrower_type', PeminjamanBuku::BORROWER_GURU)
                        ->whereHas('guru', fn (Builder $gq) => GuruSekolahFilter::applyAccessibleScope($gq, $sekolahId));
                })
                ->orWhere(function (Builder $tamuLoan) use ($sekolahId) {
                    $tamuLoan->where('borrower_type', PeminjamanBuku::BORROWER_TAMU);
                });
        });
    }

    // ─── Stats ─────────────────────────────────────────────────────────────

    /**
     * @return array{active: int, overdue: int, returned_today: int, fine_estimate: int}
     */
    protected function peminjamanStats(Builder $baseQuery): array
    {
        $activeQuery = (clone $baseQuery)->whereHas('items', fn ($q) => $q->where('status', 'dipinjam'));
        $allActive   = (clone $activeQuery)->with('items')->get();

        $activeQty  = $allActive->sum(fn ($p) => $p->items->where('status', 'dipinjam')->sum('qty'));
        $overdueQty = $allActive->filter(fn ($p) => $p->due_date?->lt(now()->startOfDay()))
            ->sum(fn ($p) => $p->items->where('status', 'dipinjam')->sum('qty'));

        $fineEstimate = $allActive->filter(fn ($p) => $p->due_date?->lt(now()->startOfDay()))
            ->flatMap(fn ($p) => $p->items->where('status', 'dipinjam'))
            ->sum(fn ($item) => $item->calculateFine()['total_fine']);

        $returnedToday = (clone $baseQuery)
            ->whereHas('items', fn ($q) => $q
                ->where('status', 'dikembalikan')
                ->whereDate('return_date', now()->toDateString())
            )->count();

        return [
            'active'         => $activeQty,
            'overdue'        => $overdueQty,
            'returned_today' => $returnedToday,
            'fine_estimate'  => (int) $fineEstimate,
        ];
    }

    /**
     * @return array{waiting: int, overdue: int, due_today: int, fine_estimate: int}
     */
    protected function pengembalianStats(Builder $baseQuery): array
    {
        $activeQuery = (clone $baseQuery)->whereHas('items', fn ($q) => $q->where('status', 'dipinjam'));
        $allActive   = (clone $activeQuery)->with('items')->get();

        $waitingQty  = $allActive->sum(fn ($p) => $p->items->where('status', 'dipinjam')->sum('qty'));
        $overdueQty  = $allActive->filter(fn ($p) => $p->due_date?->lt(now()->startOfDay()))
            ->sum(fn ($p) => $p->items->where('status', 'dipinjam')->sum('qty'));
        $dueTodayQty = $allActive->filter(fn ($p) => $p->due_date?->isToday())
            ->sum(fn ($p) => $p->items->where('status', 'dipinjam')->sum('qty'));

        $fineEstimate = $allActive->filter(fn ($p) => $p->due_date?->lt(now()->startOfDay()))
            ->flatMap(fn ($p) => $p->items->where('status', 'dipinjam'))
            ->sum(fn ($item) => $item->calculateFine()['total_fine']);

        return [
            'waiting'       => $waitingQty,
            'overdue'       => $overdueQty,
            'due_today'     => $dueTodayQty,
            'fine_estimate' => (int) $fineEstimate,
        ];
    }

    // ─── Cell helpers ──────────────────────────────────────────────────────

    protected function loanTimingLabel(Peminjaman $loan): array|string
    {
        $hasActive = $loan->items?->contains(fn ($i) => $i->status === 'dipinjam');
        if (! $hasActive) {
            return '-';
        }

        if ($loan->due_date && $loan->due_date->lt(now()->startOfDay())) {
            return $this->badgeCell('+'.$loan->due_date->diffInDays(now()).' hari', 'badge badge-red');
        }

        if ($loan->due_date && $loan->due_date->isToday()) {
            return $this->badgeCell('Jatuh tempo hari ini', 'badge badge-amber');
        }

        if ($loan->due_date) {
            $remaining = now()->startOfDay()->diffInDays($loan->due_date);

            return $this->badgeCell($remaining.' hari lagi', 'badge badge-green');
        }

        return '-';
    }

    protected function formatRupiah(int|float $amount): array|string
    {
        if ($amount <= 0) {
            return '-';
        }

        return $this->cell('Rp '.number_format((float) $amount, 0, ',', '.'), (int) $amount, 'number');
    }

    /**
     * Render a compact book list for the DataTable "Buku (Jml)" column.
     */
    protected function bookListHtml(Peminjaman $loan): array
    {
        $items = $loan->items ?? collect();
        if ($items->isEmpty()) {
            return $this->cell('-', null, 'text');
        }

        $lines = $items->map(function ($item) {
            $judul    = htmlspecialchars($item->buku?->judul ?? '?', ENT_QUOTES, 'UTF-8');
            $qty      = max(1, (int) $item->qty);
            $status   = $item->statusLabel();
            $qtyLabel = $qty > 1 ? " ({$qty}×)" : '';

            return '<div class="library-book-line">'.$judul.$qtyLabel.' <span class="text-xs text-slate-400">— '.$status.'</span></div>';
        })->implode('');

        return $this->cell('<div class="library-book-list">'.$lines.'</div>', null, 'html');
    }

    /**
     * Build an aggregated status badge for the whole transaction.
     */
    protected function statusBadge(Peminjaman $loan): array
    {
        $items       = $loan->items ?? collect();
        $totalQty    = (int) $items->sum('qty');
        $dipinjamQty = (int) $items->where('status', 'dipinjam')->sum('qty');
        $overdueQty  = (int) $items->where('status', 'dipinjam')
            ->filter(fn ($i) => $i->due_date && $i->due_date->lt(now()->startOfDay()))
            ->sum('qty');
        $returnedQty = (int) $items->where('status', 'dikembalikan')->sum('qty');

        if ($overdueQty > 0) {
            $label = 'Terlambat ('.$overdueQty.'/'.$totalQty.')';
            $class = 'badge badge-red';
        } elseif ($dipinjamQty > 0) {
            $label = 'Dipinjam ('.$dipinjamQty.'/'.$totalQty.')';
            $class = 'badge badge-blue';
        } elseif ($returnedQty === $totalQty) {
            $label = 'Dikembalikan';
            $class = 'badge badge-green';
        } else {
            $label = $items->first()?->status ?? '-';
            $class = 'badge';
        }

        return $this->badgeCell($label, $class);
    }

    /**
     * Build the actions array for the DataTable Aksi column (transaction level).
     *
     * @param  non-empty-string|null  $extendUrl  Route for extend action; defaults to admin route
     * @return array<string, mixed>
     */
    protected function loanActionButtons(Peminjaman $loan, ?string $extendUrl = null): array
    {
        $actions = [];

        // Detail
        $actions['loan_detail'] = [
            'loan_id' => $loan->id,
        ];

        // Perpanjang — only when ALL items can be extended
        $allCanExtend = $loan->items?->every(fn ($i) => $i->canExtend()) ?? false;
        if ($allCanExtend) {
            $actions['loan_extend'] = [
                'loan_id' => $loan->id,
                'url' => $extendUrl ?? route('admin.perpustakaan.peminjaman.extend', $loan->id),
                'label' => $loan->borrowerDisplayLabel(),
                'due_date' => $loan->due_date?->format('Y-m-d') ?? '',
            ];
        }

        // Kembalikan
        $actions['loan_return'] = [
            'loan_id' => $loan->id,
            'label' => $loan->borrowerDisplayLabel(),
            'loan_date' => $loan->loan_date?->format('Y-m-d') ?? '',
            'due_date' => $loan->due_date?->format('Y-m-d') ?? '',
        ];

        return $this->cell('', ['actions' => $actions], 'action');
    }

    protected function loanSettingsPayload(): array
    {
        return [
            'loanDays' => LibrarySettings::loanDays(),
            'maxBooks' => LibrarySettings::maxBooks(),
            'finePerDay' => LibrarySettings::finePerDay(),
            'extensionDays' => LibrarySettings::extensionDays(),
            'maxExtensions' => LibrarySettings::maxExtensions(),
            'fineLostBook' => LibrarySettings::fineLostBook(),
            'fineDamagedBook' => LibrarySettings::fineDamagedBook(),
        ];
    }
}
