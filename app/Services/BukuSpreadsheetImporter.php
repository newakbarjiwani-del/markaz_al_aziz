<?php

namespace App\Services;

use App\Models\Buku;
use App\Models\Sekolah;
use App\Support\BukuSpreadsheetTemplate;
use App\Support\ImportPreviewStatus;
use App\Support\ImportStoreMethod;
use Illuminate\Support\Facades\DB;

class BukuSpreadsheetImporter
{
    /**
     * @param  list<array<string, string>>  $rows
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function preview(?Sekolah $sekolah, array $rows): array
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
    public function importFromPreview(?Sekolah $sekolah, array $previewRows, string $method): array
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
    public function import(?Sekolah $sekolah, array $rows): array
    {
        $preview = $this->preview($sekolah, $rows);

        return $this->importFromPreview($sekolah, $preview['rows'], ImportStoreMethod::CREATE_AND_UPDATE);
    }

    /** @param array<string, string> $record */
    private function analyzeRow(?Sekolah $sekolah, array $record, int $rowNumber): array
    {
        $display = [
            'kode_buku' => trim($record['KODE_BUKU'] ?? '') ?: '-',
            'isbn' => trim($record['ISBN'] ?? '') ?: '-',
            'judul' => trim($record['JUDUL'] ?? '') ?: '-',
            'pengarang' => trim($record['PENGARANG'] ?? '') ?: '-',
            'penerbit' => trim($record['PENERBIT'] ?? '') ?: '-',
            'jumlah' => trim($record['JUMLAH'] ?? '') ?: '0',
        ];

        try {
            $this->validatedPayload($sekolah, $record);
            $judul = trim($record['JUDUL'] ?? '');
            $jumlah = BukuSpreadsheetTemplate::normalizedJumlah($record);
            $display['judul'] = $judul;
            $display['jumlah'] = (string) $jumlah;

            $existing = $this->findExisting($sekolah, $record);

            return [
                'row_number' => $rowNumber,
                'status' => $existing ? ImportPreviewStatus::WILL_UPDATE : ImportPreviewStatus::WILL_CREATE,
                'description' => $existing
                    ? 'Buku sudah terdaftar dan akan diperbarui.'
                    : 'Buku baru akan ditambahkan.',
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
    private function importRow(?Sekolah $sekolah, array $record): string
    {
        $payload = $this->validatedPayload($sekolah, $record);
        $buku = $this->findExisting($sekolah, $record);

        if ($buku) {
            $buku->fill($payload);
            $buku->syncTersediaFromLoans();
            $buku->save();

            return 'updated';
        }

        $buku = new Buku($payload);
        $buku->sekolah_id = $sekolah?->id;
        $buku->tersedia = (int) $payload['keadaan_baik'];
        $buku->save();

        return 'created';
    }

    /**
     * @param  array<string, string>  $record
     * @return array<string, mixed>
     */
    private function validatedPayload(?Sekolah $sekolah, array $record): array
    {
        $judul = trim($record['JUDUL'] ?? '');
        if ($judul === '') {
            throw new \InvalidArgumentException('Judul buku wajib diisi.');
        }

        $jumlah = BukuSpreadsheetTemplate::normalizedJumlah($record);
        if ($jumlah < 1) {
            throw new \InvalidArgumentException('Jumlah eksemplar minimal 1.');
        }

        $keadaan = BukuSpreadsheetTemplate::resolveKeadaan($record, $jumlah);
        $totalKeadaan = $keadaan['baik'] + $keadaan['ringan'] + $keadaan['berat'];

        if ($totalKeadaan !== $jumlah) {
            throw new \InvalidArgumentException('Jumlah keadaan (baik + rusak ringan + rusak berat) harus sama dengan jumlah eksemplar.');
        }

        $isbn = trim($record['ISBN'] ?? '');
        $kodeBuku = trim($record['KODE_BUKU'] ?? '');

        return [
            'isbn' => $isbn !== '' ? $isbn : null,
            'kode_buku' => $kodeBuku !== '' ? $kodeBuku : null,
            'judul' => $judul,
            'pengarang' => trim($record['PENGARANG'] ?? '') ?: null,
            'penerbit' => trim($record['PENERBIT'] ?? '') ?: null,
            'kategori' => trim($record['KATEGORI'] ?? '') ?: null,
            'tahun_terbit' => BukuSpreadsheetTemplate::normalizedTahunTerbit($record),
            'cetak_ke' => BukuSpreadsheetTemplate::normalizedCetakKe($record),
            'jumlah' => $jumlah,
            'keadaan_baik' => $keadaan['baik'],
            'keadaan_rusak_ringan' => $keadaan['ringan'],
            'keadaan_rusak_berat' => $keadaan['berat'],
            'tanggal_penerimaan' => BukuSpreadsheetTemplate::parseTanggalPenerimaan($record),
            'sumber_dana' => trim($record['SUMBER_DANA'] ?? '') ?: null,
            'keterangan' => trim($record['KETERANGAN'] ?? '') ?: null,
        ];
    }

    /** @param array<string, string> $record */
    private function findExisting(?Sekolah $sekolah, array $record): ?Buku
    {
        $isbn = trim($record['ISBN'] ?? '');
        if ($isbn !== '') {
            return Buku::query()->where('isbn_key', $isbn)->first();
        }

        $kodeBuku = trim($record['KODE_BUKU'] ?? '');
        if ($kodeBuku !== '') {
            $query = Buku::query()->where('kode_buku', $kodeBuku);

            if ($sekolah !== null) {
                $query->where('sekolah_id', $sekolah->id);
            } else {
                $query->whereNull('sekolah_id');
            }

            return $query->first();
        }

        $judul = trim($record['JUDUL'] ?? '');
        $query = Buku::query()
            ->whereNull('isbn')
            ->whereNull('kode_buku')
            ->where('judul', $judul);

        if ($sekolah !== null) {
            $query->where('sekolah_id', $sekolah->id);
        } else {
            $query->whereNull('sekolah_id');
        }

        return $query->first();
    }
}
