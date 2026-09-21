<?php

namespace Database\Factories;

use App\Models\TahfidzHalaqohAnggota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahfidzHalaqohAnggota>
 */
class TahfidzHalaqohAnggotaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'total_juz' => 5,
        ];
    }
}
