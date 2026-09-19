<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\KartuGuru;
use App\Models\ProfilGuru;
use App\Models\Sekolah;
use App\Support\ImportPreviewStatus;
use App\Support\ImportStoreMethod;
use App\Support\TeacherSpreadsheetTemplate;
use Illuminate\Support\Facades\DB;

class TeacherSpreadsheetImporter
{
    /**
     * @param  list<array<string, string>>  $rows
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function preview(Sekolah $sekolah, array $rows): array
    {
        $previewRows = [];

        foreach ($rows as $index => $record) {
            $previewRows[] = $this->analyzeRow($sekolah, $record, $index + 2);
        }

        return [
            'rows' => $previewRows,
            'summary' => $this->summarizePreview($previewRows),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $previewRows
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function importFromPreview(Sekolah $sekolah, array $previewRows, string $method): array
    {
        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($sekolah, $previewRows, $method, &$result) {
            foreach ($previewRows as $previewRow) {
                $rowNumber = (int) ($previewRow['row_number'] ?? 0);
                $status = (int) ($previewRow['status'] ?? ImportPreviewStatus::CANT_STORE);
                $record = $previewRow['record'] ?? [];

                if (! is_array($record)) {
                    $result['skipped']++;
                    $result['errors'][] = 'Baris '.$rowNumber.': data baris tidak valid.';

                    continue;
                }

                if (! ImportStoreMethod::allows($status, $method)) {
                    $result['skipped']++;
                    $result['errors'][] = 'Baris '.$rowNumber.': '.ImportStoreMethod::skipReason($status, $method);

                    continue;
                }

                try {
                    $outcome = $this->importRow($sekolah, $record);
                    $result[$outcome]++;
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = 'Baris '.$rowNumber.': '.$e->getMessage();
                }
            }
        });

        return $result;
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function import(Sekolah $sekolah, array $rows): array
    {
        $preview = $this->preview($sekolah, $rows);

        return $this->importFromPreview($sekolah, $preview['rows'], ImportStoreMethod::CREATE_AND_UPDATE);
    }

    /** @param array<string, string> $record */
    private function analyzeRow(Sekolah $sekolah, array $record, int $rowNumber): array
    {
        $display = [
            'nip' => trim($record['NIP'] ?? '') ?: '-',
            'nama' => trim($record['NAMA'] ?? '') ?: '-',
            'jabatan' => trim($record['JABATAN'] ?? '') ?: '-',
        ];

        try {
            $nip = trim($record['NIP'] ?? '');
            if ($nip === '') {
                throw new \InvalidArgumentException('NIP wajib diisi.');
            }

            $name = trim($record['NAMA'] ?? '');
            if ($name === '') {
                throw new \InvalidArgumentException('Nama wajib diisi.');
            }

            TeacherSpreadsheetTemplate::normalizedStatus($record);

            $display['nip'] = $nip;
            $display['nama'] = $name;
            $display['jabatan'] = trim($record['JABATAN'] ?? '') ?: '-';

            $exists = Guru::query()
                ->where('sekolah_id', $sekolah->id)
                ->where('nip', $nip)
                ->exists();

            return [
                'row_number' => $rowNumber,
                'status' => $exists ? ImportPreviewStatus::WILL_UPDATE : ImportPreviewStatus::WILL_CREATE,
                'description' => $exists
                    ? 'Data guru dengan NIP ini sudah ada dan akan diperbarui.'
                    : 'Data guru baru akan ditambahkan.',
                'display' => $display,
                'record' => $record,
            ];
        } catch (\Throwable $e) {
            return [
                'row_number' => $rowNumber,
                'status' => ImportPreviewStatus::CANT_STORE,
                'description' => $e->getMessage(),
                'display' => $display,
                'record' => $record,
            ];
        }
    }

    /** @param list<array<string, mixed>> $previewRows */
    private function summarizePreview(array $previewRows): array
    {
        $summary = [
            'total' => count($previewRows),
            'invalid' => 0,
            'will_create' => 0,
            'will_update' => 0,
        ];

        foreach ($previewRows as $row) {
            match ((int) ($row['status'] ?? ImportPreviewStatus::CANT_STORE)) {
                ImportPreviewStatus::WILL_CREATE => $summary['will_create']++,
                ImportPreviewStatus::WILL_UPDATE => $summary['will_update']++,
                default => $summary['invalid']++,
            };
        }

        return $summary;
    }

    /** @param array<string, string> $record */
    private function importRow(Sekolah $sekolah, array $record): string
    {
        $nip = trim($record['NIP'] ?? '');
        if ($nip === '') {
            throw new \InvalidArgumentException('NIP wajib diisi.');
        }

        $name = trim($record['NAMA'] ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('Nama wajib diisi.');
        }

        $payload = [
            'name' => $name,
            'jabatan' => trim($record['JABATAN'] ?? '') ?: null,
            'jenis_guru' => trim($record['JENIS_GURU'] ?? '') ?: null,
            'golongan' => trim($record['GOLONGAN'] ?? '') ?: null,
            'phone' => TeacherSpreadsheetTemplate::normalizedPhone($record),
            'status' => TeacherSpreadsheetTemplate::normalizedStatus($record),
        ];

        $guru = Guru::query()
            ->where('sekolah_id', $sekolah->id)
            ->where('nip', $nip)
            ->first();

        if ($guru) {
            $guru->update($payload);

            return 'updated';
        }

        $guru = Guru::create(array_merge($payload, [
            'sekolah_id' => $sekolah->id,
            'nip' => $nip,
        ]));

        ProfilGuru::firstOrCreate(['guru_id' => $guru->id]);
        KartuGuru::provisionFor($guru);

        return 'created';
    }
}
