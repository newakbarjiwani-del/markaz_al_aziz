<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Empty catalog seeder — katalog prestasi starts with no rows.
 * Admin creates entries via UI. Idempotent no-op for seed chains.
 */
class PrestasiCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Intentionally empty — seed no jenis_prestasi rows.
    }
}
