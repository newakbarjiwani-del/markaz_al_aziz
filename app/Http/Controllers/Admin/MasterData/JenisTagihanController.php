<?php

namespace App\Http\Controllers\Admin\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreJenisTagihanRequest;
use App\Http\Requests\MasterData\UpdateJenisTagihanRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\JenisTagihan;
use App\Support\ActionMessage;
use App\Support\MasterDataUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JenisTagihanController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.master-data.jenis-tagihan', [
            'title' => 'Master Data — Jenis Tagihan',
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('master_data.view');

        $query = JenisTagihan::query()
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'code', 'description'],
            'orderable' => ['name', 'sort_order', 'created_at'],
        ], function (JenisTagihan $jenisTagihan) {
            $blocked = MasterDataUsage::jenisTagihanBlockedReason($jenisTagihan);
            $fields = array_merge(
                $jenisTagihan->only(['name', 'code', 'description', 'default_amount', 'sort_order']),
                [
                    'is_spp' => $jenisTagihan->is_spp ? '1' : '0',
                    'is_active' => $jenisTagihan->is_active ? '1' : '0',
                ]
            );

            $actions = [];
            if ($blocked === null) {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.jenis-tagihan.update', $jenisTagihan),
                    'form_target' => 'jenis-tagihan-form',
                    'modal_target' => 'jenis-tagihan-modal',
                    'record' => $fields,
                ];
                $actions['delete'] = [
                    'url' => route('admin.master-data.jenis-tagihan.destroy', $jenisTagihan),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus jenis tagihan ini?',
                    'confirm_detail' => [
                        ['label' => 'Jenis', 'value' => $jenisTagihan->name],
                        ['label' => 'Kode', 'value' => $jenisTagihan->code ?? '-'],
                        ['label' => 'Default', 'value' => 'Rp '.number_format((int) $jenisTagihan->default_amount, 0, ',', '.')],
                    ],
                ];
            } else {
                $actions['edit'] = [
                    'update_url' => route('admin.master-data.jenis-tagihan.update', $jenisTagihan),
                    'form_target' => 'jenis-tagihan-form',
                    'modal_target' => 'jenis-tagihan-modal',
                    'record' => $fields,
                    'partial_edit' => true,
                    'editable_fields' => 'default_amount,is_spp',
                ];
            }

            return [
                $jenisTagihan->name,
                $jenisTagihan->code ?? '-',
                $jenisTagihan->default_amount
                    ? $this->cell('Rp '.number_format((float) $jenisTagihan->default_amount, 0, ',', '.'), (float) $jenisTagihan->default_amount, 'number')
                    : '-',
                $jenisTagihan->is_spp
                    ? $this->badgeCell('SPP', 'badge badge-accent')
                    : '-',
                $this->badgeCell($jenisTagihan->is_active ? 'Aktif' : 'Nonaktif', $jenisTagihan->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreJenisTagihanRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isSpp = $request->boolean('is_spp');

        $jenisTagihan = JenisTagihan::create([
            ...$data,
            'is_spp' => $isSpp,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis tagihan berhasil ditambahkan', $jenisTagihan->name),
            $jenisTagihan,
            201
        );
    }

    public function update(Request $request, JenisTagihan $jenisTagihan): JsonResponse
    {
        if (MasterDataUsage::jenisTagihanBlockedReason($jenisTagihan)) {
            $this->authorize('master_data.update');
            $request->validate(UpdateJenisTagihanRequest::limitedRules());

            return $this->updateInUse($request, $jenisTagihan);
        }

        $data = $this->validateWithFormRequest($request, UpdateJenisTagihanRequest::class);
        $isSpp = $request->boolean('is_spp');

        $jenisTagihan->update([
            ...$data,
            'is_spp' => $isSpp,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Jenis tagihan berhasil diperbarui', $jenisTagihan->name),
            $jenisTagihan
        );
    }

    private function updateInUse(Request $request, JenisTagihan $jenisTagihan): JsonResponse
    {
        $isSpp = $request->boolean('is_spp');

        $jenisTagihan->update([
            'default_amount' => $request->input('default_amount'),
            'is_spp' => $isSpp,
        ]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pengaturan SPP dan nominal default berhasil diperbarui', $jenisTagihan->name),
            $jenisTagihan
        );
    }

    /**
     * @param  class-string<\Illuminate\Foundation\Http\FormRequest>  $formRequestClass
     * @return array<string, mixed>
     */
    private function validateWithFormRequest(Request $request, string $formRequestClass): array
    {
        /** @var \Illuminate\Foundation\Http\FormRequest $formRequest */
        $formRequest = $formRequestClass::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->setRouteResolver($request->getRouteResolver());
        $formRequest->setUserResolver($request->getUserResolver());
        $formRequest->validateResolved();

        return $formRequest->validated();
    }

    public function destroy(JenisTagihan $jenisTagihan): JsonResponse
    {
        $this->authorize('master_data.delete');

        if ($reason = MasterDataUsage::jenisTagihanBlockedReason($jenisTagihan)) {
            return $this->jsonError('Jenis tagihan tidak dapat dihapus. '.$reason);
        }

        $name = $jenisTagihan->name;
        $jenisTagihan->delete();

        return $this->jsonSuccess(ActionMessage::withSubject('Jenis tagihan berhasil dihapus', $name));
    }

    public function seedSppBulanan(Request $request): JsonResponse
    {
        $this->authorize('master_data.create');

        $months = [
            'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
            'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER',
        ];

        $created = 0;
        $restored = 0;
        $updated = 0;

        foreach ($months as $index => $month) {
            $name = 'SPP '.$month;
            $code = 'spp_'.Str::lower($month);

            $existing = JenisTagihan::withTrashed()
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->orderBy('id')
                ->first();

            if (! $existing) {
                JenisTagihan::create([
                    'name' => $name,
                    'code' => $code,
                    'description' => 'Tagihan SPP bulan '.$month,
                    'default_amount' => 0,
                    'is_spp' => true,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);
                $created++;
                continue;
            }

            if ($existing->trashed()) {
                $existing->restore();
                $restored++;
            }

            $existing->update([
                'is_spp' => true,
                'is_active' => true,
                'sort_order' => $existing->sort_order ?: ($index + 1),
                'code' => $existing->code ?: $code,
                'description' => $existing->description ?: ('Tagihan SPP bulan '.$month),
            ]);
            $updated++;
        }

        return $this->jsonSuccess(
            "Jenis tagihan SPP bulanan disiapkan. Dibuat {$created}, dipulihkan {$restored}, diperbarui {$updated}."
        );
    }
}
