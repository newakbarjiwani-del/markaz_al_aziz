<?php

namespace App\Services\Finance;

use App\Models\Tagihan;

class FirstUnpaidTagihanQuery
{
    public static function forSiswa(int $siswaId): ?Tagihan
    {
        return Tagihan::query()
            ->rootBill()
            ->with(['tahunAkademik', 'jenisTagihan'])
            ->where('siswa_id', $siswaId)
            ->unpaid()
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByRaw('CASE WHEN urutan IS NULL THEN 1 ELSE 0 END')
            ->orderBy('urutan')
            ->orderBy('id')
            ->first();
    }
}
