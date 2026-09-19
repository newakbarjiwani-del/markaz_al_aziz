<?php

namespace App\Services;

use App\Models\Sekolah;
use App\Models\Siswa;
use App\Support\ImportPreviewStatus;
use App\Support\ImportStoreMethod;
use App\Support\SiswaCatatanSpreadsheetTemplate;
use App\Support\VirtualAccountNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Shared importer for student achievements / violations (Excel, NIS-keyed).
 * Subclasses declare the target model via {@see modelClass()}.
 */
abstract class SiswaCatatanSpreadsheetImporter
{
    /** @var array<string, int> */
    private array $seenKeys = [];

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Human-readable type label, e.g. "Prestasi". */
    abstract public function label(): string;

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function preview(Sekolah $sekolah, array $rows): array
    {
        $this->seenKeys = [];
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
                    $this->importRow($sekolah, $record);
                    $result['created']++;
                } catch (\Throwable $e) {
                    $result['skipped']++;
                    $result['errors'][] = 'Baris '.$rowNumber.': '.$e->getMessage();
                }
            }
        });

        return $result;
    }

    /** @param array<string, string> $record */
    protected function analyzeRow(Sekolah $sekolah, array $record, int $rowNumber): array
    {
        $display = [
            'nis' => trim($record['NIS'] ?? '') ?: '-',
            'nama' => '-',
            'kelas' => '-',
            'judul' => trim($record['JUDUL'] ?? '') ?: '-',
            'tanggal' => trim($record['TANGGAL'] ?? '') ?: '-',
            'point' => trim($record['POINT'] ?? '') ?: '0',
        ];

        try {
            $context = $this->resolveRowContext($sekolah, $record);

            $display['nis'] = $context['siswa']->nis;
            $display['nama'] = $context['siswa']->name;
            $display['kelas'] = $context['siswa']->kelas?->name ?? '-';
            $display['judul'] = $context['judul'];
            $display['tanggal'] = $context['tanggal'];
            $display['point'] = (string) $context['point'];

            $compositeKey = implode('|', [$context['siswa']->id, $context['judul'], $context['tanggal']]);

            if (isset($this->seenKeys[$compositeKey])) {
                throw new \InvalidArgumentException(
                    'Duplikat baris dengan siswa, judul, dan tanggal yang sama (baris '.$this->seenKeys[$compositeKey].').'
                );
            }

            $this->seenKeys[$compositeKey] = $rowNumber;

            return [
                'row_number' => $rowNumber,
                'status' => ImportPreviewStatus::WILL_CREATE,
                'description' => $this->label().' baru akan ditambahkan.',
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

    /**
     * @param  array<string, string>  $record
     * @return array{siswa: Siswa, judul: string, tanggal: string, point: int}
     */
    protected function resolveRowContext(Sekolah $sekolah, array $record): array
    {
        $nis = VirtualAccountNumber::requireValidNis(
            SiswaCatatanSpreadsheetTemplate::normalizedNis($record)
        );

        $siswa = Siswa::query()
            ->where('sekolah_id', $sekolah->id)
            ->where('nis', $nis)
            ->first();

        if ($siswa === null) {
            throw new \InvalidArgumentException('Siswa dengan NIS '.$nis.' tidak ditemukan di sekolah ini.');
        }

        $judul = trim($record['JUDUL'] ?? '');

        if ($judul === '') {
            throw new \InvalidArgumentException('Judul wajib diisi.');
        }

        $tanggal = SiswaCatatanSpreadsheetTemplate::normalizedTanggal($record);

        if ($tanggal === null) {
            throw new \InvalidArgumentException('Tanggal tidak valid. Gunakan format YYYY-MM-DD atau DD/MM/YYYY.');
        }

        $point = SiswaCatatanSpreadsheetTemplate::normalizedPoint($record) ?? 0;

        if ($point < 0 || $point > 99999) {
            throw new \InvalidArgumentException('Point harus berupa angka 0–99999.');
        }

        return [
            'siswa' => $siswa,
            'judul' => $judul,
            'tanggal' => $tanggal,
            'point' => $point,
        ];
    }

    /** @param array<string, string> $record */
    protected function importRow(Sekolah $sekolah, array $record): void
    {
        $context = $this->resolveRowContext($sekolah, $record);
        $model = $this->modelClass();

        $model::create([
            'siswa_id' => $context['siswa']->id,
            'sekolah_id' => $sekolah->id,
            'judul' => $context['judul'],
            'keterangan' => trim($record['KETERANGAN'] ?? '') ?: null,
            'tanggal' => $context['tanggal'],
            'point' => $context['point'],
            'reported_by' => null,
        ]);
    }

    /** @param list<array<string, mixed>> $previewRows */
    private function summarizePreview(array $previewRows): array
    {
        $summary = [
            'total' => count($previewRows),
            'will_create' => 0,
            'will_update' => 0,
            'invalid' => 0,
        ];

        foreach ($previewRows as $row) {
            $status = (int) ($row['status'] ?? ImportPreviewStatus::CANT_STORE);

            match ($status) {
                ImportPreviewStatus::WILL_CREATE => $summary['will_create']++,
                ImportPreviewStatus::WILL_UPDATE => $summary['will_update']++,
                default => $summary['invalid']++,
            };
        }

        return $summary;
    }
}
