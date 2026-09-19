<?php

namespace App\Http\Controllers\Admin\Spmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spmb\StoreSpmbPeriodeRequest;
use App\Http\Requests\Spmb\UpdateSpmbPeriodeRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\SpmbPeriode;
use App\Models\TahunAkademik;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PeriodeController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('spmb.view');

        return view('admin.spmb.periode', [
            'title' => 'Periode SPMB',
            'schools' => AdminSchoolScope::schools(),
            'tahunAkademik' => TahunAkademik::query()->orderByDesc('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('spmb.view');

        $query = SpmbPeriode::query()
            ->with(['sekolah', 'tahunAkademik'])
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'description'],
            'orderable' => ['name', 'opens_at', 'closes_at', 'is_active'],
        ], function (SpmbPeriode $periode) {
            $fields = array_merge(
                $periode->only(['name', 'sekolah_id', 'tahun_akademik_id', 'description']),
                [
                    'opens_at' => $periode->opens_at?->format('Y-m-d\TH:i'),
                    'closes_at' => $periode->closes_at?->format('Y-m-d\TH:i'),
                    'is_active' => $periode->is_active ? '1' : '0',
                ]
            );

            $actions = [
                'edit' => [
                    'update_url' => route('admin.spmb.periode.update', $periode),
                    'form_target' => 'spmb-periode-form',
                    'modal_target' => 'spmb-periode-modal',
                    'record' => $fields,
                ],
                'delete' => [
                    'url' => route('admin.spmb.periode.destroy', $periode),
                    'confirm_title' => 'Konfirmasi Hapus',
                    'confirm_message' => 'Apakah Anda yakin ingin menghapus periode SPMB ini?',
                    'confirm_detail' => [
                        ['label' => 'Nama', 'value' => $periode->name],
                        ['label' => 'Status', 'value' => $periode->is_active ? 'Aktif' : 'Nonaktif'],
                    ],
                ],
            ];

            return [
                $periode->name,
                $periode->sekolah?->name ?? 'Semua sekolah',
                $periode->tahunAkademik?->name ?? '-',
                $this->dateCell($periode->opens_at),
                $this->dateCell($periode->closes_at),
                $this->badgeCell(
                    $periode->isOpen() ? 'Terbuka' : ($periode->is_active ? 'Aktif (tutup)' : 'Nonaktif'),
                    $periode->isOpen() ? 'badge badge-green' : ($periode->is_active ? 'badge badge-amber' : 'badge badge-red')
                ),
                $this->cell('', ['actions' => $actions], 'action'),
            ];
        });
    }

    public function store(StoreSpmbPeriodeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isActive = $request->boolean('is_active');
        $data['sekolah_id'] = $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null;
        $data['tahun_akademik_id'] = $request->filled('tahun_akademik_id') ? $request->integer('tahun_akademik_id') : null;

        $periode = DB::transaction(function () use ($data, $isActive) {
            if ($isActive) {
                SpmbPeriode::query()->update(['is_active' => false]);
            }

            return SpmbPeriode::create([
                ...$data,
                'is_active' => $isActive,
            ]);
        });

        return $this->jsonSuccess('Periode SPMB berhasil ditambahkan.', $periode, 201);
    }

    public function update(UpdateSpmbPeriodeRequest $request, SpmbPeriode $periode): JsonResponse
    {
        $data = $request->validated();
        $isActive = $request->boolean('is_active');
        $data['sekolah_id'] = $request->filled('sekolah_id') ? $request->integer('sekolah_id') : null;
        $data['tahun_akademik_id'] = $request->filled('tahun_akademik_id') ? $request->integer('tahun_akademik_id') : null;

        DB::transaction(function () use ($periode, $data, $isActive): void {
            if ($isActive) {
                SpmbPeriode::query()
                    ->where('id', '!=', $periode->id)
                    ->update(['is_active' => false]);
            }

            $periode->update([
                ...$data,
                'is_active' => $isActive,
            ]);
        });

        return $this->jsonSuccess('Periode SPMB berhasil diperbarui.', $periode->fresh());
    }

    public function destroy(SpmbPeriode $periode): JsonResponse
    {
        $this->authorize('spmb.delete');

        $name = $periode->name;
        $periode->delete();

        return $this->jsonSuccess('Periode SPMB "'.$name.'" berhasil dihapus.');
    }
}
