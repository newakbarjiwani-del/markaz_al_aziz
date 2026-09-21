<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tahfidz\BuildTahfidzRekapWaRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\TahfidzRekap;
use App\Models\TahfidzRekapSiswa;
use App\Services\TahfidzRekapComposer;
use App\Services\TahfidzRekapWhatsAppService;
use App\Support\ActionMessage;
use App\Support\AdminSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KirimRekapWaController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly TahfidzRekapWhatsAppService $whatsAppService,
        private readonly TahfidzRekapComposer $composer,
    ) {}

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        $rekaps = TahfidzRekap::query()->with('program')->orderByDesc('starts_on');
        AdminSchoolScope::apply($rekaps);

        return view('admin.tahfidz.kirim-wa.index', [
            'title' => 'Kirim Rekap WhatsApp',
            'rekaps' => $rekaps->limit(50)->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('tahfidz.view');

        $query = TahfidzRekapSiswa::query()
            ->with(['siswa.orangTua', 'rekap.program', 'halaqoh.guru'])
            ->when($request->filled('rekap_id'), fn ($q) => $q->where('rekap_id', $request->integer('rekap_id')));

        AdminSchoolScope::applyRelation($query, 'rekap');

        return $this->datatableResponse($request, $query, [
            'searchable' => ['siswa.name', 'siswa.nis'],
            'orderable' => [null, null, null, null, null],
        ], function (TahfidzRekapSiswa $row) {
            $phone = $this->whatsAppService->resolvePhone($row->siswa);
            $hasPhone = $phone !== null;
            $phoneHint = $hasPhone
                ? '<span class="text-xs text-emerald-700">WA wali tersedia</span>'
                : '<span class="text-xs text-slate-500">Nomor WA wali tidak ada</span>';

            $button = $hasPhone
                ? '<button type="button" class="btn-primary text-sm tahfidz-wa-open" data-rekap-siswa-id="'.e((string) $row->id).'">Buka WhatsApp</button>'
                : '<span class="text-xs text-slate-400">—</span>';

            return [
                e($row->siswa?->nis ?? '-'),
                $this->cell(
                    e($row->siswa?->name ?? '-').'<div>'.$phoneHint.'</div>',
                    $row->siswa?->name,
                    'html'
                ),
                e($row->halaqoh?->displayName() ?? '-'),
                e($row->rekap?->periodLabel() ?? '-'),
                $this->cell($button, $row->id, 'html'),
            ];
        });
    }

    public function build(BuildTahfidzRekapWaRequest $request): JsonResponse
    {
        $row = TahfidzRekapSiswa::query()->findOrFail($request->integer('rekap_siswa_id'));
        $this->authorizeRekap($row->rekap);
        $payload = $this->whatsAppService->build($row);

        return $this->jsonSuccess(
            ActionMessage::withSubject('Pesan WhatsApp siap dikirim', $payload['siswa_name']),
            $payload
        );
    }

    public function preview(TahfidzRekap $rekap): JsonResponse
    {
        $this->authorize('tahfidz.view');
        $this->authorizeRekap($rekap);

        return $this->jsonSuccess('Preview rekap lengkap.', [
            'message' => $this->composer->fullMessage($rekap),
        ]);
    }

    private function authorizeRekap(?TahfidzRekap $rekap): void
    {
        abort_unless($rekap, 404);
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $rekap->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
