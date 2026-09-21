<?php

namespace App\Services;

use App\Models\TahfidzRekap;
use App\Models\TahfidzRekapSiswa;
use App\Support\PortalGreeting;
use Illuminate\Support\Str;

class TahfidzRekapComposer
{
    public function fullMessage(TahfidzRekap $rekap): string
    {
        $rekap->loadMissing([
            'program',
            'baris.siswa',
            'baris.halaqoh.guru',
        ]);

        $program = $rekap->program;
        $lines = [
            $program?->title() ?? 'REKAP PENCAPAIAN',
            'Tanggal '.$rekap->periodLabel(),
            '',
        ];

        $grouped = $rekap->baris
            ->sortBy(fn (TahfidzRekapSiswa $row) => $row->halaqoh?->displayName().' '.$row->siswa?->name)
            ->groupBy('halaqoh_id');

        foreach ($grouped as $rows) {
            $halaqoh = $rows->first()?->halaqoh;
            $lines[] = '🌟'.$halaqoh?->displayName();
            $lines[] = '';

            foreach ($rows->sortBy(fn (TahfidzRekapSiswa $row) => $row->siswa?->name) as $row) {
                $lines[] = $this->studentBlock($row);
                $lines[] = '';
            }
        }

        return trim(implode("\n", $lines));
    }

    public function childMessage(TahfidzRekapSiswa $row): string
    {
        $row->loadMissing(['rekap.program', 'siswa', 'halaqoh.guru']);

        $nama = $row->siswa?->name ?? 'Ananda';
        $instansi = (string) config('app.nama_instansi', config('app.name'));

        $lines = [
            PortalGreeting::salutation().' Bapak/Ibu wali '.$nama.',',
            '',
            'Berikut rekap pencapaian '.$nama.' di '.$row->halaqoh?->displayName().' periode '.$row->rekap?->periodLabel().'.',
            '',
            $this->studentBlock($row),
            '',
            $instansi,
        ];

        return implode("\n", $lines);
    }

    public function studentBlock(TahfidzRekapSiswa $row): string
    {
        $lines = [
            '🧕'.$this->kakName($row->siswa?->name),
            '',
            '•Tatsbit : '.$row->tatsbitLabel(),
            '* Muroja\'ah partner: '.$row->murojaahLabel(),
            '',
            '▪️Absensi kehadiran:',
            'Hadir: '.$this->hariCount($row->hadir_hari),
            'Sakit: '.$this->hariCount($row->sakit_hari),
            'Pulang: '.$this->hariCount($row->pulang_hari),
            '',
            '🎗️Total seluruh  hafalan : '.$row->total_juz.' juz',
        ];

        if (filled($row->prestasi)) {
            $lines[] = '';
            $lines[] = '🏅Prestasi: '.$row->prestasi;
        }

        return implode("\n", $lines);
    }

    private function kakName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'Kak';
        }

        $first = Str::of($name)->explode(' ')->first();

        return 'Kak '.$first;
    }

    private function hariCount(?int $count): string
    {
        if ($count === null || $count < 1) {
            return '-';
        }

        return $count.' hari';
    }
}
