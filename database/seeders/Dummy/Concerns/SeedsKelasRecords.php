<?php

namespace Database\Seeders\Dummy\Concerns;

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Support\KelasJsonCatalog;
use App\Support\KelasLabel;

trait SeedsKelasRecords
{
    /**
     * @param  array{code: string, unit: string, classes?: list<string>}  $definition
     */
    protected function seedKelasForSchool(Sekolah $sekolah, array $definition, bool $idempotent = false): void
    {
        $jsonRows = KelasJsonCatalog::rowsBySchoolCode()[$definition['code']] ?? [];

        if ($jsonRows !== []) {
            foreach ($jsonRows as $row) {
                $this->createKelasRecord($sekolah, [
                    'kelas' => $row['kelas'],
                    'kelompok' => $row['kelompok'],
                    'name' => $row['name'],
                    'unit' => $row['unit'] ?? $definition['unit'],
                    'wali_kelas' => $row['wali_kelas'],
                    'is_active' => $row['is_active'],
                ], $idempotent);
            }

            return;
        }

        foreach ($definition['classes'] ?? [] as $className) {
            $parts = KelasLabel::parseClassName($className);

            $this->createKelasRecord($sekolah, [
                'kelas' => $parts['kelas'],
                'kelompok' => $parts['kelompok'],
                'name' => $className,
                'unit' => $definition['unit'],
                'wali_kelas' => null,
                'is_active' => true,
            ], $idempotent);
        }
    }

    /**
     * @param  array{
     *     kelas: ?string,
     *     kelompok: ?string,
     *     name: string,
     *     unit: ?string,
     *     wali_kelas: ?string,
     *     is_active: bool
     * }  $data
     */
    private function createKelasRecord(Sekolah $sekolah, array $data, bool $idempotent): void
    {
        $attributes = [
            'kelas' => $data['kelas'],
            'kelompok' => $data['kelompok'],
            'name' => $data['name'],
            'unit' => $data['unit'],
            'jenjang' => null,
            'wali_kelas' => $data['wali_kelas'],
            'is_active' => $data['is_active'],
        ];

        if ($idempotent) {
            Kelas::query()->updateOrCreate(
                [
                    'sekolah_id' => $sekolah->id,
                    'name' => $data['name'],
                ],
                $attributes
            );

            return;
        }

        Kelas::create(array_merge(['sekolah_id' => $sekolah->id], $attributes));
    }
}
