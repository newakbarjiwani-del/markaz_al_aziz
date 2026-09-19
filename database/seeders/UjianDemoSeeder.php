<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use App\Models\Ujian;
use App\Models\UjianSoal;
use App\Support\AkademikSemester;
use Illuminate\Database\Seeder;

class UjianDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = TahunAkademik::query()->where('is_active', true)->orderBy('id')->first()
            ?? TahunAkademik::query()->orderByDesc('id')->first();

        $sekolah = Sekolah::query()->where('is_active', true)->orderBy('id')->first()
            ?? Sekolah::query()->orderBy('id')->first();

        if (! $tahun || ! $sekolah) {
            return;
        }

        $kelas = Kelas::query()
            ->where('sekolah_id', $sekolah->id)
            ->orderBy('id')
            ->first();

        $mapel = MataPelajaran::query()
            ->where(function ($q) use ($sekolah): void {
                $q->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolah->id);
            })
            ->orderBy('id')
            ->first();

        $ujian = Ujian::query()->updateOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'tahun_akademik_id' => $tahun->id,
                'title' => 'UTS Matematika Demo',
            ],
            [
                'semester' => AkademikSemester::GANJIL,
                'mata_pelajaran_id' => $mapel?->id,
                'kelas_id' => $kelas?->id,
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addDays(14),
                'duration_minutes' => 60,
                'status' => Ujian::STATUS_PUBLISHED,
                'max_attempts' => 1,
            ],
        );

        if ($ujian->soal()->doesntExist()) {
            $ujian->soal()->create([
                'sort_order' => 1,
                'jenis' => UjianSoal::JENIS_PILIHAN_GANDA,
                'pertanyaan' => 'Berapakah hasil 2 + 2?',
                'poin' => 10,
                'opsi' => [
                    ['key' => 'A', 'label' => '3'],
                    ['key' => 'B', 'label' => '4'],
                    ['key' => 'C', 'label' => '5'],
                    ['key' => 'D', 'label' => '22'],
                ],
                'kunci' => 'B',
            ]);

            $ujian->soal()->create([
                'sort_order' => 2,
                'jenis' => UjianSoal::JENIS_ESSAY,
                'pertanyaan' => 'Jelaskan singkat pengertian bilangan genap.',
                'poin' => 5,
                'opsi' => null,
                'kunci' => null,
            ]);
        }

        // Touch siswa so demo has a target class occupant when present.
        if ($kelas) {
            Siswa::query()->where('kelas_id', $kelas->id)->orderBy('id')->first();
        }
    }
}
