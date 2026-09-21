<?php

namespace Database\Factories;

use App\Models\TahfidzRekapSiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahfidzRekapSiswa>
 */
class TahfidzRekapSiswaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tatsbit_juz' => [1, 2, 4],
            'murojaah_juz' => [6, 7, 8, 9, 10],
            'hadir_hari' => 4,
            'sakit_hari' => 0,
            'pulang_hari' => 0,
            'total_juz' => 5,
            'prestasi' => "Tasmi' 5 juz sekali duduk (1-5)",
        ];
    }
}
