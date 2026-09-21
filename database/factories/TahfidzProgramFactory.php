<?php

namespace Database\Factories;

use App\Models\TahfidzProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahfidzProgram>
 */
class TahfidzProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'ITQON',
            'angkatan' => 6,
            'peserta_label' => 'SANTRIWATI',
            'is_active' => true,
        ];
    }
}
