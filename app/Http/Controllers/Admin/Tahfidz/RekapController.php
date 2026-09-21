<?php

namespace App\Http\Controllers\Admin\Tahfidz;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tahfidz\StoreTahfidzRekapRequest;
use App\Http\Requests\Tahfidz\UpdateTahfidzRekapSiswaRequest;
use App\Http\Traits\DataTableTrait;
use App\Models\TahfidzProgram;
use App\Models\TahfidzRekap;
use App\Models\TahfidzRekapSiswa;
use App\Services\TahfidzRekapComposer;
use App\Services\TahfidzRekapService;
use App\Support\AdminSchoolScope;
use App\Support\TahfidzKehadiranStatus;
use App\Support\TahfidzRekapStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RekapController extends Controller
{
    use DataTableTrait;

    public function __construct(
        private readonly TahfidzRekapService $rekapService,
        private readonly TahfidzRekapComposer $composer,
    ) {}

    public function index(): View
    {
        $this->authorize('tahfidz.view');

        $programs = TahfidzProgram::query()->orderBy('name');
        AdminSchoolScope::apply($programs);

        return view('admin.tahfidz.rekap.index', [
            'title' => 'Rekap Mingguan',
            'programs' => $programs->get(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('tahfidz.view');

        $query = TahfidzRekap::query()
            ->with('program')
            ->withCount('baris')
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->integer('program_id')));

        AdminSchoolScope::apply($query);

        return $this->datatableResponse($request, $query, [
            'searchable' => ['program.name'],
            'orderable' => [null, 'starts_on', 'status', 'baris_count', null],
        ], function (TahfidzRekap $rekap) {
            return [
                e($rekap->program?->label() ?? '-'),
                e($rekap->periodLabel()),
                e($rekap->statusLabel()),
                (string) $rekap->baris_count,
                $this->cell(
                    '<a href="'.e(route('admin.tahfidz.rekap.show', $rekap)).'" class="btn-secondary text-sm">Isi rekap</a>',
                    $rekap->id,
                    'html'
                ),
            ];
        });
    }

    public function store(StoreTahfidzRekapRequest $request): JsonResponse|RedirectResponse
    {
        $program = TahfidzProgram::query()->findOrFail($request->integer('program_id'));
        $this->authorizeProgram($program);

        $rekap = $this->rekapService->ensureForPeriod(
            $program,
            $request->input('starts_on'),
            $request->input('ends_on'),
            $request->user()?->id,
        );

        if ($request->wantsJson()) {
            return $this->jsonSuccess('Rekap mingguan siap diisi.', [
                'id' => $rekap->id,
                'show_url' => route('admin.tahfidz.rekap.show', $rekap),
            ], 201);
        }

        return redirect()
            ->route('admin.tahfidz.rekap.show', $rekap)
            ->with('success', 'Rekap mingguan siap diisi.');
    }

    public function show(TahfidzRekap $rekap): View
    {
        $this->authorize('tahfidz.view');
        $this->authorizeRekap($rekap);

        $rekap->load(['program', 'baris.siswa', 'baris.halaqoh.guru', 'baris.halaqoh.jadwal']);
        $this->rekapService->syncAnggota($rekap);
        $rekap->load(['baris.siswa', 'baris.halaqoh.guru', 'baris.halaqoh.jadwal']);

        $sessionDates = [];
        foreach ($rekap->baris->pluck('halaqoh')->unique('id')->filter() as $halaqoh) {
            $sessionDates[$halaqoh->id] = $this->rekapService->sessionDates($rekap, $halaqoh);
        }

        $grouped = $rekap->baris
            ->sortBy(fn (TahfidzRekapSiswa $row) => $row->halaqoh?->displayName().' '.$row->siswa?->name)
            ->groupBy('halaqoh_id');

        return view('admin.tahfidz.rekap.show', [
            'title' => $rekap->program?->title() ?? 'Rekap Mingguan',
            'rekap' => $rekap,
            'grouped' => $grouped,
            'sessionDates' => $sessionDates,
            'kehadiran' => TahfidzKehadiranStatus::labels(),
            'preview' => $this->composer->fullMessage($rekap),
        ]);
    }

    public function updateBaris(UpdateTahfidzRekapSiswaRequest $request, TahfidzRekap $rekap, TahfidzRekapSiswa $tahfidzRekapSiswa): JsonResponse
    {
        $this->authorizeRekap($rekap);
        abort_unless($tahfidzRekapSiswa->rekap_id === $rekap->id, 404);

        $row = $this->rekapService->saveBaris($tahfidzRekapSiswa, $request->validated());

        return $this->jsonSuccess('Rekap siswa disimpan.', $row);
    }

    public function markSiap(Request $request, TahfidzRekap $rekap): RedirectResponse
    {
        $this->authorize('tahfidz.update');
        $this->authorizeRekap($rekap);
        $rekap->update(['status' => TahfidzRekapStatus::SIAP]);

        return redirect()
            ->route('admin.tahfidz.rekap.show', $rekap)
            ->with('success', 'Rekap ditandai siap dikirim.');
    }

    private function authorizeRekap(TahfidzRekap $rekap): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $rekap->sekolah_id !== $scoped) {
            abort(403);
        }
    }

    private function authorizeProgram(TahfidzProgram $program): void
    {
        $scoped = AdminSchoolScope::operatorSekolahId();
        if ($scoped !== null && (int) $program->sekolah_id !== $scoped) {
            abort(403);
        }
    }
}
