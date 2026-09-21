<?php

namespace App\Http\Controllers\Portal\Guru;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\Siswa;
use App\Models\TahfidzHalaqoh;
use App\Models\TahfidzProgram;
use App\Models\TahfidzProgress;
use App\Models\TahfidzRekap;
use App\Models\TahfidzRekapSiswa;
use App\Models\TahfidzSurah;
use App\Services\TahfidzProgressService;
use App\Services\TahfidzRekapService;
use App\Support\TahfidzKehadiranStatus;
use App\Support\TahfidzProgressStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TahfidzController extends Controller
{
    use PortalAccess;

    public function __construct(
        private readonly TahfidzProgressService $progressService,
        private readonly TahfidzRekapService $rekapService,
    ) {}

    public function index(): View
    {
        $guru = $this->linkedGuru();

        $progress = TahfidzProgress::query()
            ->with(['siswa', 'surah'])
            ->orderByDesc('last_reviewed_at')
            ->limit(50)
            ->get();

        return view('portal.guru.tahfidz.index', [
            'title' => 'Tahfidz',
            'progress' => $progress,
            'surahs' => TahfidzSurah::query()->orderBy('number')->get(),
            'statuses' => TahfidzProgressStatus::labels(),
            'halaqoh' => $this->guruHalaqoh($guru->id),
        ]);
    }

    public function storeProgress(Request $request): RedirectResponse
    {
        $this->linkedGuru();

        $data = $request->validate([
            'siswa_id' => ['required', 'exists:siswa,id'],
            'surah_id' => ['required', Rule::exists('tahfidz_surah', 'id')],
            'ayah_from' => ['required', 'integer', 'min:1'],
            'ayah_to' => ['required', 'integer', 'min:1', 'gte:ayah_from'],
            'status' => ['required', Rule::in(TahfidzProgressStatus::values())],
            'note' => ['nullable', 'string', 'max:2000'],
            'verified' => ['nullable', 'boolean'],
        ]);

        $siswa = Siswa::query()->findOrFail($data['siswa_id']);

        $this->progressService->upsert([
            'siswa_id' => $siswa->id,
            'sekolah_id' => $siswa->sekolah_id,
            'surah_id' => (int) $data['surah_id'],
            'ayah_from' => (int) $data['ayah_from'],
            'ayah_to' => (int) $data['ayah_to'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'source' => 'guru',
            'verified' => $request->boolean('verified'),
            'actor_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('portal.guru.tahfidz.index')
            ->with('success', 'Progress siswa diperbarui.');
    }

    public function rekapIndex(): View
    {
        $guru = $this->linkedGuru();
        $halaqoh = $this->guruHalaqoh($guru->id);
        $programIds = $halaqoh->pluck('program_id')->unique()->filter();

        $rekaps = TahfidzRekap::query()
            ->with('program')
            ->whereIn('program_id', $programIds)
            ->orderByDesc('starts_on')
            ->limit(20)
            ->get();

        return view('portal.guru.tahfidz.rekap-index', [
            'title' => 'Rekap Halaqoh',
            'halaqoh' => $halaqoh,
            'programs' => TahfidzProgram::query()->whereIn('id', $programIds)->orderBy('name')->get(),
            'rekaps' => $rekaps,
        ]);
    }

    public function rekapStore(Request $request): RedirectResponse
    {
        $guru = $this->linkedGuru();
        $halaqoh = $this->guruHalaqoh($guru->id);
        $programIds = $halaqoh->pluck('program_id')->all();

        $data = $request->validate([
            'program_id' => ['required', Rule::in($programIds)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);

        $program = TahfidzProgram::query()->findOrFail($data['program_id']);
        $rekap = $this->rekapService->ensureForPeriod(
            $program,
            $data['starts_on'],
            $data['ends_on'],
            $request->user()?->id,
        );

        return redirect()
            ->route('portal.guru.tahfidz.rekap.show', $rekap)
            ->with('success', 'Rekap mingguan siap diisi.');
    }

    public function rekapShow(TahfidzRekap $rekap): View
    {
        $guru = $this->linkedGuru();
        $halaqohIds = $this->guruHalaqoh($guru->id)->pluck('id');
        abort_unless($this->guruOwnsRekap($guru->id, $rekap), 403);

        $this->rekapService->syncAnggota($rekap);
        $rekap->load(['program', 'baris.siswa', 'baris.halaqoh.guru', 'baris.halaqoh.jadwal']);

        $rows = $rekap->baris->whereIn('halaqoh_id', $halaqohIds);
        $sessionDates = [];
        foreach ($rows->pluck('halaqoh')->unique('id')->filter() as $halaqoh) {
            $sessionDates[$halaqoh->id] = $this->rekapService->sessionDates($rekap, $halaqoh);
        }

        return view('portal.guru.tahfidz.rekap-show', [
            'title' => $rekap->program?->title() ?? 'Rekap Halaqoh',
            'rekap' => $rekap,
            'rows' => $rows,
            'sessionDates' => $sessionDates,
            'kehadiran' => TahfidzKehadiranStatus::labels(),
        ]);
    }

    public function rekapUpdateBaris(Request $request, TahfidzRekap $rekap, TahfidzRekapSiswa $tahfidzRekapSiswa): RedirectResponse
    {
        $guru = $this->linkedGuru();
        abort_unless($tahfidzRekapSiswa->rekap_id === $rekap->id, 404);
        abort_unless((int) $tahfidzRekapSiswa->halaqoh()->value('guru_id') === (int) $guru->id, 403);

        $data = $request->validate([
            'tatsbit_juz' => ['nullable'],
            'murojaah_juz' => ['nullable'],
            'kehadiran_harian' => ['nullable', 'array'],
            'hadir_hari' => ['nullable', 'integer', 'min:0', 'max:31'],
            'sakit_hari' => ['nullable', 'integer', 'min:0', 'max:31'],
            'pulang_hari' => ['nullable', 'integer', 'min:0', 'max:31'],
            'total_juz' => ['nullable', 'integer', 'min:0', 'max:30'],
            'prestasi' => ['nullable', 'string', 'max:255'],
        ]);

        $this->rekapService->saveBaris($tahfidzRekapSiswa, $data);

        return redirect()
            ->route('portal.guru.tahfidz.rekap.show', $rekap)
            ->with('success', 'Rekap '.$tahfidzRekapSiswa->siswa?->name.' disimpan.');
    }

    /**
     * @return Collection<int, TahfidzHalaqoh>
     */
    private function guruHalaqoh(int $guruId): Collection
    {
        return TahfidzHalaqoh::query()
            ->with(['program', 'anggota.siswa'])
            ->where('guru_id', $guruId)
            ->orderBy('id')
            ->get();
    }

    private function guruOwnsRekap(int $guruId, TahfidzRekap $rekap): bool
    {
        return TahfidzHalaqoh::query()
            ->where('guru_id', $guruId)
            ->where('program_id', $rekap->program_id)
            ->exists();
    }
}
