<?php

namespace App\Http\Controllers\Admin\Attendance;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Models\Pelajaran;
use App\Support\AdminSchoolScope;
use App\Support\SoftDeleteRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PelajaranController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        return view('admin.absensi.pelajaran', [
            'title' => 'Pelajaran',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Pelajaran::query()
            ->with('sekolah')
            ->when($request->filled('sekolah_id'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->whereNull('sekolah_id')
                        ->orWhere('sekolah_id', $request->sekolah_id);
                });
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', $request->is_active === '1');
            });

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'code'],
            'orderable' => ['name', 'code', 'created_at'],
        ], function (Pelajaran $row) {
            $fields = array_merge(
                $row->only(['code', 'name', 'description', 'is_active']),
                [
                    'sekolah_id' => $row->coversAllSchools() ? 'all' : (string) $row->sekolah_id,
                    'is_active' => $row->is_active ? '1' : '0',
                ]
            );

            return [
                $row->coversAllSchools() ? 'Semua sekolah' : ($row->sekolah?->name ?? '-'),
                $row->code ?: '-',
                $row->name,
                $this->badgeCell($row->is_active ? 'Aktif' : 'Nonaktif', $row->is_active ? 'badge badge-green' : 'badge badge-red'),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.absensi.pelajaran.update', $row),
                            'form_target' => 'pelajaran-form',
                            'modal_target' => 'pelajaran-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.absensi.pelajaran.destroy', $row),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus pelajaran ini?',
                            'confirm_detail' => [
                                ['label' => 'Pelajaran', 'value' => $row->name],
                                ['label' => 'Kode', 'value' => $row->code ?: '-'],
                                ['label' => 'Sekolah', 'value' => $row->coversAllSchools() ? 'Semua sekolah' : ($row->sekolah?->name ?? '-')],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('attendance.create');

        $data = $this->validated($request);

        $pelajaran = Pelajaran::create(array_merge($data, [
            'is_active' => $request->boolean('is_active', true),
        ]));

        return $this->jsonSuccess('Pelajaran berhasil ditambahkan.', $pelajaran, 201);
    }

    public function update(Request $request, Pelajaran $pelajaran): JsonResponse
    {
        $this->authorize('attendance.update');

        $data = $this->validated($request, $pelajaran);

        $pelajaran->update(array_merge($data, [
            'is_active' => $request->boolean('is_active'),
        ]));

        return $this->jsonSuccess('Pelajaran berhasil diperbarui.', $pelajaran);
    }

    public function destroy(Pelajaran $pelajaran): JsonResponse
    {
        $this->authorize('attendance.delete');

        $pelajaran->delete();

        return $this->jsonSuccess('Pelajaran berhasil dihapus.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Pelajaran $pelajaran = null): array
    {
        $scope = $this->resolveScopePayload($request);
        $sekolahId = $scope['sekolah_id'];

        $validated = $request->validate([
            'sekolah_id' => ['nullable'],
            'code' => ['nullable', 'string', 'max:20'],
            'name' => [
                'required',
                'string',
                'max:100',
                SoftDeleteRules::unique('pelajaran', 'name', $pelajaran?->id)
                    ->where(fn ($q) => $sekolahId === null
                        ? $q->whereNull('sekolah_id')
                        : $q->where('sekolah_id', $sekolahId)),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['sekolah_id'] = $sekolahId;

        return $validated;
    }

    /** @return array{sekolah_id: ?int} */
    private function resolveScopePayload(Request $request): array
    {
        $scoped = AdminSchoolScope::operatorSekolahId($request->user());
        if ($scoped !== null) {
            return ['sekolah_id' => $scoped];
        }

        $raw = $request->input('sekolah_id');
        if ($raw === null || $raw === '' || $raw === 'all') {
            return ['sekolah_id' => null];
        }

        $request->validate([
            'sekolah_id' => [SoftDeleteRules::exists('sekolah')],
        ]);

        return ['sekolah_id' => (int) $raw];
    }
}
