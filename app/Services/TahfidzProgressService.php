<?php

namespace App\Services;

use App\Models\TahfidzMurajaahLog;
use App\Models\TahfidzProgress;
use App\Support\TahfidzProgressStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TahfidzProgressService
{
    /**
     * @param  array{
     *     siswa_id: int,
     *     sekolah_id?: int|null,
     *     surah_id: int,
     *     ayah_from: int,
     *     ayah_to: int,
     *     status: string,
     *     note?: string|null,
     *     source: string,
     *     verified?: bool,
     *     actor_id?: int|null
     * }  $data
     */
    public function upsert(array $data): TahfidzProgress
    {
        if ($data['ayah_from'] > $data['ayah_to']) {
            throw ValidationException::withMessages([
                'ayah_to' => 'Ayat akhir harus sama atau setelah ayat awal.',
            ]);
        }

        if (! in_array($data['status'], TahfidzProgressStatus::values(), true)) {
            throw ValidationException::withMessages([
                'status' => 'Status hafalan tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $progress = TahfidzProgress::query()->firstOrNew([
                'siswa_id' => $data['siswa_id'],
                'surah_id' => $data['surah_id'],
                'ayah_from' => $data['ayah_from'],
                'ayah_to' => $data['ayah_to'],
            ]);

            $progress->sekolah_id = $data['sekolah_id'] ?? $progress->sekolah_id;
            $progress->status = $data['status'];
            $progress->last_reviewed_at = now();

            if (($data['verified'] ?? false) === true) {
                $progress->verified_by = $data['actor_id'] ?? null;
            }

            $progress->save();

            TahfidzMurajaahLog::query()->create([
                'progress_id' => $progress->id,
                'reviewed_at' => now(),
                'note' => $data['note'] ?? null,
                'source' => $data['source'],
                'created_by' => $data['actor_id'] ?? null,
            ]);

            return $progress->fresh(['surah', 'siswa', 'verifiedBy']);
        });
    }
}
