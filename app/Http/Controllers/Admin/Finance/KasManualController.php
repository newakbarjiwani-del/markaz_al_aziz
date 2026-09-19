<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreKasManualRequest;
use App\Http\Requests\Finance\UpdateKasManualRequest;
use App\Http\Traits\DataTableTrait;
use App\Http\Traits\FilterTrait;
use App\Models\KasManual;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use App\Support\AjaxSelect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KasManualController extends Controller
{
    use DataTableTrait;
    use FilterTrait;

    public function index(): View
    {
        $this->authorize('finance.view');

        $stats = $this->aggregateStats(KasManual::query());

        return view('admin.keuangan.kas-manual', [
            'title' => 'Kas Manual',
            'totalKredit' => $stats['total_kredit'],
            'totalDebet' => $stats['total_debet'],
            'saldoBersih' => $stats['saldo_bersih'],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        return $this->jsonSuccess('OK', $this->aggregateStats($this->filteredQuery($request)));
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        $query = $this->filteredQuery($request)->with('creator:id,name');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['kategori', 'deskripsi', 'creator.name'],
            'orderable' => ['tanggal', 'kategori', 'kredit', 'debet', 'created_at'],
        ], function (KasManual $row) {
            $displayKredit = $row->kredit ? 'Rp '.number_format($row->kredit, 0, ',', '.') : '-';
            $displayDebet = $row->debet ? 'Rp '.number_format($row->debet, 0, ',', '.') : '-';

            return [
                $row->tanggal?->isoFormat('D MMM YYYY'),
                $row->kategori,
                $row->deskripsi ?? '-',
                $this->cell($displayKredit, $row->kredit ?? 0, 'number'),
                $this->cell($displayDebet, $row->debet ?? 0, 'number'),
                $row->creator?->name ?? '-',
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.keuangan.kas-manual.update', $row),
                            'form_target' => 'kas-manual-form',
                            'modal_target' => 'kas-manual-modal',
                            'modal_title' => 'Ubah Catatan Kas',
                            'record' => $this->formRecord($row),
                        ],
                        'delete' => [
                            'url' => route('admin.keuangan.kas-manual.destroy', $row),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus catatan kas ini?',
                            'confirm_detail' => [
                                ['label' => 'Tanggal', 'value' => $row->tanggal?->isoFormat('D MMM YYYY')],
                                ['label' => 'Kategori', 'value' => $row->kategori],
                                ['label' => 'Kredit', 'value' => $displayKredit],
                                ['label' => 'Debet', 'value' => $displayDebet],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreKasManualRequest $request): JsonResponse
    {
        $data = $request->validated();

        $kas = KasManual::create([
            ...$this->attributesFromValidated($data),
            'sekolah_id' => AdminSchoolScope::resolveForStore($request),
            'created_by' => $request->user()?->id,
        ]);

        return $this->jsonSuccess(
            $this->successMessage('ditambahkan', $kas),
            $kas,
            201
        );
    }

    public function update(UpdateKasManualRequest $request, KasManual $kasManual): JsonResponse
    {
        $kasManual->update($this->attributesFromValidated($request->validated()));

        return $this->jsonSuccess(
            $this->successMessage('diperbarui', $kasManual),
            $kasManual
        );
    }

    public function destroy(KasManual $kasManual): JsonResponse
    {
        $this->authorize('finance.delete');

        $detail = $this->recordLabel($kasManual);
        $kasManual->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Catatan kas berhasil dihapus', $detail));
    }

    public function kategoriList(Request $request): JsonResponse
    {
        $this->authorize('finance.view');

        $term = trim($request->string('term')->toString());

        $items = KasManual::query()
            ->select('kategori')
            ->when($term !== '', fn (Builder $q) => $q->where('kategori', 'like', '%'.$term.'%'))
            ->distinct()
            ->orderBy('kategori')
            ->limit(25)
            ->pluck('kategori')
            ->map(fn (string $kategori) => ['id' => $kategori, 'text' => $kategori]);

        return AjaxSelect::respond($items);
    }

    private function filteredQuery(Request $request): Builder
    {
        return KasManual::query()
            ->when($request->filled('kategori'), fn (Builder $q) => $q->where('kategori', $request->kategori))
            ->when($request->filled('date_from') || $request->filled('date_to'), function (Builder $q) use ($request) {
                $this->applyDateRange($q, $request, 'tanggal');
            });
    }

    /**
     * @return array{total_kredit: int, total_debet: int, saldo_bersih: int}
     */
    private function aggregateStats(Builder $query): array
    {
        $totalKredit = (int) (clone $query)->sum('kredit');
        $totalDebet = (int) (clone $query)->sum('debet');

        return [
            'total_kredit' => $totalKredit,
            'total_debet' => $totalDebet,
            'saldo_bersih' => $totalKredit - $totalDebet,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributesFromValidated(array $data): array
    {
        $nominal = (int) $data['nominal'];

        return [
            'tanggal' => $data['tanggal'],
            'kategori' => $data['kategori'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'kredit' => $data['arah'] === 'pemasukan' ? $nominal : null,
            'debet' => $data['arah'] === 'pengeluaran' ? $nominal : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formRecord(KasManual $row): array
    {
        $isPemasukan = (int) ($row->kredit ?? 0) > 0;

        return [
            'tanggal' => $row->tanggal?->format('Y-m-d'),
            'kategori' => $row->kategori,
            'deskripsi' => $row->deskripsi,
            'arah' => $isPemasukan ? 'pemasukan' : 'pengeluaran',
            'nominal' => $isPemasukan ? $row->kredit : $row->debet,
        ];
    }

    private function recordLabel(KasManual $kas): string
    {
        $nominal = (int) ($kas->kredit ?: $kas->debet ?: 0);
        $arah = $kas->kredit ? 'Pemasukan' : 'Pengeluaran';

        return $kas->kategori.' · '.$arah.' '.ActionMessage::rupiah($nominal).' · '.$kas->tanggal?->isoFormat('D MMM YYYY');
    }

    private function successMessage(string $verb, KasManual $kas): string
    {
        return ActionMessage::withSubject('Catatan kas berhasil '.$verb, $this->recordLabel($kas));
    }
}
