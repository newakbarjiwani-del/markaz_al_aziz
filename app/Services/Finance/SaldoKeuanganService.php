<?php

namespace App\Services\Finance;

use App\Models\SaldoKeuangan;
use App\Models\Siswa;

class SaldoKeuanganService
{
    public function forSiswa(Siswa $siswa, bool $lock = false): SaldoKeuangan
    {
        $query = SaldoKeuangan::query()->where('siswa_id', $siswa->id);

        if ($lock) {
            $query->lockForUpdate();
        }

        $saldo = $query->first();

        if ($saldo !== null) {
            return $saldo;
        }

        return SaldoKeuangan::create([
            'siswa_id' => $siswa->id,
            'balance' => 0,
        ]);
    }

    public function credit(SaldoKeuangan $saldo, int $amount): SaldoKeuangan
    {
        $saldo->increment('balance', $amount);

        return $saldo->fresh();
    }

    public function debit(SaldoKeuangan $saldo, int $amount): SaldoKeuangan
    {
        $saldo->decrement('balance', $amount);

        return $saldo->fresh();
    }
}
