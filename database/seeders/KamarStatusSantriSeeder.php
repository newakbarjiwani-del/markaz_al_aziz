<?php

namespace Database\Seeders;

use App\Models\Kamar;
use App\Models\StatusSantri;
use Illuminate\Database\Seeder;

class KamarStatusSantriSeeder extends Seeder
{
    public function run(): void
    {
        $kamarRows = [
            ['kode' => 'A-01', 'nama' => 'A-01', 'blok' => 'Asrama A', 'kapasitas' => 4, 'sort_order' => 1],
            ['kode' => 'A-02', 'nama' => 'A-02', 'blok' => 'Asrama A', 'kapasitas' => 4, 'sort_order' => 2],
            ['kode' => 'A-03', 'nama' => 'A-03', 'blok' => 'Asrama A', 'kapasitas' => 4, 'sort_order' => 3],
            ['kode' => 'A-04', 'nama' => 'A-04', 'blok' => 'Asrama A', 'kapasitas' => 4, 'sort_order' => 4],
            ['kode' => 'B-01', 'nama' => 'B-01', 'blok' => 'Asrama B', 'kapasitas' => 6, 'sort_order' => 5],
        ];

        foreach ($kamarRows as $row) {
            Kamar::withTrashed()->updateOrCreate(
                ['nama' => $row['nama']],
                [
                    ...$row,
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );
        }

        $statusRows = [
            ['nama' => 'Santri', 'sort_order' => 1],
            ['nama' => 'Pengurus', 'sort_order' => 2],
            ['nama' => 'Mudabir', 'sort_order' => 3],
        ];

        foreach ($statusRows as $row) {
            StatusSantri::withTrashed()->updateOrCreate(
                ['nama' => $row['nama']],
                [
                    ...$row,
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
