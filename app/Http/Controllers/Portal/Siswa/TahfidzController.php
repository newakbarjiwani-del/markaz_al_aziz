<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Traits\PortalAccess;
use App\Models\TahfidzAyat;
use App\Models\TahfidzProgress;
use App\Models\TahfidzSurah;
use App\Models\TahfidzTarget;
use App\Services\TahfidzProgressService;
use App\Support\TahfidzProgressStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TahfidzController extends Controller
{
    use PortalAccess;

    public function __construct(private readonly TahfidzProgressService $progressService) {}

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        $surahs = TahfidzSurah::query()->orderBy('number')->get();
        $juzList = TahfidzAyat::query()->select('juz')->distinct()->orderBy('juz')->pluck('juz');
        $targets = TahfidzTarget::query()
            ->with('surah')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('due_date')
            ->limit(10)
            ->get();
        $progress = TahfidzProgress::query()
            ->with('surah')
            ->where('siswa_id', $siswa->id)
            ->orderByDesc('last_reviewed_at')
            ->limit(10)
            ->get();

        return view('portal.siswa.tahfidz.index', [
            'title' => 'Tahfidz',
            'siswa' => $siswa,
            'surahs' => $surahs,
            'juzList' => $juzList,
            'targets' => $targets,
            'progress' => $progress,
            'statuses' => TahfidzProgressStatus::labels(),
        ]);
    }

    public function surah(TahfidzSurah $surah): View
    {
        $siswa = $this->linkedSiswa();
        $ayahs = $surah->ayat()->get();

        return view('portal.siswa.tahfidz.mushaf', [
            'title' => $surah->label(),
            'siswa' => $siswa,
            'heading' => $surah->label(),
            'subheading' => $surah->name_ar,
            'ayahs' => $ayahs,
            'backUrl' => route('portal.siswa.tahfidz.index'),
            'nav' => $this->mushafNav($surah),
        ]);
    }

    public function juz(int $juz): View
    {
        abort_unless($juz >= 1 && $juz <= 30, 404);
        $siswa = $this->linkedSiswa();

        $ayahs = TahfidzAyat::query()
            ->with('surah')
            ->where('juz', $juz)
            ->orderBy('surah_id')
            ->orderBy('ayah_number')
            ->get();

        abort_if($ayahs->isEmpty(), 404);

        return view('portal.siswa.tahfidz.mushaf', [
            'title' => 'Juz '.$juz,
            'siswa' => $siswa,
            'heading' => 'Juz '.$juz,
            'subheading' => $ayahs->count().' ayat (fixture tersedia)',
            'ayahs' => $ayahs,
            'backUrl' => route('portal.siswa.tahfidz.index'),
            'nav' => [
                'prev' => $juz > 1 ? route('portal.siswa.tahfidz.juz', $juz - 1) : null,
                'next' => $juz < 30 ? route('portal.siswa.tahfidz.juz', $juz + 1) : null,
            ],
        ]);
    }

    public function page(int $page): View
    {
        abort_unless($page >= 1, 404);
        $siswa = $this->linkedSiswa();

        $ayahs = TahfidzAyat::query()
            ->with('surah')
            ->where('page', $page)
            ->orderBy('surah_id')
            ->orderBy('ayah_number')
            ->get();

        abort_if($ayahs->isEmpty(), 404);

        $maxPage = (int) TahfidzAyat::query()->max('page');

        return view('portal.siswa.tahfidz.mushaf', [
            'title' => 'Halaman '.$page,
            'siswa' => $siswa,
            'heading' => 'Halaman mushaf '.$page,
            'subheading' => null,
            'ayahs' => $ayahs,
            'backUrl' => route('portal.siswa.tahfidz.index'),
            'nav' => [
                'prev' => $page > 1 ? route('portal.siswa.tahfidz.page', $page - 1) : null,
                'next' => $page < $maxPage ? route('portal.siswa.tahfidz.page', $page + 1) : null,
            ],
        ]);
    }

    public function storeProgress(Request $request): RedirectResponse
    {
        $siswa = $this->linkedSiswa();

        $data = $request->validate([
            'surah_id' => ['required', Rule::exists('tahfidz_surah', 'id')],
            'ayah_from' => ['required', 'integer', 'min:1'],
            'ayah_to' => ['required', 'integer', 'min:1', 'gte:ayah_from'],
            'status' => ['required', Rule::in(TahfidzProgressStatus::values())],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->progressService->upsert([
            'siswa_id' => $siswa->id,
            'sekolah_id' => $siswa->sekolah_id,
            'surah_id' => (int) $data['surah_id'],
            'ayah_from' => (int) $data['ayah_from'],
            'ayah_to' => (int) $data['ayah_to'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'source' => 'siswa',
            'verified' => false,
            'actor_id' => $request->user()?->id,
        ]);

        return redirect()
            ->route('portal.siswa.tahfidz.index')
            ->with('success', 'Progress hafalan disimpan.');
    }

    /**
     * @return array{prev: ?string, next: ?string}
     */
    private function mushafNav(TahfidzSurah $surah): array
    {
        $prev = TahfidzSurah::query()->where('number', '<', $surah->number)->orderByDesc('number')->first();
        $next = TahfidzSurah::query()->where('number', '>', $surah->number)->orderBy('number')->first();

        return [
            'prev' => $prev ? route('portal.siswa.tahfidz.surah', $prev) : null,
            'next' => $next ? route('portal.siswa.tahfidz.surah', $next) : null,
        ];
    }
}
