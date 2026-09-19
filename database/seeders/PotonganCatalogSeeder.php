<?php

namespace Database\Seeders;

use App\Models\JenisPotongan;
use App\Support\PotonganTipe;
use Illuminate\Database\Seeder;

class PotonganCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'nama' => 'Beasiswa',
                'kode' => 'BEASISWA',
                'tipe_default' => PotonganTipe::PERCENT,
                'nilai_default' => 50,
                'sort_order' => 1,
                'keterangan' => 'Potongan beasiswa (default 50%).',
            ],
            [
                'nama' => 'Kurang Mampu',
                'kode' => 'KURANG_MAMPU',
                'tipe_default' => PotonganTipe::FIXED,
                'nilai_default' => 100000,
                'sort_order' => 2,
                'keterangan' => 'Potongan siswa kurang mampu (default Rp 100.000).',
            ],
        ];

        foreach ($rows as $row) {
            $existing = JenisPotongan::query()
                ->where('nama', $row['nama'])
                ->whereNull('deleted_at')
                ->first();

            if ($existing !== null) {
                continue;
            }

            JenisPotongan::create([
                ...$row,
                'sekolah_id' => null,
                'is_active' => true,
            ]);
        }
    }
}
