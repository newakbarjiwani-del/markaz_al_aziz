<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SyncServerRolesSeeder::class,
            RolePermissionSeeder::class,
            ProductionSchoolSeeder::class,
            SppMonthlyJenisTagihanSeeder::class,
            ProductionUserSeeder::class,
        ]);
    }
}
