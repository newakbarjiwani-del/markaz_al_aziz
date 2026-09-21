<?php

namespace App\Services;

use App\Models\TahfidzHalaqoh;
use App\Models\TahfidzHalaqohAnggota;
use App\Models\TahfidzProgram;
use App\Models\TahfidzRekap;
use App\Models\TahfidzRekapSiswa;
use App\Support\TahfidzJuzList;
use App\Support\TahfidzKehadiranStatus;
use App\Support\TahfidzRekapStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TahfidzRekapService
{
    public function ensureForPeriod(TahfidzProgram $program, string $startsOn, string $endsOn, ?int $createdBy = null): TahfidzRekap
    {
        return DB::transaction(function () use ($program, $startsOn, $endsOn, $createdBy) {
            $rekap = TahfidzRekap::query()->firstOrCreate(
                [
                    'program_id' => $program->id,
                    'starts_on' => $startsOn,
                    'ends_on' => $endsOn,
                ],
                [
                    'sekolah_id' => $program->sekolah_id,
                    'status' => TahfidzRekapStatus::DRAFT,
                    'created_by' => $createdBy,
                ],
            );

            $this->syncAnggota($rekap);

            return $rekap->fresh(['program', 'baris.siswa', 'baris.halaqoh.guru']);
        });
    }

    public function syncAnggota(TahfidzRekap $rekap): void
    {
        $anggota = TahfidzHalaqohAnggota::query()
            ->whereHas('halaqoh', fn ($q) => $q->where('program_id', $rekap->program_id))
            ->with('halaqoh')
            ->get();

        foreach ($anggota as $member) {
            TahfidzRekapSiswa::query()->firstOrCreate(
                [
                    'rekap_id' => $rekap->id,
                    'siswa_id' => $member->siswa_id,
                ],
                [
                    'halaqoh_id' => $member->halaqoh_id,
                    'total_juz' => $member->total_juz,
                    'hadir_hari' => 0,
                    'sakit_hari' => 0,
                    'pulang_hari' => 0,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveBaris(TahfidzRekapSiswa $row, array $data): TahfidzRekapSiswa
    {
        $tatsbit = TahfidzJuzList::parse($data['tatsbit_juz'] ?? []);
        $murojaah = TahfidzJuzList::parse($data['murojaah_juz'] ?? []);
        $harian = $this->normalizeHarian($data['kehadiran_harian'] ?? []);

        if ($harian !== []) {
            $totals = TahfidzKehadiranStatus::totals($harian);
            $hadir = $totals['hadir_hari'];
            $sakit = $totals['sakit_hari'];
            $pulang = $totals['pulang_hari'];
        } else {
            $hadir = (int) ($data['hadir_hari'] ?? 0);
            $sakit = (int) ($data['sakit_hari'] ?? 0);
            $pulang = (int) ($data['pulang_hari'] ?? 0);
        }

        $totalJuz = (int) ($data['total_juz'] ?? $row->total_juz);

        $row->update([
            'tatsbit_juz' => $tatsbit,
            'murojaah_juz' => $murojaah,
            'kehadiran_harian' => $harian === [] ? null : $harian,
            'hadir_hari' => $hadir,
            'sakit_hari' => $sakit,
            'pulang_hari' => $pulang,
            'total_juz' => $totalJuz,
            'prestasi' => $data['prestasi'] ?? null,
        ]);

        TahfidzHalaqohAnggota::query()
            ->where('halaqoh_id', $row->halaqoh_id)
            ->where('siswa_id', $row->siswa_id)
            ->update(['total_juz' => $totalJuz]);

        return $row->fresh(['siswa', 'halaqoh.guru', 'rekap.program']);
    }

    /**
     * @return list<string>
     */
    public function sessionDates(TahfidzRekap $rekap, TahfidzHalaqoh $halaqoh): array
    {
        $days = $halaqoh->jadwal()
            ->where('is_active', true)
            ->pluck('day_of_week')
            ->unique()
            ->all();

        if ($days === []) {
            return [];
        }

        $dates = [];
        $cursor = CarbonImmutable::parse($rekap->starts_on->toDateString());
        $end = CarbonImmutable::parse($rekap->ends_on->toDateString());

        while ($cursor->lte($end)) {
            if (in_array($cursor->dayOfWeekIso, $days, true)) {
                $dates[] = $cursor->toDateString();
            }
            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeHarian(mixed $input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $allowed = TahfidzKehadiranStatus::values();
        $harian = [];

        foreach ($input as $date => $status) {
            if (! is_string($date) || $status === null || $status === '') {
                continue;
            }

            $value = (string) $status;
            if (in_array($value, $allowed, true)) {
                $harian[$date] = $value;
            }
        }

        ksort($harian);

        return $harian;
    }
}
