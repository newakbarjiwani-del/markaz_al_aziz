<?php

namespace Database\Seeders\Dummy;

use App\Models\JenisTagihan;
use App\Models\RekeningBank;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Support\TagihanPeriode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceSeeder extends Seeder
{
    /** @var int[] */
    private const SOLID_AMOUNTS = [150000, 200000, 300000];

    private const SPP_PERIODES = [202607, 202608, 202609, 202610, 202611, 202612];

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $jenisTagihan = $this->seedJenisTagihan();
        $tahunAktifId = TahunAkademik::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        foreach (Sekolah::query()->orderBy('id')->cursor() as $sekolah) {
            RekeningBank::query()->firstOrCreate(
                [
                    'sekolah_id' => $sekolah->id,
                    'account_number' => '123456789'.str_pad((string) $sekolah->id, 1, '0', STR_PAD_LEFT),
                ],
                [
                    'bank' => 'BCA',
                    'account_name' => 'Yayasan YAYASAN ITTIHAD - '.$sekolah->code,
                    'is_active' => true,
                ]
            );

            $studentQuery = Siswa::query()
                ->where('sekolah_id', $sekolah->id)
                ->orderBy('id');

            // Cap finance rows on large catalogs (e.g. takhasus) to stay within PHP memory_limit.
            if (DummySchoolCatalog::FINANCE_STUDENTS_PER_SCHOOL > 0) {
                $studentQuery->limit(DummySchoolCatalog::FINANCE_STUDENTS_PER_SCHOOL);
            }

            $studentQuery->eachById(function (Siswa $siswa) use ($sekolah, $tahunAktifId, $jenisTagihan): void {
                $this->seedSaldoLedger($siswa);
                $this->seedSppBulanan($sekolah->id, $siswa, $tahunAktifId, $jenisTagihan['SPP']);
                $this->seedBiayaTahunan($sekolah->id, $siswa, $tahunAktifId, $jenisTagihan);

                unset($siswa);
            }, 25);

            gc_collect_cycles();
        }
    }

    /**
     * @return array<string, JenisTagihan>
     */
    private function seedJenisTagihan(): array
    {
        $definitions = [
            ['name' => 'SPP', 'code' => 'spp', 'default_amount' => 300000, 'is_spp' => true, 'sort_order' => 1],
            ['name' => 'Uang Gedung', 'code' => 'uang-gedung', 'default_amount' => 300000, 'sort_order' => 2],
            ['name' => 'Seragam', 'code' => 'seragam', 'default_amount' => 200000, 'sort_order' => 3],
            ['name' => 'Kegiatan', 'code' => 'kegiatan', 'default_amount' => 150000, 'sort_order' => 4],
        ];

        $map = [];
        foreach ($definitions as $definition) {
            $map[$definition['name']] = JenisTagihan::query()->firstOrCreate(
                ['name' => $definition['name']],
                [
                    'code' => $definition['code'],
                    'default_amount' => $definition['default_amount'],
                    'is_spp' => $definition['is_spp'] ?? false,
                    'sort_order' => $definition['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        return $map;
    }

    private function seedSppBulanan(int $sekolahId, Siswa $siswa, ?int $tahunAkademikId, JenisTagihan $sppJenis): void
    {
        foreach (self::SPP_PERIODES as $index => $periode) {
            $amount = (float) ($sppJenis->default_amount ?? 500000);
            $scenario = ($siswa->id + $index) % 4;

            [$paid, $status] = match ($scenario) {
                0 => [$amount, Tagihan::STATUS_PAID],
                1 => [0, Tagihan::STATUS_UNPAID],
                2 => [0, Tagihan::STATUS_UNPAID],
                default => [$amount, Tagihan::STATUS_PAID],
            };

            [$year, $month] = TagihanPeriode::toCalendar($periode);
            $dueDate = now()->setDate($year, $month, 10);
            if ($dueDate->isPast()) {
                $dueDate = $dueDate->addYear();
            }

            $tagihanId = DB::table('tagihan')->insertGetId([
                'sekolah_id' => $sekolahId,
                'siswa_id' => $siswa->id,
                'tahun_akademik_id' => $tahunAkademikId,
                'jenis_tagihan_id' => $sppJenis->id,
                'jenis' => $sppJenis->name,
                'amount' => $amount,
                'paid' => $paid,
                'status' => $status,
                'due_date' => $dueDate->toDateString(),
                'periode' => $periode,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($paid > 0) {
                $this->catatPembayaran($siswa->id, $tagihanId, $paid, $index);
            }
        }
    }

    /**
     * @param  array<string, JenisTagihan>  $jenisTagihan
     */
    private function seedBiayaTahunan(int $sekolahId, Siswa $siswa, ?int $tahunAkademikId, array $jenisTagihan): void
    {
        $biaya = [
            ['jenis' => 'Uang Gedung', 'periode' => 202601],
            ['jenis' => 'Seragam', 'periode' => 202602],
            ['jenis' => 'Kegiatan', 'periode' => 202603],
        ];

        foreach ($biaya as $offset => $item) {
            $master = $jenisTagihan[$item['jenis']];
            $amount = (float) $master->default_amount;
            $paid = match (($siswa->id + $offset) % 3) {
                0 => $amount,
                1 => 0,
                default => 0,
            };

            $status = $paid >= $amount ? Tagihan::STATUS_PAID : Tagihan::STATUS_UNPAID;

            $tagihanId = DB::table('tagihan')->insertGetId([
                'sekolah_id' => $sekolahId,
                'siswa_id' => $siswa->id,
                'tahun_akademik_id' => $tahunAkademikId,
                'jenis_tagihan_id' => $master->id,
                'jenis' => $master->name,
                'amount' => $amount,
                'paid' => $paid,
                'status' => $status,
                'due_date' => now()->addMonths(2 + $offset)->toDateString(),
                'periode' => $item['periode'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($paid > 0) {
                $this->catatPembayaran($siswa->id, $tagihanId, $paid, $offset + 10);
            }
        }
    }

    private function catatPembayaran(int $siswaId, int $tagihanId, float $amount, int $seed): void
    {
        $paidDt = now()->subDays(2 + ($seed % 40));
        $method = ['transfer', 'tunai', 'qris', 'va'][$seed % 4];
        $reference = 'PAY-'.strtoupper(substr(md5($tagihanId.'-'.$seed), 0, 8));

        $paymentId = DB::table('pembayaran')->insertGetId([
            'siswa_id' => $siswaId,
            'method' => $method,
            'reference' => $reference,
            'total_amount' => $amount,
            'paid_dt' => $paidDt,
            'created_at' => $paidDt,
            'updated_at' => $paidDt,
        ]);

        DB::table('pembayaran_detail')->insert([
            'pembayaran_id' => $paymentId,
            'tagihan_id' => $tagihanId,
            'amount' => $amount,
            'created_at' => $paidDt,
            'updated_at' => $paidDt,
        ]);

        DB::table('tagihan')->where('id', $tagihanId)->update([
            'paid_dt' => $paidDt,
            'reference' => $reference,
            'fidbank' => $method,
            'updated_at' => now(),
        ]);
    }

    private function seedSaldoLedger(Siswa $siswa): void
    {
        $baseDate = now()->startOfMonth()->subMonth();
        $creditA = self::SOLID_AMOUNTS[($siswa->id + 2) % 3];
        $creditB = self::SOLID_AMOUNTS[$siswa->id % 3];
        $debit = self::SOLID_AMOUNTS[($siswa->id + 1) % 3];

        $entries = [
            ['METODE' => 'TOP UP', 'KREDIT' => $creditA, 'DEBET' => 0, 'day' => 3],
            ['METODE' => 'TOP UP', 'KREDIT' => $creditB, 'DEBET' => 0, 'day' => 12],
            ['METODE' => 'FROM INVOICE', 'KREDIT' => 0, 'DEBET' => $debit, 'day' => 18],
        ];

        $rows = [];
        foreach ($entries as $index => $entry) {
            $trxDate = $baseDate->copy()->addDays($entry['day']);
            $rows[] = [
                'CUSTID' => $siswa->id,
                'METODE' => $entry['METODE'],
                'TRXDATE' => $trxDate,
                'KREDIT' => $entry['KREDIT'],
                'DEBET' => $entry['DEBET'],
                'NOREFF' => 'SD-'.str_pad((string) $siswa->id, 5, '0', STR_PAD_LEFT).'-'.($index + 1),
                'FIDBANK' => null,
                'KDCHANNEL' => null,
                'created_at' => $trxDate,
                'updated_at' => $trxDate,
            ];
        }

        DB::table('sccttran')->insert($rows);

        $balance = $creditA + $creditB - $debit;

        DB::table('saldo_keuangan')->updateOrInsert(
            ['siswa_id' => $siswa->id],
            ['balance' => $balance, 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
