<?php

namespace Database\Seeders;

use App\Models\JenisTagihan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SppMonthlyJenisTagihanSeeder extends Seeder
{
    public function run(): void
    {
        $months = [
            'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
            'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER',
        ];

        $baseAmount = (float) (JenisTagihan::query()
            ->whereRaw('LOWER(name) = ?', ['spp'])
            ->value('default_amount') ?? 0);

        foreach ($months as $index => $month) {
            $name = 'SPP '.$month;
            $code = 'spp_'.Str::lower($month);

            $jenis = JenisTagihan::withTrashed()
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->orderBy('id')
                ->first();

            if (! $jenis) {
                JenisTagihan::create([
                    'name' => $name,
                    'code' => $code,
                    'description' => 'Tagihan SPP bulan '.$month,
                    'default_amount' => $baseAmount,
                    'is_spp' => true,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]);

                continue;
            }

            if ($jenis->trashed()) {
                $jenis->restore();
            }

            $jenis->update([
                'is_spp' => true,
                'is_active' => true,
                'code' => $jenis->code ?: $code,
                'description' => $jenis->description ?: ('Tagihan SPP bulan '.$month),
                'sort_order' => $jenis->sort_order ?: ($index + 1),
            ]);
        }
    }
}
