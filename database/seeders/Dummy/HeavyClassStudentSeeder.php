<?php

namespace Database\Seeders\Dummy;

use App\Models\JadwalAbsen;
use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Pad selected classes to ~50 active students for portal guru absensi load tests.
 *
 * Memory-safe: bulk inserts, no finance/cashless ledgers, chunked + GC.
 * Idempotent — only tops up to TARGET_PER_CLASS (does not delete).
 *
 * Run (after migrate --seed):
 *   php artisan db:seed --class=Database\\Seeders\\Dummy\\HeavyClassStudentSeeder
 */
class HeavyClassStudentSeeder extends Seeder
{
    public const TARGET_PER_CLASS = 50;

    /** Insert related rows every N students to keep peak memory low. */
    private const CHUNK = 25;

    /** @var list<string> */
    private const FIRST_NAMES_M = [
        'Ahmad', 'Budi', 'Dimas', 'Eko', 'Fajar', 'Gilang', 'Hadi', 'Irfan', 'Joko', 'Kurnia',
        'Lutfi', 'Maman', 'Naufal', 'Omar', 'Putra', 'Rafi', 'Surya', 'Teguh', 'Umar', 'Wahyu',
    ];

    /** @var list<string> */
    private const FIRST_NAMES_F = [
        'Aisyah', 'Bella', 'Citra', 'Dewi', 'Elisa', 'Farah', 'Gita', 'Hana', 'Indah', 'Juwita',
        'Kartika', 'Lina', 'Maya', 'Nadia', 'Putri', 'Rina', 'Sari', 'Tania', 'Ulya', 'Wulan',
    ];

    /** @var list<string> */
    private const LAST_NAMES = [
        'Santoso', 'Wijaya', 'Pratama', 'Saputra', 'Nugroho', 'Hidayat', 'Kurniawan', 'Setiawan',
        'Rahman', 'Firmansyah', 'Maulana', 'Gunawan', 'Suryadi', 'Hakim', 'Syahputra',
    ];

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $sekolah = Sekolah::query()
            ->where('code', DummySchoolCatalog::demoSchoolCode())
            ->first();

        if (! $sekolah) {
            $this->command?->warn('Demo school not found — skip HeavyClassStudentSeeder.');

            return;
        }

        $tahunAktifId = TahunAkademik::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        $kelasIds = $this->targetKelasIds($sekolah->id);

        if ($kelasIds === []) {
            $this->command?->warn('No target classes found — skip HeavyClassStudentSeeder.');

            return;
        }

        $prefix = (string) $sekolah->id;
        $nisCounter = 0;
        Siswa::query()
            ->where('sekolah_id', $sekolah->id)
            ->select(['id', 'nis'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$nisCounter, $prefix): void {
                foreach ($rows as $row) {
                    $nis = (string) $row->nis;
                    if (str_starts_with($nis, $prefix) && ctype_digit(substr($nis, strlen($prefix)))) {
                        $nisCounter = max($nisCounter, (int) substr($nis, strlen($prefix)));
                    }
                }
            });

        $created = 0;

        foreach ($kelasIds as $kelasId) {
            $kelas = Kelas::query()->find($kelasId);
            if (! $kelas) {
                continue;
            }

            $current = Siswa::query()
                ->where('kelas_id', $kelas->id)
                ->where('status', Siswa::STATUS_ACTIVE)
                ->count();

            $need = max(0, self::TARGET_PER_CLASS - $current);

            if ($need === 0) {
                $this->command?->info("Kelas {$kelas->name}: already {$current} siswa (target ".self::TARGET_PER_CLASS.').');

                continue;
            }

            $this->command?->info("Kelas {$kelas->name}: {$current} → ".self::TARGET_PER_CLASS." (+{$need})");

            $created += $this->padKelas(
                sekolahId: (int) $sekolah->id,
                kelasId: (int) $kelas->id,
                kelasName: (string) $kelas->name,
                tahunAktifId: $tahunAktifId ? (int) $tahunAktifId : null,
                need: $need,
                nisCounter: $nisCounter,
            );

            gc_collect_cycles();
        }

