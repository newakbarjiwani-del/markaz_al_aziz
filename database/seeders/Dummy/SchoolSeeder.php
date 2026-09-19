<?php

namespace Database\Seeders\Dummy;

use App\Models\Sekolah;
use App\Models\TahunAkademik;
use Database\Seeders\Dummy\Concerns\SeedsKelasRecords;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    use SeedsKelasRecords;

    public function run(): void
    {
        $schools = [];
        foreach (DummySchoolCatalog::schools() as $definition) {
            $schools[] = Sekolah::create([
                'code' => $definition['code'],
                'name' => $definition['name'],
                'address' => $definition['address'],
                'phone' => $definition['phone'],
                'is_active' => true,
            ]);
        }

        TahunAkademik::create([
            'name' => '2025/2026',
            'is_active' => true,
        ]);

        TahunAkademik::create([
            'name' => '2024/2025',
            'is_active' => false,
        ]);

        foreach ($schools as $sekolah) {
            $definition = DummySchoolCatalog::schools()[$sekolah->code];
            $this->seedKelasForSchool($sekolah, $definition);
        }
    }
}
