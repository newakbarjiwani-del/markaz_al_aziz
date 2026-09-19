<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Admin\Library\Concerns\BuildsLibraryLoanQueries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Library\ExtendPeminjamanRequest;
use App\Http\Requests\Library\StorePeminjamanRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Guru;
use App\Models\Peminjaman;
use App\Models\PeminjamanBuku;
use App\Models\Siswa;
use App\Services\LibraryLoanService;
use App\Services\RfidResolver;
use App\Support\ActionMessage;
use App\Support\DisplayDate;
use App\Support\GuruSekolahFilter;
use App\Support\LibraryLoanPresenter;
use App\Support\RfidUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PeminjamanController extends Controller
{
    use BuildsLibraryLoanQueries;
    use DataTableTrait;

    public function __construct(
        private readonly RfidResolver $rfidResolver,
    ) {}

    public function index(): View
    {
        $baseQuery = $this->peminjamanQuery();

        return view('admin.perpustakaan.peminjaman', array_merge($this->indexViewData(), [
            'stats' => $this->peminjamanStats($baseQuery),
        ]));
    }

    public function create(): View
    {
        return view('admin.perpustakaan.peminjaman-create', $this->createViewData());
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

        $parent = $loans->first()->peminjaman()->with('items.buku')->first();
        $itemCount = $parent->items->count();
        $subject = $parent->borrowerDisplayLabel();

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
            redirect: route('admin.perpustakaan.peminjaman.index'),
        );
    }

    public function data(Request $request): JsonResponse
    {
        $query = $this->applyPeminjamanFilters($this->peminjamanQuery(), $request);

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

            return [
                $row->borrowerTypeLabel(),
                $row->borrowerIdentifier(),
                $row->borrowerName(),
                $row->borrowerMeta(),
                $this->bookListHtml($row),
                $row->loan_date,
                $row->due_date,
                $this->loanTimingLabel($row),
                $this->statusBadge($row),
                $this->formatRupiah((int) $fineTotal),
                $row->catatan_pinjam ? e(Str::limit($row->catatan_pinjam, 40)) : '-',
                $this->loanActionButtons($row),
            ];
        });
    }

    public function show(Peminjaman $peminjaman): JsonResponse
    {
        $peminjaman->loadMissing(['items.buku', 'siswa.kelas', 'guru']);

        return response()->json([
            'success' => true,
            'data' => LibraryLoanPresenter::transaction($peminjaman),
        ]);
    }

    public function siswaSummary(Siswa $siswa): JsonResponse
    {
        $this->ensureSiswaAccessible($siswa);

        return response()->json([
            'success' => true,
            'data' => LibraryLoanPresenter::siswaSummary($siswa),
        ]);
    }

    public function guruSummary(Guru $guru): JsonResponse
    {
        $this->ensureGuruAccessible($guru);

        return response()->json([
            'success' => true,
            'data' => LibraryLoanPresenter::guruSummary($guru),
        ]);
    }

    public function tamuSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tamu_nama' => ['required', 'string', 'max:255'],
            'tamu_asal' => ['nullable', 'string', 'max:255'],
            'tamu_telepon' => ['nullable', 'string', 'max:30'],
        ]);

        return response()->json([
            'success' => true,
            'data' => LibraryLoanPresenter::tamuSummary(
                $validated['tamu_nama'],
                $validated['tamu_asal'] ?? null,
                $validated['tamu_telepon'] ?? null,
            ),
        ]);
    }

    public function resolveRfid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:64'],
        ]);

        $uid = RfidUid::sanitize($validated['rfid_uid']) ?? '';

        if ($uid === '') {
            return $this->jsonError('UID kartu RFID tidak valid.', ['rfid_uid' => ['RFID tidak valid.']]);
        }

        $sekolahScope = $this->loanSekolahScope();

        $resolved = $this->rfidResolver->resolveHolder($uid);
        if (! $resolved) {
            return $this->jsonError('Kartu RFID tidak dikenali atau peminjam tidak aktif.', null, 404);
        }

        if ($resolved['type'] === 'siswa') {
            /** @var Siswa $siswa */
            $siswa = $resolved['model'];
            if (! $siswa->isActive() || ($sekolahScope !== null && (int) $siswa->sekolah_id !== $sekolahScope)) {
                return $this->jsonError('Kartu RFID tidak dikenali atau peminjam tidak aktif.', null, 404);
            }
            $siswa->loadMissing('kelas:id,name');

            return $this->jsonSuccess('Siswa ditemukan.', [
                'borrower_type' => PeminjamanBuku::BORROWER_SISWA,
                'siswa' => [
                    'id' => $siswa->id,
                    'nis' => $siswa->nis,
                    'name' => $siswa->name,
                    'kelas' => $siswa->kelas?->name,
                    'label' => $siswa->name.' · NIS '.$siswa->nis.($siswa->kelas ? ' · '.$siswa->kelas->name : ''),
                ],
            ]);
        }

        if ($resolved['type'] === 'guru') {
            /** @var Guru $guru */
            $guru = $resolved['model'];
            $isAccessible = $sekolahScope === null
                || GuruSekolahFilter::applyAccessibleScope(Guru::query()->whereKey($guru->id), $sekolahScope)->exists();
            if ($guru->status !== 'aktif' || ! $isAccessible) {
                return $this->jsonError('Kartu RFID tidak dikenali atau peminjam tidak aktif.', null, 404);
            }

            return $this->jsonSuccess('Guru ditemukan.', [
                'borrower_type' => PeminjamanBuku::BORROWER_GURU,
                'guru' => [
                    'id' => $guru->id,
                    'nip' => $guru->nip,
                    'name' => $guru->name,
                    'jabatan' => $guru->jabatan,
                    'label' => $guru->name.($guru->nip ? ' · NIP '.$guru->nip : '').($guru->jabatan ? ' · '.$guru->jabatan : ''),
                ],
            ]);
        }

        return $this->jsonError('Kartu RFID tidak dikenali atau peminjam tidak aktif.', null, 404);
    }

    public function extend(ExtendPeminjamanRequest $request, PeminjamanBuku $peminjaman, LibraryLoanService $service): JsonResponse
    {
        $this->ensureLoanAccessible($peminjaman);

        $validated = $request->validated();

        $loan = $service->extend($peminjaman->id, $this->loanSekolahScope(), [
            'due_date' => $validated['due_date'],
        ]);

        return $this->jsonSuccess(
            'Peminjaman diperpanjang · jatuh tempo baru '.DisplayDate::date($loan->due_date),
            LibraryLoanPresenter::loan($loan)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function indexViewData(): array
    {
        return array_merge($this->loanSettingsPayload(), [
            'title' => 'Peminjaman Buku',
            'createUrl' => route('admin.perpustakaan.peminjaman.create'),
            'settingUrl' => route('admin.perpustakaan.setting-denda.index'),
            'detailUrl' => url('admin/perpustakaan/peminjaman'),
            'extendUrl' => url('admin/perpustakaan/peminjaman'),
            'classes' => collect(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function createViewData(): array
    {
        return array_merge($this->loanSettingsPayload(), [
            'title' => 'Pinjam Buku Baru',
            'formAction' => route('admin.perpustakaan.peminjaman.store'),
            'redirectUrl' => route('admin.perpustakaan.peminjaman.index'),
            'backUrl' => route('admin.perpustakaan.peminjaman.index'),
            'siswaSummaryUrl' => url('admin/perpustakaan/peminjaman/siswa'),
            'guruSummaryUrl' => url('admin/perpustakaan/peminjaman/guru'),
            'tamuSummaryUrl' => route('admin.perpustakaan.peminjaman.tamu-summary'),
            'resolveRfidUrl' => route('admin.perpustakaan.peminjaman.resolve-rfid'),
        ]);
    }

    /**
     * Query Peminjaman (parent) with eager-loaded items + borrowers.
     */
    protected function peminjamanQuery(): Builder
    {
        return Peminjaman::query()
            ->select('peminjaman.*')
            ->with(['items.buku', 'siswa.kelas', 'guru']);
    }

    protected function loanSekolahScope(): ?int
    {
        return null;
    }

    protected function ensureLoanAccessible(PeminjamanBuku $loan): void
    {
        $loan->loadMissing(['peminjaman.siswa', 'peminjaman.guru', 'buku']);
    }

    protected function ensureSiswaAccessible(Siswa $siswa): void
    {
        //
    }

    protected function ensureGuruAccessible(Guru $guru): void
    {
        //
    }

    protected function jsonSuccess(string $message, mixed $data = null, int $status = 200, ?string $redirect = null): JsonResponse
    {
        return response()->json(array_filter([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'redirect' => $redirect,
        ], fn ($value) => $value !== null), $status);
    }
}
