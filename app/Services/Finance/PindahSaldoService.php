<?php

namespace App\Services\Finance;

use App\Models\Dompet;
use App\Models\SaldoKeuangan;
use App\Models\Sccttran;
use App\Models\SccttranCashless;
use App\Models\Siswa;
use App\Models\SmTopup;
use App\Support\InfaqTiers;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PindahSaldoService
{
    public function __construct(
        private readonly SccttranSaldoService $sccttranSaldo,
    ) {}

    public function biayaAdmin(): int
    {
        return (int) config('finance.biaya_admin_pindah_saldo', 1000);
    }

    /**
     * Transfer finance ledger saldo → cashless uang saku (`saldo_us`).
     *
     * @param  int|null  $infaqOverride  null = auto-calculate from tiers, 0 = skip, >0 = use value
     * @return array{refno: string, amount: int, biaya_admin: int, infaq: int, saldo_keuangan: int}
     */
    public function transfer(Siswa $siswa, int $amount, ?int $userId = null, ?int $infaqOverride = null): array
    {
        $siswa->assertCanTransact();

        if ($amount < 1000) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal minimal Rp 1.000.',
            ]);
        }

        $biayaAdmin = $this->biayaAdmin();
        $saldoKeuangan = $this->sccttranSaldo->balanceForSiswa($siswa->id);
        $totalDibutuhkan = $amount + $biayaAdmin;

        if ($saldoKeuangan < $totalDibutuhkan) {
            $message = 'Saldo keuangan tidak mencukupi. Tersedia: Rp '
                .number_format($saldoKeuangan, 0, ',', '.')
                .', dibutuhkan: Rp '.number_format($totalDibutuhkan, 0, ',', '.')
                .($biayaAdmin > 0 ? ' (termasuk biaya admin Rp '.number_format($biayaAdmin, 0, ',', '.').')' : '');

            throw ValidationException::withMessages([
                'amount' => $message,
            ]);
        }

        $refno = 'PS'.now()->format('YmdHi').substr(str_pad((string) $siswa->nis, 6, '0', STR_PAD_LEFT), -6);

        $infaqAmount = $infaqOverride !== null
            ? max(0, $infaqOverride)
            : InfaqTiers::calculate($amount);

        DB::transaction(function () use ($siswa, $amount, $biayaAdmin, $refno, $userId, $infaqAmount) {
            Sccttran::create([
                'CUSTID' => $siswa->id,
                'user_id' => $userId,
                'METODE' => 'PINDAH SALDO',
                'TRXDATE' => now(),
                'KREDIT' => 0,
                'DEBET' => $amount,
                'NOREFF' => $refno,
            ]);

            SccttranCashless::create([
                'CUSTID' => $siswa->id,
                'user_id' => $userId,
                'METODE' => 'PINDAH SALDO',
                'TRXDATE' => now(),
                'KREDIT' => $amount,
                'DEBET' => 0,
                'NOREFF' => $refno,
            ]);

            SmTopup::create([
                'CUSTID' => $siswa->id,
                'user_id' => $userId,
                'NOMINAL' => $amount,
                'TRXDATE' => now(),
            ]);

            if ($biayaAdmin > 0) {
                SccttranCashless::create([
                    'CUSTID' => $siswa->id,
                    'user_id' => $userId,
                    'METODE' => 'ADMIN FEE',
                    'TRXDATE' => now(),
                    'KREDIT' => 0,
                    'DEBET' => $biayaAdmin,
                    'NOREFF' => $refno,
                ]);
            }

            if ($infaqAmount > 0) {
                $infaqRefno = 'INF'.now()->format('YmdHi').substr(str_pad((string) $siswa->nis, 6, '0', STR_PAD_LEFT), -6);

                SccttranCashless::create([
                    'CUSTID' => $siswa->id,
                    'user_id' => $userId,
                    'METODE' => 'INFAQ',
                    'TRXDATE' => now(),
                    'KREDIT' => 0,
                    'DEBET' => $infaqAmount,
                    'NOREFF' => $infaqRefno,
                ]);

                Dompet::firstOrCreate(['siswa_id' => $siswa->id])
                    ->decrement('saldo_us', $infaqAmount);
            }

            Dompet::firstOrCreate(['siswa_id' => $siswa->id])
                ->increment('saldo_us', $amount);

            $saldo = SaldoKeuangan::firstOrCreate(
                ['siswa_id' => $siswa->id],
                ['balance' => 0]
            );
            $saldo->decrement('balance', $amount);
        });

        return [
            'refno' => $refno,
            'amount' => $amount,
            'biaya_admin' => $biayaAdmin,
            'infaq' => $infaqAmount,
            'saldo_keuangan' => $this->sccttranSaldo->balanceForSiswa($siswa->id),
        ];
    }
}
