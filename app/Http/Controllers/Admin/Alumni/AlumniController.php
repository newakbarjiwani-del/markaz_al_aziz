<?php

namespace App\Http\Controllers\Admin\Alumni;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alumni\StoreAlumniRequest;
use App\Http\Requests\Alumni\StoreAlumniTracerRequest;
use App\Http\Requests\Alumni\UpdateAlumniRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Alumni;
use App\Models\AlumniTracer;
use App\Services\AlumniTracerService;
use App\Support\AdminSchoolScope;
use App\Support\AlumniTracerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlumniController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('alumni.view');

        return view('admin.alumni.index', [
            'title' => 'Data Alumni',
            'schools' => AdminSchoolScope::schools(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('alumni.view');

        $query = Alumni::query()
            ->with('sekolah')
            ->withCount('tracers')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('angkatan'), fn ($q) => $q->where('angkatan', $request->string('angkatan')))
            ->when($request->filled('sekolah_id'), fn ($q) => $q->where('sekolah_id', $request->integer('sekolah_id')));

        AdminSchoolScope::applyWithGlobal($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['name', 'nis', 'email', 'phone', 'angkatan'],
            'orderable' => ['name', 'angkatan', 'is_active'],
        ], function (Alumni $alumni) {
            $fields = array_merge(
                $alumni->only(['name', 'nis', 'angkatan', 'phone', 'email', 'address', 'notes', 'sekolah_id', 'siswa_id']),
                ['is_active' => $alumni->is_active ? '1' : '0']
            );

            $showUrl = route('admin.alumni.alumni.show', $alumni);

            return [
                $this->cell(
                    '<a href="'.e($showUrl).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($alumni->name).'</a>',
                    $alumni->name,
                    'text'
                ),
                $alumni->nis ?: '-',
                $alumni->angkatan ?: '-',
                $alumni->sekolah?->name ?? 'Semua sekolah',
                $alumni->tracers_count,
                $this->badgeCell(
                    $alumni->is_active ? 'Aktif' : 'Nonaktif',
                    $alumni->is_active ? 'badge badge-green' : 'badge badge-red'
                ),
                $this->cell('', [
                    'actions' => [
                        'edit' => [
                            'update_url' => route('admin.alumni.alumni.update', $alumni),
                            'form_target' => 'alumni-form',
                            'modal_target' => 'alumni-modal',
                            'record' => $fields,
                        ],
                        'delete' => [
                            'url' => route('admin.alumni.alumni.destroy', $alumni),
                            'confirm_title' => 'Konfirmasi Hapus',
                            'confirm_message' => 'Apakah Anda yakin ingin menghapus alumni ini?',
                            'confirm_detail' => [
                                ['label' => 'Nama', 'value' => $alumni->name],
                                ['label' => 'NIS', 'value' => $alumni->nis ?: '-'],
                            ],
                        ],
                    ],
                ], 'action'),
            ];
        });
    }

    public function store(StoreAlumniRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active', true);

        $alumni = Alumni::create($data);

        return $this->jsonSuccess('Alumni berhasil ditambahkan.', $alumni, 201);
    }

    public function update(UpdateAlumniRequest $request, Alumni $alumni): JsonResponse
    {
        $data = $request->validated();
        $data['sekolah_id'] = AdminSchoolScope::resolveOptionalFromRequest($request);
        $data['is_active'] = $request->boolean('is_active');

        $alumni->update($data);

        return $this->jsonSuccess('Alumni berhasil diperbarui.', $alumni->fresh());
    }

    public function destroy(Alumni $alumni): JsonResponse
    {
        $this->authorize('alumni.delete');

        $name = $alumni->name;
        $alumni->delete();

        return $this->jsonSuccess('Alumni "'.$name.'" berhasil dihapus.');
    }

    public function show(Alumni $alumni): View
    {
        $this->authorize('alumni.view');

        $alumni->load(['sekolah', 'siswa', 'tracers']);

        return view('admin.alumni.show', [
            'title' => $alumni->name,
            'alumni' => $alumni,
            'statusLabels' => AlumniTracerStatus::labels(),
        ]);
    }

    public function storeTracer(StoreAlumniTracerRequest $request, Alumni $alumni, AlumniTracerService $service): JsonResponse
    {
        $data = $request->validated();

        $tracer = $service->submit([
            'alumni_id' => $alumni->id,
            'sekolah_id' => $alumni->sekolah_id,
            'name' => $alumni->name,
            'nis' => $alumni->nis,
            'angkatan' => $alumni->angkatan,
            'phone' => $alumni->phone,
            'email' => $alumni->email,
            'address' => $alumni->address,
            'tahun_tracer' => $data['tahun_tracer'],
            'status_lulusan' => $data['status_lulusan'],
            'institusi' => $data['institusi'] ?? null,
            'jabatan' => $data['jabatan'] ?? null,
            'bidang' => $data['bidang'] ?? null,
            'kota' => $data['kota'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'source' => AlumniTracer::SOURCE_ADMIN,
        ]);

        return $this->jsonSuccess('Respons tracer disimpan.', $tracer, 201);
    }

    public function destroyTracer(Alumni $alumni, AlumniTracer $tracer): JsonResponse
    {
        $this->authorize('alumni.delete');

        if ((int) $tracer->alumni_id !== (int) $alumni->id) {
            abort(404);
        }

        $tracer->delete();

        return $this->jsonSuccess('Respons tracer dihapus.');
    }
}
