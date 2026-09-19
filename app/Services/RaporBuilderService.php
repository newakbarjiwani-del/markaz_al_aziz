<?php

namespace App\Services;

use App\Models\NilaiEntry;
use App\Models\Rapor;
use App\Models\RaporMapel;
use App\Models\Siswa;
use App\Support\AkademikSemester;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RaporBuilderService
{
    /**
     * Build or refresh draft rapor lines from averaged nilai_entry scores per mapel.
     */
    public function buildDraft(Siswa $siswa, int $tahunAkademikId, string $semester): Rapor
    {
        if (! in_array($semester, AkademikSemester::values(), true)) {
            throw ValidationException::withMessages([
                'semester' => 'Semester tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($siswa, $tahunAkademikId, $semester) {
            $rapor = Rapor::query()->firstOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_akademik_id' => $tahunAkademikId,
                    'semester' => $semester,
                ],
                [
                    'status' => Rapor::STATUS_DRAFT,
                ],
            );

            if ($rapor->isFinal()) {
                throw ValidationException::withMessages([
                    'status' => 'Rapor sudah final dan tidak dapat dibangun ulang.',
                ]);
            }

            $averages = NilaiEntry::query()
                ->where('siswa_id', $siswa->id)
                ->where('tahun_akademik_id', $tahunAkademikId)
                ->where('semester', $semester)
                ->selectRaw('mata_pelajaran_id, AVG(skor) as avg_skor')
                ->groupBy('mata_pelajaran_id')
                ->get();

            foreach ($averages as $row) {
                $nilai = round((float) $row->avg_skor, 2);
                RaporMapel::query()->updateOrCreate(
                    [
                        'rapor_id' => $rapor->id,
                        'mata_pelajaran_id' => $row->mata_pelajaran_id,
                    ],
                    [
                        'nilai_akhir' => $nilai,
                        'predikat' => $this->predikat($nilai),
                    ],
                );
            }

            return $rapor->fresh(['mapel.mataPelajaran', 'siswa', 'tahunAkademik']);
        });
    }

    public function finalize(Rapor $rapor): Rapor
    {
        if ($rapor->isFinal()) {
            return $rapor;
        }

        $rapor->update([
            'status' => Rapor::STATUS_FINAL,
            'finalized_at' => now(),
        ]);

        return $rapor->fresh(['mapel.mataPelajaran', 'siswa', 'tahunAkademik']);
    }

    private function predikat(float $nilai): string
    {
        return match (true) {
            $nilai >= 90 => 'A',
            $nilai >= 80 => 'B',
            $nilai >= 70 => 'C',
            $nilai >= 60 => 'D',
            default => 'E',
        };
    }
}
