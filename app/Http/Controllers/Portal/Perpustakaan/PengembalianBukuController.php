<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\PengembalianBukuController as BaseController;
use App\Http\Controllers\Portal\Perpustakaan\Concerns\ScopedToLibrarySekolah;
use App\Http\Requests\Library\ReturnPeminjamanRequest;
use App\Models\Kelas;
use App\Models\PeminjamanBuku;
use App\Services\LibraryLoanService;
use App\Support\ActionMessage;
use App\Support\LibraryLoanPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PengembalianBukuController extends BaseController
{
    use ScopedToLibrarySekolah;

    public function index(): View
    {
        $baseQuery = $this->activeLoanQuery();

        return view('admin.perpustakaan.pengembalian-buku', array_merge($this->indexViewData(), [
            'stats' => $this->pengembalianStats($baseQuery),
            'kondisiOptions' => \App\Support\LibrarySettings::kondisiOptionsForReturn(),
            'formAction' => route('portal.perpustakaan.pengembalian-buku.store'),
            'ajaxUrl' => route('portal.perpustakaan.pengembalian-buku.data'),
            'previewUrl' => url('portal/perpustakaan/pengembalian-buku/preview'),
            'detailUrl' => url('portal/perpustakaan/peminjaman'),
            'settingUrl' => route('portal.perpustakaan.setting-denda.index'),
            'classes' => Kelas::query()
                ->when($this->librarySekolahId(), fn (Builder $query, int $sekolahId) => $query->where('sekolah_id', $sekolahId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]));
    }

    public function store(ReturnPeminjamanRequest $request, LibraryLoanService $service): JsonResponse
    {
        $validated = $request->validated();

        $items = $service->returnTransaction(
            (int) $validated['peminjaman_id'],
            $this->librarySekolahId(),
            [
                'return_date'           => $validated['return_date'] ?? null,
                'kondisi_kembali'        => $validated['kondisi_kembali'],
                'catatan_kembali'        => $validated['catatan_kembali'] ?? null,
                'processed_by_user_id'   => auth()->id(),
            ],
        );

        $first = $items->first();
        $count = $items->count();
        $subject = $first?->borrowerDisplayLabel() ?? 'Peminjaman';
        $totalFine = $items->sum('fine_amount');
        $fineText = $totalFine > 0 ? ' · denda '.ActionMessage::rupiah((int) $totalFine) : '';

        return $this->jsonSuccess(
            ActionMessage::withSubject($count.' buku berhasil diproses'.$fineText, $subject),
            [
                'count' => $count,
                'items' => $items->map(fn ($item) => LibraryLoanPresenter::loan($item))->values()->all(),
                'total_fine' => $totalFine,
            ]
        );
    }

    protected function activeLoanQuery(): Builder
    {
        return $this->applyLibraryLoanSchoolScope(parent::activeLoanQuery(), $this->librarySekolahId());
    }

    protected function loanSekolahScope(): ?int
    {
        return $this->librarySekolahId();
    }

    protected function ensureLoanAccessible(PeminjamanBuku $loan): void
    {
        $loan->loadMissing(['peminjaman.siswa', 'peminjaman.guru', 'buku']);

        if ($this->librarySekolahId() === null) {
            return;
        }

        $outside = match ($loan->borrower_type) {
            PeminjamanBuku::BORROWER_SISWA => $loan->peminjaman?->siswa === null || $this->siswaOutsideLibraryScope($loan->peminjaman->siswa),
            PeminjamanBuku::BORROWER_GURU  => $loan->peminjaman?->guru === null || $this->guruOutsideLibraryScope($loan->peminjaman->guru),
            PeminjamanBuku::BORROWER_TAMU  => $loan->buku?->sekolah_id !== null
                && (int) $loan->buku->sekolah_id !== $this->librarySekolahId(),
            default => true,
        };

        abort_if($outside, 404);
    }
}
