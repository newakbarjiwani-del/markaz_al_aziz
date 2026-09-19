<?php

namespace Database\Seeders\Dummy;

class DummySchoolCatalog
{
    public const STUDENTS_PER_CLASS = 10;

    public const TEACHERS_PER_SCHOOL = 10;

    /**
     * Cap how many kelas receive dummy students (kelas.json can list 90+ for Takhasus).
     * Keeps demo DB + FinanceSeeder within typical PHP memory_limit (128M).
     */
    public const MAX_CLASSES_WITH_STUDENTS = 8;

    /** Cap finance tagihan/ledger rows per sekolah (0 = all seeded students). */
    public const FINANCE_STUDENTS_PER_SCHOOL = 40;

    /** @return list<string> */
    public static function fallbackClasses(string $schoolCode): array
    {
        return [];
    }

    /** @return array<string, array{code: string, name: string, unit: string, address: string, phone: string, classes: list<string>}> */
    public static function schools(): array
    {
        return [
            'paud' => [
                'code' => 'paud',
                'name' => 'Pendidikan Anak Usia Dini (PAUD)',
                'unit' => 'PAUD',
                'address' => 'Pekanbaru, Riau, Indonesia',
                'phone' => '0761123401',
                'classes' => [],
            ],
            'mts' => [
                'code' => 'mts',
                'name' => 'Madrasah Tsanawiyah (MTs)',
                'unit' => 'MTs',
                'address' => 'Pekanbaru, Riau, Indonesia',
                'phone' => '0761123402',
                'classes' => [],
            ],
            'ma' => [
                'code' => 'ma',
                'name' => 'Madrasah Aliyah (MA)',
                'unit' => 'MA',
                'address' => 'Pekanbaru, Riau, Indonesia',
                'phone' => '0761123403',
                'classes' => [],
            ],
            'takhasus' => [
                'code' => 'takhasus',
                'name' => 'Pondok Pesantren (Takhasus)',
                'unit' => 'Takhasus',
                'address' => 'Pekanbaru, Riau, Indonesia',
                'phone' => '0761123404',
                'classes' => [],
            ],
        ];
    }

    public static function demoSchoolCode(): string
    {
        return 'ma';
    }
}
