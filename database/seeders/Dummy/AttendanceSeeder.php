<?php

namespace Database\Seeders\Dummy;

use App\Models\AbsensiSiswa;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsensiGuru;
use App\Models\Kelas;
use App\Models\Pelajaran;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Support\Weekday;
use Illuminate\Database\Seeder;

/**
 * Seeds jadwal absen (multi-kelas) + sample student attendance so portal guru works out of the box.
 */
class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $sekolah = Sekolah::query()
            ->where('code', DummySchoolCatalog::demoSchoolCode())
            ->first();

        if (! $sekolah) {
            return;
        }

        $guru = Guru::query()
            ->where('sekolah_id', $sekolah->id)
            ->orderBy('id')
            ->first();

        $kelasList = Kelas::query()
            ->where('sekolah_id', $sekolah->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if (! $guru || $kelasList->count() < 2) {
            return;
        }

        $jadwalGuru = JadwalAbsensiGuru::query()->firstOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'name' => 'Jadwal Absensi Guru '.$sekolah->unit,
            ],
            [
                'jam_masuk' => '07:00',
                'jam_pulang' => '15:00',
                'toleransi_menit' => 15,
                'is_active' => true,
            ]
        );

        if (! $guru->jadwal_absensi_guru_id) {
            $guru->update(['jadwal_absensi_guru_id' => $jadwalGuru->id]);
        }

        $pelajaranPagi = Pelajaran::query()->firstOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'name' => 'Matematika',
            ],
            [
                'code' => 'MTK',
                'is_active' => true,
            ]
        );

        $pelajaranSiang = Pelajaran::query()->firstOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'name' => 'Bahasa Indonesia',
            ],
            [
                'code' => 'BIN',
                'is_active' => true,
            ]
        );

        // First schedule: two kelas in the morning (multi-class attendance).
        $kelasPagi = $kelasList->take(2);
        $jadwalPagi = $this->syncKelasJadwal(
            sekolah: $sekolah,
            name: 'Absensi Pagi '.$sekolah->unit,
            kelasIds: $kelasPagi->pluck('id')->all(),
            guru: $guru,
            pelajaran: $pelajaranPagi,
            timeStart: '07:00',
            timeEnd: '08:00',
        );

        // Second schedule: another kelas in late morning (guru manages multiple schedules/classes).
        $kelasSiang = $kelasList->slice(2, 1);
        if ($kelasSiang->isNotEmpty()) {
            $this->syncKelasJadwal(
                sekolah: $sekolah,
                name: 'Absensi Siang '.$sekolah->unit,
                kelasIds: $kelasSiang->pluck('id')->all(),
                guru: $guru,
                pelajaran: $pelajaranSiang,
                timeStart: '09:00',
                timeEnd: '10:00',
            );
        }

        $today = Weekday::fromDate(now());
        $slotToday = $jadwalPagi->hari()
            ->where('day_of_week', $today)
            ->where('is_active', true)
            ->with('slots')
            ->first()
            ?->slots
            ->first();

        if (! $slotToday) {
            return;
        }

        $students = Siswa::query()
            ->whereIn('kelas_id', $kelasPagi->pluck('id'))
            ->where('status', Siswa::STATUS_ACTIVE)
            ->orderBy('id')
            ->limit(8)
            ->get();

        foreach ($students as $index => $siswa) {
            AbsensiSiswa::query()->updateOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'jadwal_absen_slot_id' => $slotToday->id,
                    'date' => now()->toDateString(),
                ],
                [
                    'sekolah_id' => $siswa->sekolah_id,
                    'status' => $index % 5 === 0 ? 'izin' : 'hadir',
                    'method' => 'manual',
                    'time_in' => $index % 5 === 0 ? null : '07:'.sprintf('%02d', 5 + $index),
                ]
            );
        }
    }

    /**
     * @param  list<int>  $kelasIds
     */
    private function syncKelasJadwal(
        Sekolah $sekolah,
        string $name,
        array $kelasIds,
        Guru $guru,
        Pelajaran $pelajaran,
        string $timeStart,
        string $timeEnd,
    ): JadwalAbsen {
        $jadwal = JadwalAbsen::query()->firstOrCreate(
            [
                'sekolah_id' => $sekolah->id,
                'name' => $name,
            ],
            [
                'assignment_type' => 'kelas',
                'is_active' => true,
                'notes' => 'Dummy jadwal multi-kelas untuk portal guru',
            ]
        );

        $jadwal->fill([
            'assignment_type' => 'kelas',
            'is_active' => true,
        ])->save();

        $jadwal->kelas()->sync($kelasIds);

        foreach (Weekday::numbers() as $dayOfWeek) {
            if ($dayOfWeek === Weekday::MINGGU) {
                $hari = $jadwal->hari()->where('day_of_week', $dayOfWeek)->first();
                if ($hari) {
                    $hari->slots()->delete();
                    $hari->delete();
                }

                continue;
            }

            $hari = $jadwal->hari()->withTrashed()->firstOrNew(['day_of_week' => $dayOfWeek]);
            if (! $hari->jadwal_absen_id) {
                $hari->jadwal_absen_id = $jadwal->id;
            }
            if ($hari->trashed()) {
                $hari->restore();
            }
            $hari->fill(['is_active' => true])->save();

            $hari->slots()->delete();
            $hari->slots()->create([
                'pelajaran_id' => $pelajaran->id,
                'guru_id' => $guru->id,
                'time_start' => $timeStart,
                'time_end' => $timeEnd,
                'tolerance_minutes' => 15,
                'sort_order' => 0,
            ]);
        }

        return $jadwal->fresh(['hari.slots', 'kelas']);
    }
}
