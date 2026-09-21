<?php

namespace Database\Factories;

use App\Models\TahfidzJadwal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahfidzJadwal>
 */
class TahfidzJadwalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'day_of_week' => 1,
            'time_start' => '07:00',
            'time_end' => '09:00',
            'is_active' => true,
        ];
    }
}
