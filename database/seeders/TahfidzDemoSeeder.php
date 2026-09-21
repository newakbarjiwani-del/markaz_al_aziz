<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Siswa;
use App\Models\TahfidzHalaqoh;
use App\Models\TahfidzHalaqohAnggota;
use App\Models\TahfidzJadwal;
use App\Models\TahfidzProgram;
use App\Models\TahfidzProgress;
use App\Models\TahfidzSurah;
use App\Models\TahfidzTarget;
use App\Models\User;
use App\Services\TahfidzProgressService;
use App\Services\TahfidzRekapService;
use App\Support\TahfidzProgressStatus;
use App\Support\TahfidzRekapStatus;
use Illuminate\Database\Seeder;

class TahfidzDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TahfidzQuranSeeder::class);

        $siswa = Siswa::query()->orderBy('id')->first();
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first();
        $fatihah = TahfidzSurah::query()->where('number', 1)->first();
        $ikhlas = TahfidzSurah::query()->where('number', 112)->first();

        if (! $siswa || ! $fatihah) {
            return;
        }

        TahfidzTarget::query()->updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'surah_id' => $fatihah->id,
                'ayah_from' => 1,
                'ayah_to' => 7,
            ],
            [
                'sekolah_id' => $siswa->sekolah_id,
                'range_type' => 'ayat',
                'juz' => null,
                'period' => 'weekly',
                'due_date' => now()->addDays(7)->toDateString(),
                'assigned_by' => $admin?->id,
                'note' => 'Demo target Al-Fatihah',
            ],
        );

        if ($ikhlas) {
            TahfidzTarget::query()->updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'juz' => 30,
                    'range_type' => 'juz',
                ],
                [
                    'sekolah_id' => $siswa->sekolah_id,
                    'surah_id' => null,
                    'ayah_from' => null,
                    'ayah_to' => null,
                    'period' => 'daily',
                    'due_date' => now()->addDay()->toDateString(),
                    'assigned_by' => $admin?->id,
                    'note' => 'Demo target Juz 30',
                ],
            );
        }

        if (TahfidzProgress::query()->where('siswa_id', $siswa->id)->doesntExist()) {
            app(TahfidzProgressService::class)->upsert([
                'siswa_id' => $siswa->id,
                'sekolah_id' => $siswa->sekolah_id,
                'surah_id' => $fatihah->id,
                'ayah_from' => 1,
                'ayah_to' => 7,
                'status' => TahfidzProgressStatus::PROSES,
                'note' => 'Demo murajaah awal',
                'source' => 'guru',
                'verified' => false,
                'actor_id' => $admin?->id,
            ]);
        }

        $this->seedHalaqoh($siswa, $admin);
    }

    private function seedHalaqoh(Siswa $siswa, ?User $admin): void
    {
        $gurus = Guru::query()->orderBy('id')->limit(2)->get();
        if ($gurus->isEmpty()) {
            return;
        }

        $program = TahfidzProgram::query()->updateOrCreate(
            [
                'sekolah_id' => $siswa->sekolah_id,
                'name' => 'ITQON',
                'angkatan' => 6,
            ],
            [
                'peserta_label' => 'SANTRIWATI',
                'is_active' => true,
            ],
        );

        $siswaList = Siswa::query()
            ->where('sekolah_id', $siswa->sekolah_id)
            ->orderBy('id')
            ->limit(4)
            ->get();

        foreach ($gurus as $index => $guru) {
            $halaqoh = TahfidzHalaqoh::query()->updateOrCreate(
                [
                    'program_id' => $program->id,
                    'guru_id' => $guru->id,
                ],
                [
                    'sekolah_id' => $siswa->sekolah_id,
                    'name' => null,
                ],
            );

            TahfidzJadwal::query()->updateOrCreate(
                [
                    'halaqoh_id' => $halaqoh->id,
                    'day_of_week' => 1,
                    'time_start' => '07:00:00',
                ],
                [
                    'time_end' => '09:00:00',
                    'is_active' => true,
                ],
            );

            $members = $siswaList->slice($index * 2, 2);
            if ($members->isEmpty()) {
                $members = collect([$siswa]);
            }

            foreach ($members as $member) {
                TahfidzHalaqohAnggota::query()->updateOrCreate(
                    [
                        'halaqoh_id' => $halaqoh->id,
                        'siswa_id' => $member->id,
                    ],
                    ['total_juz' => 5],
                );
            }
        }

        $rekap = app(TahfidzRekapService::class)->ensureForPeriod(
            $program,
            '2026-09-12',
            '2026-09-17',
            $admin?->id,
        );

        $first = $rekap->baris()->with('siswa')->first();
        if ($first) {
            app(TahfidzRekapService::class)->saveBaris($first, [
                'tatsbit_juz' => '1,2,4',
                'murojaah_juz' => '6,7,8,9,10',
                'hadir_hari' => 4,
                'sakit_hari' => 0,
                'pulang_hari' => 0,
                'total_juz' => 5,
                'prestasi' => "Tasmi' 5 juz sekali duduk (1-5)",
            ]);
        }

        $rekap->update(['status' => TahfidzRekapStatus::SIAP]);
    }
}
