<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPelajaranSlot;
use App\Models\KalenderPendidikan;
use App\Models\Kelas;
use App\Models\KompetensiDasar;
use App\Models\Kurikulum;
use App\Models\KurikulumMapel;
use App\Models\MataPelajaran;
use App\Models\NilaiEntry;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use App\Models\User;
use App\Services\RaporBuilderService;
use App\Support\AkademikSemester;
use Illuminate\Database\Seeder;

class AkademikDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = TahunAkademik::query()->where('is_active', true)->orderBy('id')->first()
            ?? TahunAkademik::query()->orderByDesc('id')->first()
            ?? TahunAkademik::query()->create([
                'name' => '2026/2027',
                'is_active' => true,
            ]);

        $sekolah = Sekolah::query()->where('is_active', true)->orderBy('id')->first()
            ?? Sekolah::query()->orderBy('id')->first();

        if (! $sekolah) {
            return;
        }

        $kelas = Kelas::query()
            ->where('sekolah_id', $sekolah->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first()
            ?? Kelas::query()->where('sekolah_id', $sekolah->id)->orderBy('id')->first();

        $guru = Guru::query()->where('sekolah_id', $sekolah->id)->orderBy('id')->first();
        $siswa = $kelas
            ? Siswa::query()->where('kelas_id', $kelas->id)->orderBy('id')->first()
            : null;
        $recorder = User::query()->role('admin')->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();

        $mapelDefs = [
            ['code' => 'MTK', 'name' => 'Matematika', 'kelompok' => 'Umum'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'kelompok' => 'Umum'],
            ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam', 'kelompok' => 'Agama'],
        ];

        $mapels = collect();
        foreach ($mapelDefs as $def) {
            $mapels->push(MataPelajaran::query()->updateOrCreate(
                [
                    'sekolah_id' => $sekolah->id,
                    'code' => $def['code'],
                ],
                [
                    'name' => $def['name'],
                    'kelompok' => $def['kelompok'],
                    'is_active' => true,
                ],
            ));
        }

        $kurikulum = Kurikulum::query()->updateOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'tahun_akademik_id' => $tahun->id,
                'name' => 'Kurikulum Merdeka Demo',
            ],
            [
                'jenjang' => $kelas?->unit,
                'is_active' => true,
                'description' => 'Data demo modul akademik Phase 2.',
            ],
        );

        foreach ($mapels as $index => $mapel) {
            $kurikulumMapel = KurikulumMapel::query()->updateOrCreate(
                [
                    'kurikulum_id' => $kurikulum->id,
                    'mata_pelajaran_id' => $mapel->id,
                    'tingkat' => null,
                ],
                [
                    'jam_mingguan' => 4,
                    'sort_order' => $index + 1,
                ],
            );

            KompetensiDasar::query()->updateOrCreate(
                [
                    'kurikulum_mapel_id' => $kurikulumMapel->id,
                    'kode' => $mapel->code.'.1',
                ],
                [
                    'deskripsi' => 'Memahami konsep dasar '.$mapel->name.'.',
                    'semester' => AkademikSemester::GANJIL,
                    'sort_order' => 1,
                ],
            );
        }

        if ($kelas) {
            $jadwal = JadwalPelajaran::query()->updateOrCreate(
                [
                    'sekolah_id' => $sekolah->id,
                    'tahun_akademik_id' => $tahun->id,
                    'kelas_id' => $kelas->id,
                ],
                [
                    'name' => 'Jadwal '.$kelas->name,
                    'is_active' => true,
                ],
            );

            foreach ($mapels->take(2)->values() as $index => $mapel) {
                JadwalPelajaranSlot::query()->updateOrCreate(
                    [
                        'jadwal_pelajaran_id' => $jadwal->id,
                        'day_of_week' => $index + 1,
                        'time_start' => sprintf('%02d:00:00', 7 + $index),
                        'time_end' => sprintf('%02d:40:00', 7 + $index),
                        'mata_pelajaran_id' => $mapel->id,
                    ],
                    [
                        'guru_id' => $guru?->id,
                        'ruang' => 'R'.($index + 1),
                        'sort_order' => $index + 1,
                    ],
                );
            }
        }

        KalenderPendidikan::query()->updateOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'tahun_akademik_id' => $tahun->id,
                'name' => 'Awal tahun ajaran',
            ],
            [
                'starts_on' => now()->startOfYear()->toDateString(),
                'ends_on' => now()->startOfYear()->addDays(6)->toDateString(),
                'jenis' => KalenderPendidikan::JENIS_KEGIATAN,
                'notes' => 'Masa orientasi / pengenalan lingkungan sekolah.',
            ],
        );

        KalenderPendidikan::query()->updateOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'tahun_akademik_id' => $tahun->id,
                'name' => 'UTS semester ganjil',
            ],
            [
                'starts_on' => now()->addMonths(2)->startOfWeek()->toDateString(),
                'ends_on' => now()->addMonths(2)->startOfWeek()->addDays(4)->toDateString(),
                'jenis' => KalenderPendidikan::JENIS_UJIAN,
                'notes' => null,
            ],
        );

        if (! $siswa || ! $recorder) {
            return;
        }

        foreach ($mapels as $mapel) {
            foreach ([NilaiEntry::JENIS_HARIAN, NilaiEntry::JENIS_UTS] as $jenis) {
                NilaiEntry::query()->updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'mata_pelajaran_id' => $mapel->id,
                        'tahun_akademik_id' => $tahun->id,
                        'semester' => AkademikSemester::GANJIL,
                        'jenis' => $jenis,
                        'kompetensi_dasar_id' => null,
                    ],
                    [
                        'skor' => $jenis === NilaiEntry::JENIS_UTS ? 88 : 82,
                        'catatan' => 'Demo akademik',
                        'recorded_by' => $recorder->id,
                    ],
                );
            }
        }

        $rapor = app(RaporBuilderService::class)->buildDraft(
            $siswa,
            $tahun->id,
            AkademikSemester::GANJIL,
        );
        $rapor->update(['catatan_wali' => 'Pertahankan semangat belajar.']);
        app(RaporBuilderService::class)->finalize($rapor);
    }
}
