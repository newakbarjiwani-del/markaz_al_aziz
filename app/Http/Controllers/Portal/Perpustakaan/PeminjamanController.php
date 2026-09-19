<?php

namespace App\Http\Controllers\Portal\Perpustakaan;

use App\Http\Controllers\Admin\Library\PeminjamanController as BaseController;
use App\Http\Controllers\Portal\Perpustakaan\Concerns\ScopedToLibrarySekolah;
use App\Http\Requests\Library\StorePeminjamanRequest;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Models\Siswa;
use App\Services\LibraryLoanService;
use App\Support\ActionMessage;
use App\Support\LibraryLoanPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PeminjamanController extends BaseController
{
    use ScopedToLibrarySekolah;

    public function index(): View
    {
        $baseQuery = $this->peminjamanQuery();

        return view('admin.perpustakaan.peminjaman', array_merge($this->indexViewData(), [
            'stats' => $this->peminjamanStats($baseQuery),
            'ajaxUrl' => route('portal.perpustakaan.peminjaman.data'),
            'createUrl' => route('portal.perpustakaan.peminjaman.create'),
            'settingUrl' => route('portal.perpustakaan.setting-denda.index'),
            'detailUrl' => url('portal/perpustakaan/peminjaman'),
            'classes' => Kelas::query()
                ->when($this->librarySekolahId(), fn (Builder $query, int $sekolahId) => $query->where('sekolah_id', $sekolahId))
                ->orderBy('name')
                ->get(['id', 'name']),
        ]));
    }

    public function create(): View
    {
        return view('admin.perpustakaan.peminjaman-create', array_merge($this->createViewData(), [
            'formAction' => route('portal.perpustakaan.peminjaman.store'),
            'redirectUrl' => route('portal.perpustakaan.peminjaman.index'),
            'backUrl' => route('portal.perpustakaan.peminjaman.index'),
            'siswaLookupUrl' => route('portal.perpustakaan.rekap-pengunjung.lookup'),
            'siswaLookupResolveUrl' => url('portal/perpustakaan/rekap-pengunjung/lookup'),
            'guruLookupUrl' => route('portal.perpustakaan.rekap-pengunjung.guru-lookup'),
            'guruLookupResolveUrl' => url('portal/perpustakaan/rekap-pengunjung/guru/lookup'),
            'bukuLookupUrl' => route('portal.perpustakaan.buku.lookup'),
            'bukuLookupResolveUrl' => url('portal/perpustakaan/buku/lookup'),
            'siswaSummaryUrl' => url('portal/perpustakaan/peminjaman/siswa'),
            'guruSummaryUrl' => url('portal/perpustakaan/peminjaman/guru'),
            'tamuSummaryUrl' => route('portal.perpustakaan.peminjaman.tamu-summary'),
            'resolveRfidUrl' => route('portal.perpustakaan.peminjaman.resolve-rfid'),
        ]));
    }

    public function store(StorePeminjamanRequest $request, LibraryLoanService $service): JsonResponse
    {
        $validated = $request->validated();

        $loans = $service->borrowMany(
            [
                'borrower_type' => $validated['borrower_type'],
                'siswa_id' => $validated['siswa_id'] ?? null,
                'guru_id' => $validated['guru_id'] ?? null,
                'tamu_nama' => $validated['tamu_nama'] ?? null,
                'tamu_asal' => $validated['tamu_asal'] ?? null,
                'tamu_telepon' => $validated['tamu_telepon'] ?? null,
            ],
            $validated['buku_ids'],
            $this->loanSekolahScope(),
            [
                'loan_date' => $validated['loan_date'] ?? null,
                'due_date' => $validated['due_date'] ?? null,
                'catatan_pinjam' => $validated['catatan_pinjam'] ?? null,
                'processed_by_user_id' => auth()->id(),
            ],
        );

        $parent    = $loans->first()->peminjaman()->with('items.buku')->first();
        $itemCount = $parent->items->count();
        $subject   = $parent->borrowerDisplayLabel();

        if ($itemCount === 1) {
            $subject .= ' · '.($parent->items->first()->buku?->judul ?? '');
            $message = ActionMessage::withSubject('Buku berhasil dipinjam', $subject);
        } else {
            $message = ActionMessage::withSubject($itemCount.' buku berhasil dipinjam', $subject);
        }

        return $this->jsonSuccess(
            $message,
            [
                'count' => $itemCount,
                'transaction' => LibraryLoanPresenter::transaction($parent),
            ],
            redirect: route('portal.perpustakaan.peminjaman.index'),
        );
    }

    protected function indexViewData(): array
    {
        return array_merge(parent::indexViewData(), [
            'createUrl' => route('portal.perpustakaan.peminjaman.create'),
            'settingUrl' => route('portal.perpustakaan.setting-denda.index'),
            'detailUrl' => url('portal/perpustakaan/peminjaman'),
            'extendUrl' => url('portal/perpustakaan/peminjaman'),
        ]);
    }

    protected function loanActionButtons(Peminjaman $loan, ?string $extendUrl = null): array
    {
        return parent::loanActionButtons(
            $loan,
            route('portal.perpustakaan.peminjaman.extend', $loan->id)
        );
    }

    protected function createViewData(): array
    {
        return array_merge(parent::createViewData(), [
            'formAction' => route('portal.perpustakaan.peminjaman.store'),
            'redirectUrl' => route('portal.perpustakaan.peminjaman.index'),
            'backUrl' => route('portal.perpustakaan.peminjaman.index'),
            'siswaSummaryUrl' => url('portal/perpustakaan/peminjaman/siswa'),
            'guruSummaryUrl' => url('portal/perpustakaan/peminjaman/guru'),
            'tamuSummaryUrl' => route('portal.perpustakaan.peminjaman.tamu-summary'),
            'resolveRfidUrl' => route('portal.perpustakaan.peminjaman.resolve-rfid'),
        ]);
    }

    protected function loanSekolahScope(): ?int
    {
        return null;
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

    protected function ensureSiswaAccessible(Siswa $siswa): void
    {
        // Allowed globally
    }

    protected function ensureGuruAccessible(Guru $guru): void
    {
        // Allowed globally
    }
}
