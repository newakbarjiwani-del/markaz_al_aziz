<?php

namespace Database\Seeders;

use App\Models\Siswa;
use App\Models\TahfidzProgress;
use App\Models\TahfidzSurah;
use App\Models\TahfidzTarget;
use App\Models\User;
use App\Services\TahfidzProgressService;
use App\Support\TahfidzProgressStatus;
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
    }
}
