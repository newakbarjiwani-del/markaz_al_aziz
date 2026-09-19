<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Admin\Library\Concerns\BuildsLibraryLoanQueries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Library\ReturnPeminjamanRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Services\LibraryLoanService;
use App\Support\ActionMessage;
use App\Support\LibraryLoanPresenter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengembalianBukuController extends Controller
{
    use BuildsLibraryLoanQueries;
    use DataTableTrait;

    public function index(): View
    {
        $baseQuery = $this->activeLoanQuery();

        return view('admin.perpustakaan.pengembalian-buku', array_merge($this->indexViewData(), [
            'stats' => $this->pengembalianStats($baseQuery),
        ]));
    }

    public function store(ReturnPeminjamanRequest $request, LibraryLoanService $service): JsonResponse
    {
        $validated = $request->validated();

        $items = $service->returnTransaction(
            (int) $validated['peminjaman_id'],
            $this->loanSekolahScope(),
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

    /**
     * Preview the return for all active items in a Peminjaman (parent) transaction.
     */
    public function preview(Request $request, Peminjaman $peminjaman): JsonResponse
    {
        $peminjaman->loadMissing(['items.buku', 'items.peminjaman.siswa', 'items.peminjaman.guru']);

        $activeItems = $peminjaman->items->where('status', PeminjamanBuku::STATUS_DIPINJAM);
        if ($activeItems->isEmpty()) {
            return $this->jsonError('Tidak ada buku aktif yang perlu dikembalikan.');
        }

        $kondisi = $request->string('kondisi_kembali', PeminjamanBuku::KONDISI_BAIK)->toString();
        if (! array_key_exists($kondisi, PeminjamanBuku::kondisiOptions())) {
            $kondisi = PeminjamanBuku::KONDISI_BAIK;
        }

        $returnDate = $request->filled('return_date')
            ? Carbon::parse($request->string('return_date'))->startOfDay()
            : now()->startOfDay();

        // Use the first active item for preview — all items share the same kondisi/returnDate.
        $firstActive = $activeItems->first();

        return response()->json([
            'success' => true,
            'data' => LibraryLoanPresenter::loan($firstActive, $kondisi, $returnDate),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->applyPeminjamanFilters($this->activeLoanQuery(), $request);

        return $this->datatableResponse($request, $query, [
            'searchable' => [
                'borrower_type',
                'tamu_nama',
                'tamu_asal',
                'tamu_telepon',
                'siswa.name',
                'siswa.nis',
                'guru.name',
                'guru.nip',
            ],
            'orderable' => ['loan_date', 'due_date', 'borrower_type', 'created_at'],
        ], function (Peminjaman $row) {
            $fineTotal = $row->items
                ->where('status', PeminjamanBuku::STATUS_DIPINJAM)
                ->sum(fn ($i) => $i->calculateFine()['total_fine']);
            $extCount = $row->items->sum('perpanjangan_count');

            return [
                $row->borrowerTypeLabel(),
                $row->borrowerIdentifier(),
                $row->borrowerName(),
                $row->borrowerMeta(),
                $this->bookListHtml($row),
                $row->loan_date,
                $row->due_date,
                $this->loanTimingLabel($row),
                $extCount > 0 ? $extCount.'x' : '-',
                $this->formatRupiah((int) $fineTotal),
                $this->cell('', [
                    'actions' => [
                        'loan_detail' => ['loan_id' => $row->id],
                        'loan_return' => [
                            'loan_id' => $row->id,
                            'label' => $row->borrowerDisplayLabel(),
                            'loan_date' => $row->loan_date?->format('Y-m-d') ?? '',
                            'due_date' => $row->due_date?->format('Y-m-d') ?? '',
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function indexViewData(): array
    {
        return array_merge($this->loanSettingsPayload(), [
            'title' => 'Pengembalian Buku',
            'formAction' => route('admin.perpustakaan.pengembalian-buku.store'),
            'previewUrl' => url('admin/perpustakaan/pengembalian-buku/preview'),
            'detailUrl' => url('admin/perpustakaan/peminjaman'),
            'settingUrl' => route('admin.perpustakaan.setting-denda.index'),
            'kondisiOptions' => \App\Support\LibrarySettings::kondisiOptionsForReturn(),
        ]);
    }

    /**
     * Active loans at the Peminjaman (parent) level where at least one item is dipinjam.
     */
    protected function activeLoanQuery(): Builder
    {
        return Peminjaman::query()
            ->select('peminjaman.*')
            ->with(['items.buku', 'siswa.kelas', 'guru'])
            ->whereHas('items', fn ($q) => $q->where('status', PeminjamanBuku::STATUS_DIPINJAM));
    }

    protected function loanSekolahScope(): ?int
    {
        return null;
    }

    protected function ensureLoanAccessible(PeminjamanBuku $loan): void
    {
        $loan->loadMissing(['peminjaman.siswa', 'peminjaman.guru', 'buku']);
    }
}
