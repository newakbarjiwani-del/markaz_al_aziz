<?php

namespace Database\Seeders;

use App\Models\Sekolah;
use Database\Seeders\Dummy\Concerns\SeedsKelasRecords;
use Database\Seeders\Dummy\DummySchoolCatalog;
use Illuminate\Database\Seeder;

class ProductionSchoolSeeder extends Seeder
{
    use SeedsKelasRecords;

    public function run(): void
    {
        foreach (DummySchoolCatalog::schools() as $definition) {
            $sekolah = Sekolah::query()->firstOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'address' => $definition['address'],
                    'phone' => $definition['phone'],
                    'is_active' => true,
                ]
            );

            $this->seedKelasForSchool($sekolah, $definition, idempotent: true);

            $status = $sekolah->wasRecentlyCreated ? 'baru' : 'sudah ada';
            $this->command?->info("Sekolah \"{$definition['code']}\" siap ({$status}).");
        }
    }
}
