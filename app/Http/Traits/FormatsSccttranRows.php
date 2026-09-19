<?php

namespace App\Http\Traits;

use App\Models\Sccttran;

trait FormatsSccttranRows
{
    /**
     * @return list<mixed>
     */
    protected function formatSccttranRow(Sccttran $row, bool $withStudent = false): array
    {
        $cells = [];

        if ($withStudent) {
            $cells[] = $row->siswa?->nis ?? '-';
            $cells[] = $row->siswa?->name ?? '-';
            $cells[] = $row->siswa?->kelas?->name ?? '-';
        }

        $cells = array_merge($cells, [
            $row->TRXDATE,
            $row->METODE ?? '-',
            $row->KREDIT > 0 ? 'Rp '.number_format($row->KREDIT, 0, ',', '.') : '-',
            $row->DEBET > 0 ? 'Rp '.number_format($row->DEBET, 0, ',', '.') : '-',
            $row->NOREFF ?? '-',
            $row->KDCHANNEL ?? '-',
        ]);

        return $cells;
    }
}
