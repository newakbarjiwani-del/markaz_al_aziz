<?php

namespace Database\Factories;

use App\Models\TahfidzRekap;
use App\Support\TahfidzRekapStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TahfidzRekap>
 */
class TahfidzRekapFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'starts_on' => '2026-09-12',
            'ends_on' => '2026-09-17',
            'status' => TahfidzRekapStatus::DRAFT,
        ];
    }
}
