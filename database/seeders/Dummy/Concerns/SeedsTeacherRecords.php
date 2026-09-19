<?php

namespace Database\Seeders\Dummy\Concerns;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\KartuGuru;
use App\Models\Kelas;
use App\Models\ProfilGuru;
use App\Models\RiwayatMengajar;
use App\Models\Sekolah;
use Illuminate\Support\Collection;

trait SeedsTeacherRecords
{
    /** @param Collection<int, Kelas> $kelasList */
    protected function seedTeacherWithRecords(
        Sekolah $sekolah,
        Collection $kelasList,
        string $nip,
        string $name,
        string $jabatan,
        string $jenisGuru,
        string $phone,
    ): Guru {
        $guru = Guru::create([
            'sekolah_id' => $sekolah->id,
            'nip' => $nip,
            'name' => $name,
            'jabatan' => $jabatan,
            'jenis_guru' => $jenisGuru,
            'golongan' => ['III/a', 'III/b', 'IV/a'][rand(0, 2)],
            'phone' => $phone,
            'status' => 'aktif',
        ]);
        $guru->assignRfid(sprintf('G%02d%s', $sekolah->id, preg_replace('/\D/', '', $nip)));

        AbsensiGuru::create([
            'sekolah_id' => $sekolah->id,
            'guru_id' => $guru->id,
            'date' => now()->toDateString(),
            'status' => ['hadir', 'hadir', 'izin'][rand(0, 2)],
            'method' => 'manual',
            'jam_masuk' => '07:'.sprintf('%02d', rand(0, 30)),
            'jam_keluar' => '15:'.sprintf('%02d', rand(0, 30)),
            'status_pulang' => AbsensiGuru::STATUS_PULANG_TEPAT,
            'method_keluar' => 'manual',
        ]);

        $subjects = match ($sekolah->code) {
            'paud' => ['PAUD Playgroup', 'Seni & Kreativitas', 'Motorik Halus', 'Bahasa'],
            'mts' => ['Matematika', 'Bahasa Arab', 'IPA', 'IPS', 'Bahasa Indonesia', 'Pendidikan Agama'],
            'takhasus' => ['Fiqih', 'Nahwu', 'Shorof', 'Hadits', 'Tafsir', 'Bahasa Arab'],
            default => ['Matematika', 'Fisika', 'Kimia', 'Bahasa Indonesia', 'Bahasa Inggris', 'Ekonomi'],
        };

        foreach (array_slice($subjects, 0, rand(1, 2)) as $subject) {
            $picked = $kelasList->shuffle()->take(min(2, max($kelasList->count(), 0)));

            foreach ($picked as $kelas) {
                RiwayatMengajar::create([
                    'guru_id' => $guru->id,
                    'subject' => $subject,
                    'class_name' => $kelas->name,
                    'year' => '2025/2026',
                ]);
            }
        }

        ProfilGuru::create([
            'guru_id' => $guru->id,
            'address' => 'Pekanbaru, Riau',
            'extra_fields' => ['pendidikan' => ['S1', 'S2', 'D3'][rand(0, 2)]],
        ]);

        KartuGuru::create([
            'guru_id' => $guru->id,
            'status' => 'aktif',
        ]);

        return $guru;
    }
}
