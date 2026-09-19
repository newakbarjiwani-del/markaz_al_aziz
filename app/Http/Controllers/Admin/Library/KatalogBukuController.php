<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Http\Requests\Library\StoreBukuRequest;
use App\Http\Requests\Library\UpdateBukuRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Buku;
use App\Models\PeminjamanBuku;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KatalogBukuController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('library.view');

        return view('admin.perpustakaan.katalog-buku', $this->katalogViewData(
            ajaxUrl: route('admin.perpustakaan.katalog-buku.data'),
            storeUrl: route('admin.perpustakaan.katalog-buku.store'),
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('library.view');

        $canUpdate = $request->user()?->can('library.update') ?? false;
        $canDelete = $request->user()?->can('library.delete') ?? false;
        $updateRoute = $this->katalogUpdateRouteName();
        $destroyRoute = $this->katalogDestroyRouteName();

        $query = Buku::query()
            ->with('sekolah:id,name')
            ->withCount([
                'peminjaman as active_loan_count' => fn ($q) => $q->where('status', PeminjamanBuku::STATUS_DIPINJAM),
            ]);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['kode_buku', 'isbn', 'judul', 'pengarang', 'penerbit', 'kategori'],
            'orderable' => [
                'kode_buku', 'isbn', 'judul', 'pengarang', 'penerbit',
                'tahun_terbit', 'jumlah', 'keadaan_baik', 'tersedia', 'nilai_rata', 'created_at',
            ],
        ], function (Buku $buku) use ($canUpdate, $canDelete, $updateRoute, $destroyRoute) {
            $row = [
                $buku->kode_buku ?? '-',
                $buku->isbn ?? '-',
                $buku->judul,
                $buku->pengarang ?? '-',
                $buku->penerbit ?? '-',
                $buku->tahun_terbit ?? '-',
                $buku->jumlah,
                $buku->keadaan_baik,
                $buku->tersedia,
                number_format((float) ($buku->nilai_rata ?? 0), 1),
            ];

            if ($canUpdate || $canDelete) {
                $row[] = $this->bukuActionCell(
                    $buku,
                    $canUpdate,
                    $canDelete,
                    $updateRoute,
                    $destroyRoute,
                    (int) ($buku->active_loan_count ?? 0),
                );
            }

            return $row;
        });
    }

    public function store(StoreBukuRequest $request): JsonResponse
    {
        $data = $this->payloadFromValidated($request->validated(), $request);
        $buku = new Buku($data);
        $buku->syncTersediaFromLoans();
        $buku->save();

        return $this->jsonSuccess(
            ActionMessage::withSubject('Buku berhasil ditambahkan', $buku->judul),
            $buku,
            201
        );
    }

    public function update(UpdateBukuRequest $request, Buku $buku): JsonResponse
    {
        $data = $this->payloadFromValidated($request->validated(), $request);
        $buku->fill($data);
        $buku->syncTersediaFromLoans();
        $buku->save();

        return $this->jsonSuccess(
            ActionMessage::withSubject('Buku berhasil diperbarui', $buku->judul),
            $buku
        );
    }

    public function destroy(Buku $buku): JsonResponse
    {
        $this->authorize('library.delete');

        $activeLoans = $buku->activeLoanCount();
        if ($activeLoans > 0) {
            return $this->jsonError(
                "Buku tidak dapat dihapus karena masih ada {$activeLoans} peminjaman aktif."
            );
        }

        $judul = $buku->judul;
        $buku->delete();

        return $this->jsonSuccess(
            ActionMessage::withSubject('Buku berhasil dihapus', $judul)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function katalogViewData(string $ajaxUrl, string $storeUrl): array
    {
        $user = auth()->user();
        $canManage = ($user?->can('library.update') || $user?->can('library.delete')) ?? false;
        $showSchoolSelect = $user && ! filled($user->sekolah_id);

        return [
            'title' => 'Katalog Buku',
            'ajaxUrl' => $ajaxUrl,
            'storeUrl' => $storeUrl,
            'canManage' => $canManage,
            'showSchoolSelect' => $showSchoolSelect,
            'schools' => $showSchoolSelect ? AdminSchoolScope::schools() : collect(),
            'operatorSchoolId' => filled($user?->sekolah_id) ? (int) $user->sekolah_id : null,
        ];
    }

    protected function katalogUpdateRouteName(): string
    {
        return 'admin.perpustakaan.katalog-buku.update';
    }

    protected function katalogDestroyRouteName(): string
    {
        return 'admin.perpustakaan.katalog-buku.destroy';
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payloadFromValidated(array $validated, Request $request): array
    {
        $sekolahId = $this->resolveSekolahId($request);

        return [
            'sekolah_id' => $sekolahId,
            'kode_buku' => $validated['kode_buku'] ?? null,
            'isbn' => $validated['isbn'] ?? null,
            'judul' => $validated['judul'],
            'pengarang' => $validated['pengarang'] ?? null,
            'penerbit' => $validated['penerbit'] ?? null,
            'kategori' => $validated['kategori'] ?? null,
            'tahun_terbit' => $validated['tahun_terbit'] ?? null,
            'cetak_ke' => (int) ($validated['cetak_ke'] ?? 1),
            'jumlah' => (int) $validated['jumlah'],
            'keadaan_baik' => (int) $validated['keadaan_baik'],
            'keadaan_rusak_ringan' => (int) $validated['keadaan_rusak_ringan'],
            'keadaan_rusak_berat' => (int) $validated['keadaan_rusak_berat'],
            'tanggal_penerimaan' => $validated['tanggal_penerimaan'] ?? null,
            'sumber_dana' => $validated['sumber_dana'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
        ];
    }

    private function resolveSekolahId(Request $request): ?int
    {
        $user = $request->user();
        if ($user && filled($user->sekolah_id)) {
            return (int) $user->sekolah_id;
        }

        return $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null;
    }

    private function bukuActionCell(
        Buku $buku,
        bool $canUpdate,
        bool $canDelete,
        string $updateRoute,
        string $destroyRoute,
        int $activeLoanCount = 0,
    ): array {
        $actions = [];

        if ($canUpdate) {
            $actions['edit'] = [
                'update_url' => route($updateRoute, $buku),
                'form_target' => 'buku-form',
                'modal_target' => 'buku-modal',
                'modal_title' => 'Ubah Buku',
                'record' => [
                    'sekolah_id' => $buku->sekolah_id ?? '',
                    'kode_buku' => $buku->kode_buku ?? '',
                    'isbn' => $buku->isbn ?? '',
                    'judul' => $buku->judul,
                    'pengarang' => $buku->pengarang ?? '',
                    'penerbit' => $buku->penerbit ?? '',
                    'kategori' => $buku->kategori ?? '',
                    'tahun_terbit' => $buku->tahun_terbit ?? '',
                    'cetak_ke' => $buku->cetak_ke ?? 1,
                    'jumlah' => $buku->jumlah,
                    'keadaan_baik' => $buku->keadaan_baik,
                    'keadaan_rusak_ringan' => $buku->keadaan_rusak_ringan,
                    'keadaan_rusak_berat' => $buku->keadaan_rusak_berat,
                    'tanggal_penerimaan' => $buku->tanggal_penerimaan?->format('Y-m-d') ?? '',
                    'sumber_dana' => $buku->sumber_dana ?? '',
                    'keterangan' => $buku->keterangan ?? '',
                ],
            ];
        }

        if ($canDelete && $activeLoanCount === 0) {
            $actions['delete'] = [
                'url' => route($destroyRoute, $buku),
                'confirm_title' => 'Konfirmasi Hapus',
                'confirm_message' => 'Apakah Anda yakin ingin menghapus buku ini?',
                'confirm_detail' => [
                    ['label' => 'Judul', 'value' => $buku->judul],
                    ['label' => 'ISBN', 'value' => $buku->isbn ?: '-'],
                    ['label' => 'Kode', 'value' => $buku->kode_buku ?: '-'],
                    ['label' => 'Jumlah', 'value' => (string) $buku->jumlah],
                ],
            ];
        }

        if ($actions === []) {
            return $this->cell('-', '-', 'action');
        }

        return $this->cell('', ['actions' => $actions], 'action');
    }
}