        $this->command?->info("HeavyClassStudentSeeder done — created {$created} siswa (no finance/cashless rows).");
    }

    /**
     * Prefer classes on demo jadwal absen (portal guru stress path); else first active MA classes.
     *
     * @return list<int>
     */
    private function targetKelasIds(int $sekolahId): array
    {
        $fromJadwal = JadwalAbsen::query()
            ->where('sekolah_id', $sekolahId)
            ->where('is_active', true)
            ->with('kelas:id')
            ->get()
            ->flatMap(fn (JadwalAbsen $jadwal) => $jadwal->kelas->pluck('id'))
            ->unique()
            ->values()
            ->all();

        if ($fromJadwal !== []) {
            return array_map('intval', $fromJadwal);
        }

        return Kelas::query()
            ->where('sekolah_id', $sekolahId)
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(3)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function padKelas(
        int $sekolahId,
        int $kelasId,
        string $kelasName,
        ?int $tahunAktifId,
        int $need,
        int &$nisCounter,
    ): int {
        $created = 0;
        $now = now();

        $siswaRows = [];
        $profilRows = [];
        $dompetRows = [];
        $saldoRows = [];
        $kartuRows = [];
        $rfidRows = [];
        $riwayatRows = [];

        $flush = function () use (
            &$siswaRows,
            &$profilRows,
            &$dompetRows,
            &$saldoRows,
            &$kartuRows,
            &$rfidRows,
            &$riwayatRows,
            &$created,
            $tahunAktifId,
            $kelasName,
            $now,
        ): void {
            if ($siswaRows === []) {
                return;
            }

            // insertGetId per row keeps portable ID mapping without loading Eloquent graphs.
            foreach ($siswaRows as $index => $siswaRow) {
                $siswaId = (int) DB::table('siswa')->insertGetId($siswaRow);

                $profilRows[$index]['siswa_id'] = $siswaId;
                $dompetRows[$index]['siswa_id'] = $siswaId;
                $saldoRows[$index]['siswa_id'] = $siswaId;
                $kartuRows[$index]['siswa_id'] = $siswaId;
                $rfidRows[$index]['siswa_id'] = $siswaId;
                if ($tahunAktifId) {
                    $riwayatRows[$index] = [
                        'siswa_id' => $siswaId,
                        'tahun_akademik_id' => $tahunAktifId,
                        'class_name' => $kelasName,
                        'gpa' => 3.00,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                $created++;
            }

            DB::table('profil_siswa')->insert($profilRows);
            DB::table('dompet')->insert($dompetRows);
            DB::table('saldo_keuangan')->insert($saldoRows);
            DB::table('kartu_siswa')->insert($kartuRows);
            DB::table('rfid')->insert($rfidRows);
            if ($riwayatRows !== []) {
                DB::table('riwayat_akademik')->insert($riwayatRows);
            }

            $siswaRows = [];
            $profilRows = [];
            $dompetRows = [];
            $saldoRows = [];
            $kartuRows = [];
            $rfidRows = [];
            $riwayatRows = [];
        };

        for ($i = 0; $i < $need; $i++) {
            $nisCounter++;
            $nis = sprintf('%d%06d', $sekolahId, $nisCounter);
            $gender = $i % 2 === 0 ? 'L' : 'P';
            $first = $gender === 'L'
                ? self::FIRST_NAMES_M[$i % count(self::FIRST_NAMES_M)]
                : self::FIRST_NAMES_F[$i % count(self::FIRST_NAMES_F)];
            $last = self::LAST_NAMES[$i % count(self::LAST_NAMES)];
            $name = $first.' '.$last;
            $birthDate = $now->copy()->subYears(15 + ($i % 4))->subDays($i % 300)->toDateString();

            $siswaRows[] = [
                'sekolah_id' => $sekolahId,
                'kelas_id' => $kelasId,
                'nis' => $nis,
                'nis_key' => $nis,
                'name' => $name,
                'gender' => $gender,
                'birth_date' => $birthDate,
                'birth_place' => 'Pekanbaru',
                'address' => null,
                'status' => Siswa::STATUS_ACTIVE,
                'has_foto_wajah' => 0,
                'daily_transaction_limit' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            $rfidRows[] = [
                'uid' => 'RFID'.$nis,
                'siswa_id' => 0,
                'guru_id' => null,
                'blocked' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $profilRows[] = [
                'siswa_id' => 0,
                'photo_path' => null,
                'extra_fields' => json_encode([
                    'nama_panggilan' => $first,
                    'agama' => 'Islam',
                    'golongan_darah' => ['A', 'B', 'O', 'AB'][$i % 4],
                ], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            $dompetRows[] = [
                'siswa_id' => 0,
                'saldo_us' => 0,
                'saldo_kantin' => 0,
                'saldo_tabungan' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            $saldoRows[] = [
                'siswa_id' => 0,
                'balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            $kartuRows[] = [
                'siswa_id' => 0,
                'qr_code' => 'QR-'.$nis,
                'status' => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if (count($siswaRows) >= self::CHUNK) {
                $flush();
                gc_collect_cycles();
            }
        }

        $flush();

        return $created;
    }
}
