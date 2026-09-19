<?php

namespace Database\Seeders;

use Database\Seeders\Dummy\KantinOmzetSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (config('seed.mode') === 'production') {
            $this->call(ProductionSeeder::class);

            return;
        }

        $this->call([
            SyncServerRolesSeeder::class,
            RolePermissionSeeder::class,
            DummyDataSeeder::class,
            UserSeeder::class,
            KantinUserSeeder::class,
            KantinOmzetSeeder::class,
        ]);
    }
}
