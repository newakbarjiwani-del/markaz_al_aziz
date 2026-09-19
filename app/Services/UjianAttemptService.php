<?php

namespace App\Services;

use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\UjianAttempt;
use App\Models\UjianJawaban;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UjianAttemptService
{
    public function start(Ujian $ujian, Siswa $siswa): UjianAttempt
    {
        $this->assertCanTake($ujian, $siswa);

        $inProgress = UjianAttempt::query()
            ->where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', UjianAttempt::STATUS_IN_PROGRESS)
            ->first();

        if ($inProgress) {
            return $inProgress->load(['ujian.soal', 'jawaban']);
        }

        $submittedCount = UjianAttempt::query()
            ->where('ujian_id', $ujian->id)
            ->where('siswa_id', $siswa->id)
            ->where('status', UjianAttempt::STATUS_SUBMITTED)
            ->count();

        if ($submittedCount >= (int) $ujian->max_attempts) {
            throw ValidationException::withMessages([
                'attempt' => 'Batas percobaan ujian sudah tercapai.',
            ]);
        }

        return UjianAttempt::query()->create([
            'ujian_id' => $ujian->id,
            'siswa_id' => $siswa->id,
            'started_at' => now(),
            'status' => UjianAttempt::STATUS_IN_PROGRESS,
        ])->load(['ujian.soal', 'jawaban']);
    }

    /**
     * @param  array<int|string, mixed>  $answers  map of ujian_soal_id => jawaban
     */
    public function submit(UjianAttempt $attempt, array $answers): UjianAttempt
    {
        if ($attempt->isSubmitted()) {
            throw ValidationException::withMessages([
                'attempt' => 'Percobaan ini sudah dikumpulkan.',
            ]);
        }

        $ujian = $attempt->ujian()->with('soal')->firstOrFail();
        $siswa = $attempt->siswa;

        if (! $siswa || ! $ujian->allowsSiswa($siswa)) {
            throw new HttpException(403, 'Anda tidak berhak mengumpulkan ujian ini.');
        }

        if (! $ujian->isPublished() || ! $ujian->isWithinWindow()) {
            throw ValidationException::withMessages([
                'ujian' => 'Ujian tidak dapat dikumpulkan di luar jadwal atau status.',
            ]);
        }

        return DB::transaction(function () use ($attempt, $ujian, $answers) {
            $skorMcq = 0.0;
            $skorMaxMcq = 0.0;

            foreach ($ujian->soal as $soal) {
                $raw = $answers[$soal->id] ?? $answers[(string) $soal->id] ?? null;
                $jawabanText = is_scalar($raw) ? trim((string) $raw) : '';

                $isBenar = null;
                $poinDidapat = null;

                if ($soal->isPilihanGanda()) {
                    $skorMaxMcq += (float) $soal->poin;
                    $isBenar = $jawabanText !== '' && strcasecmp($jawabanText, (string) $soal->kunci) === 0;
                    $poinDidapat = $isBenar ? (float) $soal->poin : 0.0;
                    $skorMcq += $poinDidapat;
                }

                UjianJawaban::query()->updateOrCreate(
                    [
                        'attempt_id' => $attempt->id,
                        'ujian_soal_id' => $soal->id,
                    ],
                    [
                        'jawaban' => $jawabanText !== '' ? $jawabanText : null,
                        'is_benar' => $isBenar,
                        'poin_didapat' => $poinDidapat,
                    ],
                );
            }

            $attempt->update([
                'status' => UjianAttempt::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'skor_mcq' => round($skorMcq, 2),
                'skor_max_mcq' => round($skorMaxMcq, 2),
            ]);

            return $attempt->fresh(['ujian.soal', 'jawaban.soal', 'siswa']);
        });
    }

    public function assertCanTake(Ujian $ujian, Siswa $siswa): void
    {
        if (! $ujian->isPublished()) {
            throw new HttpException(403, 'Ujian belum dipublikasikan.');
        }

        if (! $ujian->allowsSiswa($siswa)) {
            throw new HttpException(403, 'Ujian tidak tersedia untuk kelas Anda.');
        }

        if (! $ujian->isWithinWindow()) {
            throw ValidationException::withMessages([
                'ujian' => 'Ujian di luar jadwal pembukaan.',
            ]);
        }

        if ($ujian->soal()->doesntExist()) {
            throw ValidationException::withMessages([
                'ujian' => 'Ujian belum memiliki soal.',
            ]);
        }
    }
}
