<?php

namespace Database\Seeders;

use Database\Seeders\Dummy\AttendanceSeeder;
use Database\Seeders\Dummy\CashlessSeeder;
use Database\Seeders\Dummy\FinanceSeeder;
use Database\Seeders\Dummy\LibrarySeeder;
use Database\Seeders\Dummy\PerizinanSeeder;
use Database\Seeders\Dummy\PotonganSiswaSeeder;
use Database\Seeders\Dummy\SchoolSeeder;
use Database\Seeders\Dummy\StudentSeeder;
use Database\Seeders\Dummy\TeacherSeeder;
use Illuminate\Database\Seeder;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SchoolSeeder::class,
            SppMonthlyJenisTagihanSeeder::class,
            TemplatePesanTagihanSeeder::class,
            PelanggaranCatalogSeeder::class,
            PrestasiCatalogSeeder::class,
            PotonganCatalogSeeder::class,
            KamarStatusSantriSeeder::class,
            StudentSeeder::class,
            TeacherSeeder::class,
            AttendanceSeeder::class,
            FinanceSeeder::class,
            PotonganSiswaSeeder::class,
            CashlessSeeder::class,
            LibrarySeeder::class,
            PerizinanSeeder::class,
            SpmbDemoSeeder::class,
            AkademikDemoSeeder::class,
            UjianDemoSeeder::class,
            BookletDemoSeeder::class,
            AlumniDemoSeeder::class,
            TahfidzDemoSeeder::class,
        ]);
    }
}
