<?php

namespace App\Http\Controllers\Admin\Spmb;

use App\Http\Controllers\Controller;
use App\Http\Requests\Spmb\AcceptSpmbPendaftarRequest;
use App\Http\Requests\Spmb\RejectSpmbPendaftarRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\Kelas;
use App\Models\SpmbPendaftar;
use App\Services\SpmbAcceptanceService;
use App\Support\ActionMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PendaftarController extends Controller
{
    use DataTableTrait;

    public function index(): View
    {
        $this->authorize('spmb.view');

        return view('admin.spmb.pendaftar', [
            'title' => 'Pendaftar SPMB',
            'statusLabels' => SpmbPendaftar::statusLabels(),
            'kelasList' => Kelas::query()->orderBy('name')->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('spmb.view');

        $query = SpmbPendaftar::query()
            ->with(['periode', 'sekolah', 'siswa'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('periode_id'), fn ($q) => $q->where('spmb_periode_id', $request->integer('periode_id')));

        return $this->datatableResponse($request, $query, [
            'searchable' => ['nomor_pendaftaran', 'name', 'phone', 'parent_name'],
            'orderable' => ['nomor_pendaftaran', 'name', 'status', 'created_at'],
        ], function (SpmbPendaftar $pendaftar) {
            $showUrl = route('admin.spmb.pendaftar.show', $pendaftar);

            return [
                $pendaftar->nomor_pendaftaran,
                $this->cell(
                    '<a href="'.e($showUrl).'" class="font-medium text-primary-700 hover:underline dark:text-primary-300">'.e($pendaftar->name).'</a>',
                    $pendaftar->name,
                    'text'
                ),
                $pendaftar->periode?->name ?? '-',
                $this->badgeCell($pendaftar->statusLabel(), match ($pendaftar->status) {
                    SpmbPendaftar::STATUS_ACCEPTED => 'badge badge-green',
                    SpmbPendaftar::STATUS_VERIFIED => 'badge badge-blue',
                    SpmbPendaftar::STATUS_REJECTED => 'badge badge-red',
                    default => 'badge badge-amber',
                }),
                $this->dateCell($pendaftar->created_at),
                $this->cell(
                    '<a href="'.e($showUrl).'" class="btn-action btn-action-view" title="Detail">'
                    .'<i class="ti ti-eye"></i><span class="btn-action-label">Detail</span></a>',
                    null,
                    'action'
                ),
            ];
        });
    }

    public function show(SpmbPendaftar $pendaftar): View
    {
        $this->authorize('spmb.view');

        $pendaftar->load(['periode', 'sekolah', 'siswa.kelas']);

        return view('admin.spmb.pendaftar-show', [
            'title' => 'Detail Pendaftar — '.$pendaftar->name,
            'pendaftar' => $pendaftar,
            'kelasList' => Kelas::query()
                ->when($pendaftar->sekolah_id, fn ($q) => $q->where('sekolah_id', $pendaftar->sekolah_id))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function verify(SpmbPendaftar $pendaftar): JsonResponse
    {
        $this->authorize('spmb.update');

        if ($pendaftar->status !== SpmbPendaftar::STATUS_SUBMITTED) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pendaftar berstatus diajukan yang dapat diverifikasi.',
            ]);
        }

        $pendaftar->update(['status' => SpmbPendaftar::STATUS_VERIFIED]);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pendaftar berhasil diverifikasi', $pendaftar->name),
            $pendaftar->fresh()
        );
    }

    public function accept(AcceptSpmbPendaftarRequest $request, SpmbPendaftar $pendaftar, SpmbAcceptanceService $service): JsonResponse
    {
        $data = $request->validated();
        $siswa = $service->accept(
            $pendaftar,
            $data['nis'],
            isset($data['kelas_id']) ? (int) $data['kelas_id'] : null
        );

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pendaftar diterima menjadi siswa', $siswa->name),
            $siswa
        );
    }

    public function reject(RejectSpmbPendaftarRequest $request, SpmbPendaftar $pendaftar, SpmbAcceptanceService $service): JsonResponse
    {
        $service->reject($pendaftar, $request->validated('notes'));

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pendaftar ditolak', $pendaftar->name),
            $pendaftar->fresh()
        );
    }
}
