<?php

namespace Database\Seeders\Dummy;

use App\Models\Dompet;
use App\Models\PenarikanPendapatanKantin;
use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Models\SmTopup;
use App\Models\TransaksiCashless;
use App\Models\User;
use App\Support\UserStatus;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed kantin POS BELANJA (with operator user_id) + sample cash settlements.
 * Runs after KantinUserSeeder so operators exist.
 */
class KantinOmzetSeeder extends Seeder
{
    private const TOPUP_AMOUNTS = [100000, 150000, 200000];

    private const BELANJA_AMOUNTS = [8000, 12000, 15000, 20000, 25000];

    public function run(): void
    {
        DB::connection()->disableQueryLog();

        $settledBy = User::query()
            ->where('status', UserStatus::ACTIVE)
            ->whereIn('username', ['cashless', 'admin', 'superadmin'])
            ->orderByRaw("CASE username WHEN 'cashless' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->first();

        if (! $settledBy) {
            return;
        }

        $operators = User::role('kantin')
            ->where('status', UserStatus::ACTIVE)
            ->whereNotNull('sekolah_id')
            ->orderBy('id')
            ->get(['id', 'sekolah_id', 'name', 'username']);

        foreach ($operators as $operator) {
            $this->seedOperatorOmzet($operator, $settledBy);
            gc_collect_cycles();
        }
    }

    private function seedOperatorOmzet(User $operator, User $settledBy): void
    {
        $siswaIds = Siswa::query()
            ->where('sekolah_id', $operator->sekolah_id)
            ->where('status', Siswa::STATUS_ACTIVE)
            ->orderBy('id')
            ->limit(18)
            ->pluck('id')
            ->all();

        if ($siswaIds === []) {
            return;
        }

        $now = now();
        $cashlessRows = [];
        $smTopupRows = [];
        $legacyRows = [];
        $touchedSiswa = [];
        $seq = 0;

        for ($dayOffset = 10; $dayOffset >= 0; $dayOffset--) {
            $day = $now->copy()->subDays($dayOffset)->setTime(10, 0);
            $salesToday = 2 + (($operator->id + $dayOffset) % 4);

            for ($i = 0; $i < $salesToday; $i++) {
                $siswaId = $siswaIds[($seq + $i) % count($siswaIds)];
                $amount = self::BELANJA_AMOUNTS[($siswaId + $dayOffset + $i) % count(self::BELANJA_AMOUNTS)];
                $trxAt = $day->copy()->addMinutes(($i * 17) + ($seq % 11));
                $noreff = sprintf('KO%06dD%02dS%02d', $operator->id, $dayOffset, $i);

                $cashlessRows[] = [
                    'CUSTID' => $siswaId,
                    'user_id' => $operator->id,
                    'METODE' => 'BELANJA',
                    'wallet' => 'us',
                    'TRXDATE' => $trxAt,
                    'NOREFF' => $noreff,
                    'FIDBANK' => null,
                    'KDCHANNEL' => null,
                    'KREDIT' => 0,
                    'DEBET' => $amount,
                    'description' => 'Belanja kantin (dummy)',
                    'created_at' => $trxAt,
                    'updated_at' => $trxAt,
                ];

                $legacyRows[] = [
                    'siswa_id' => $siswaId,
                    'type' => 'belanja',
                    'category' => 'kantin',
                    'amount' => $amount,
                    'wallet' => 'us',
                    'description' => 'Belanja kantin (dummy)',
                    'created_at' => $trxAt,
                    'updated_at' => $trxAt,
                ];

                $touchedSiswa[$siswaId] = true;
            }

            $seq += $salesToday;
        }

        foreach (array_slice($siswaIds, 0, 12) as $index => $siswaId) {
            $topup = self::TOPUP_AMOUNTS[$index % count(self::TOPUP_AMOUNTS)];
            $trxAt = $now->copy()->subDays(12)->addHours($index);
            $noreff = sprintf('KT%06dT%02d', $operator->id, $index);

            $cashlessRows[] = [
                'CUSTID' => $siswaId,
                'user_id' => null,
                'METODE' => 'TOP UP',
                'wallet' => 'us',
                'TRXDATE' => $trxAt,
                'NOREFF' => $noreff,
                'FIDBANK' => null,
                'KDCHANNEL' => null,
                'KREDIT' => $topup,
                'DEBET' => 0,
                'description' => 'Top up cashless (kantin omzet seed)',
                'created_at' => $trxAt,
                'updated_at' => $trxAt,
            ];

            $smTopupRows[] = [
                'CUSTID' => $siswaId,
                'NOMINAL' => $topup,
                'TOPUPNO' => SmTopup::TOPUPNO_CASHLESS,
                'TRXDATE' => $trxAt,
                'created_at' => $trxAt,
                'updated_at' => $trxAt,
            ];

            $legacyRows[] = [
                'siswa_id' => $siswaId,
                'type' => 'topup',
                'category' => 'manual',
                'amount' => $topup,
                'wallet' => 'us',
                'description' => 'Top up cashless (kantin omzet seed)',
                'created_at' => $trxAt,
                'updated_at' => $trxAt,
            ];

            $touchedSiswa[$siswaId] = true;
        }

        foreach (array_chunk($cashlessRows, 100) as $chunk) {
            DB::table('sccttran_cashless')->insert($chunk);
        }

        foreach (array_chunk($smTopupRows, 100) as $chunk) {
            DB::table('sm_topup')->insert($chunk);
        }

        foreach (array_chunk($legacyRows, 100) as $chunk) {
            DB::table('transaksi_cashless')->insert($chunk);
        }

        foreach (array_keys($touchedSiswa) as $siswaId) {
            $balance = (int) SccttranCashless::query()
                ->where('CUSTID', $siswaId)
                ->selectRaw('COALESCE(SUM(KREDIT), 0) - COALESCE(SUM(DEBET), 0) AS balance')
                ->value('balance');

            Dompet::query()->updateOrCreate(
                ['siswa_id' => $siswaId],
                [
                    'saldo_us' => max(0, $balance),
                    'saldo_kantin' => 0,
                    'saldo_tabungan' => 0,
                ]
            );
        }

        $grossBelanja = (int) collect($cashlessRows)
            ->where('METODE', 'BELANJA')
            ->sum('DEBET');

        if ($grossBelanja < 10000) {
            return;
        }

        $withdrawPct = 0.45 + (($operator->id % 3) * 0.05);
        $withdrawAmount = (int) (floor(($grossBelanja * $withdrawPct) / 1000) * 1000);
        $withdrawAmount = max(10000, min($withdrawAmount, $grossBelanja - 5000));

        $firstSettle = (int) (floor(($withdrawAmount * 0.6) / 1000) * 1000);
        $secondSettle = $withdrawAmount - $firstSettle;

        $this->insertPenarikan($operator, $settledBy, $firstSettle, $now->copy()->subDays(5), 'Settlement mingguan (dummy)');

        if ($secondSettle >= 10000) {
            $this->insertPenarikan($operator, $settledBy, $secondSettle, $now->copy()->subDays(1), 'Settlement lanjutan (dummy)');
        }
    }

    private function insertPenarikan(
        User $operator,
        User $settledBy,
        int $amount,
        Carbon $settledAt,
        string $description,
    ): void {
        PenarikanPendapatanKantin::query()->create([
            'kantin_user_id' => $operator->id,
            'sekolah_id' => $operator->sekolah_id,
            'amount' => $amount,
            'method' => PenarikanPendapatanKantin::METHOD_CASH,
            'noreff' => sprintf('PK%s%04d', $settledAt->format('ymdHis'), $operator->id % 10000),
            'description' => $description,
            'settled_by' => $settledBy->id,
            'settled_at' => $settledAt,
        ]);
    }
}
