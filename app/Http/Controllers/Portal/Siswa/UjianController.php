<?php

namespace App\Http\Controllers\Portal\Siswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\SubmitUjianAttemptRequest;
use App\Http\Traits\PortalAccess;
use App\Models\Ujian;
use App\Models\UjianAttempt;
use App\Services\UjianAttemptService;
use App\Support\AkademikSemester;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UjianController extends Controller
{
    use PortalAccess;

    public function index(): View
    {
        $siswa = $this->linkedSiswa();

        $exams = Ujian::query()
            ->with(['mataPelajaran', 'tahunAkademik'])
            ->where('status', Ujian::STATUS_PUBLISHED)
            ->where(function ($q) use ($siswa): void {
                $q->whereNull('kelas_id')->orWhere('kelas_id', $siswa->kelas_id);
            })
            ->where(function ($q) use ($siswa): void {
                $q->whereNull('sekolah_id')->orWhere('sekolah_id', $siswa->sekolah_id);
            })
            ->where(function ($q): void {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('starts_at')
            ->get();

        $attempts = UjianAttempt::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('ujian_id', $exams->pluck('id'))
            ->get()
            ->groupBy('ujian_id');

        return view('portal.siswa.ujian.index', [
            'title' => 'Ujian Online',
            'siswa' => $siswa,
            'exams' => $exams,
            'attempts' => $attempts,
            'semesters' => AkademikSemester::labels(),
        ]);
    }

    public function show(Ujian $ujian): View
    {
        $siswa = $this->linkedSiswa();
        app(UjianAttemptService::class)->assertCanTake($ujian, $siswa);

        $ujian->load(['soal', 'mataPelajaran', 'tahunAkademik']);

        $attempt = UjianAttempt::query()
            ->where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->latest('id')
            ->first();

        if ($attempt?->isSubmitted()) {
            $attempt->load(['jawaban.soal']);

            return view('portal.siswa.ujian.result', [
                'title' => 'Hasil Ujian',
                'ujian' => $ujian,
                'attempt' => $attempt,
                'semesters' => AkademikSemester::labels(),
            ]);
        }

        return view('portal.siswa.ujian.show', [
            'title' => $ujian->title,
            'ujian' => $ujian,
            'attempt' => $attempt,
            'semesters' => AkademikSemester::labels(),
        ]);
    }

    public function start(Ujian $ujian, UjianAttemptService $service): RedirectResponse
    {
        $siswa = $this->linkedSiswa();
        $service->start($ujian, $siswa);

        return redirect()->route('portal.siswa.ujian.show', $ujian);
    }

    public function submit(SubmitUjianAttemptRequest $request, Ujian $ujian, UjianAttemptService $service): RedirectResponse
    {
        $siswa = $this->linkedSiswa();

        $attempt = UjianAttempt::query()
            ->where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', UjianAttempt::STATUS_IN_PROGRESS)
            ->latest('id')
            ->first();

        if (! $attempt) {
            $attempt = $service->start($ujian, $siswa);
        }

        $service->submit($attempt, $request->input('answers', []));

        return redirect()
            ->route('portal.siswa.ujian.show', $ujian)
            ->with('success', 'Jawaban berhasil dikumpulkan.');
    }
}
