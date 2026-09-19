<?php

namespace App\Services;

use App\Models\JenisTagihan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Support\ImportPreviewStatus;
use App\Support\ImportStoreMethod;
use App\Support\TagihanPeriode;
use App\Support\TagihanSpreadsheetTemplate;
use App\Support\VirtualAccountNumber;
use Illuminate\Support\Facades\DB;

class TagihanSpreadsheetImporter
{
    /**
     * @param  list<array<string, string>>  $rows
     * @return array{rows: list<array<string, mixed>>, summary: array<string, int>}
     */
    public function preview(Sekolah $sekolah, array $rows): array
    {
        $previewRows = [];
        $seenKeys = [];

        foreach ($rows as $index => $record) {
            $previewRows[] = $this->analyzeRow($sekolah, $record, $index + 2, $seenKeys);
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
            $potonganService = app(\App\Services\Finance\PotonganTagihanService::class);

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
                    $outcome = $this->importRow($sekolah, $record, $potonganService);
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
     * @param  array<string, int>  $seenKeys
     * @param  array<string, string>  $record
     * @return array<string, mixed>
     */
    private function analyzeRow(Sekolah $sekolah, array $record, int $rowNumber, array &$seenKeys): array
    {
        $display = [
            'nis' => trim($record['NIS'] ?? '') ?: '-',
            'nama' => '-',
            'jenis' => trim($record['JENIS_TAGIHAN'] ?? '') ?: '-',
            'periode' => trim($record['PERIODE'] ?? '') ?: '-',
            'tahun_akademik' => trim($record['TAHUN_AKADEMIK'] ?? '') ?: '-',
            'tagihan' => trim($record['TAGIHAN'] ?? '') ?: '-',
        ];

        try {
            $context = $this->resolveRowContext($sekolah, $record);

            $display['nis'] = $context['siswa']->nis;
            $display['nama'] = $context['siswa']->name;
            $display['jenis'] = $context['jenis']->name;
            $display['periode'] = TagihanPeriode::display($context['periode'], false);
            $display['tahun_akademik'] = $context['tahun']->name;
            $display['tagihan'] = number_format($context['amount'], 0, ',', '.');

            $compositeKey = implode('|', [
                $context['siswa']->id,
                $context['jenis']->id,
                $context['periode'],
                $context['tahun']->id,
            ]);

            if (isset($seenKeys[$compositeKey])) {
                throw new \InvalidArgumentException(
                    'Duplikat baris dengan tagihan yang sama (baris '.$seenKeys[$compositeKey].').'
                );
            }

            $seenKeys[$compositeKey] = $rowNumber;

            $existing = $this->findExistingTagihan(
                $context['siswa'],
                $context['jenis'],
                $context['periode'],
                (int) $context['tahun']->id
            );

            if ($existing === null) {
                return [
                    'row_number' => $rowNumber,
                    'status' => ImportPreviewStatus::WILL_CREATE,
                    'description' => 'Tagihan baru akan ditambahkan.',
                    'display' => $display,
                    'record' => $record,
                ];
            }

            if ($existing->isImportLocked()) {
                throw new \InvalidArgumentException($existing->billingLockMessage());
            }

            $existingAmount = (int) $existing->amount;
            $nextAmount = $context['amount'];

            if ($existingAmount === $nextAmount) {
                return [
                    'row_number' => $rowNumber,
                    'status' => ImportPreviewStatus::CANT_STORE,
                    'description' => 'Tagihan dengan kombinasi siswa, jenis, periode, dan tahun akademik sudah ada dengan nominal sama.',
                    'display' => $display,
                    'record' => $record,
                ];
            }

            return [
                'row_number' => $rowNumber,
                'status' => ImportPreviewStatus::WILL_UPDATE,
                'description' => 'Nominal tagihan akan diperbarui dari Rp '
                    .number_format($existingAmount, 0, ',', '.')
                    .' menjadi Rp '.number_format($nextAmount, 0, ',', '.').'.',
                'display' => $display,
                'comparison' => [
                    'before' => ['tagihan' => $existingAmount],
                    'after' => ['tagihan' => $nextAmount],
                ],
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

    /** @param array<string, string> $record */
    private function importRow(Sekolah $sekolah, array $record, \App\Services\Finance\PotonganTagihanService $potonganService): string
    {
        $context = $this->resolveRowContext($sekolah, $record);

        $existing = $this->findExistingTagihan(
            $context['siswa'],
            $context['jenis'],
            $context['periode'],
            (int) $context['tahun']->id
        );

        if ($existing !== null) {
            if ($existing->isImportLocked()) {
                throw new \InvalidArgumentException($existing->billingLockMessage());
            }

            $existing->update(['amount' => $context['amount']]);

            return 'updated';
        }

        $tagihan = Tagihan::create([
            'sekolah_id' => $sekolah->id,
            'siswa_id' => $context['siswa']->id,
            'tahun_akademik_id' => $context['tahun']->id,
            'jenis_tagihan_id' => $context['jenis']->id,
            'jenis' => $context['jenis']->name,
            'amount' => $context['amount'],
            'periode' => $context['periode'],
            'paid' => 0,
            'status' => Tagihan::STATUS_UNPAID,
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $potonganService->autoApply($tagihan->fresh(['siswa']));

        return 'created';
    }

    /**
     * @param  array<string, string>  $record
     * @return array{
     *     siswa: Siswa,
     *     jenis: JenisTagihan,
     *     tahun: TahunAkademik,
     *     periode: int,
     *     amount: int
     * }
     */
    private function resolveRowContext(Sekolah $sekolah, array $record): array
    {
        $nis = VirtualAccountNumber::requireValidNis(
            TagihanSpreadsheetTemplate::normalizedNis($record)
        );

        $siswa = Siswa::query()
            ->where('sekolah_id', $sekolah->id)
            ->where('nis', $nis)
            ->first();

        if ($siswa === null) {
            throw new \InvalidArgumentException('Siswa dengan NIS '.$nis.' tidak ditemukan di sekolah ini.');
        }

        $amount = TagihanSpreadsheetTemplate::normalizedAmount($record);

        if ($amount === null) {
            throw new \InvalidArgumentException('Nominal tagihan wajib diisi.');
        }

        if ($amount < 1000) {
            throw new \InvalidArgumentException('Nominal tagihan minimal Rp 1.000.');
        }

        $jenisName = trim($record['JENIS_TAGIHAN'] ?? '');

        if ($jenisName === '') {
            throw new \InvalidArgumentException('Jenis tagihan wajib diisi.');
        }

        $jenis = JenisTagihan::query()
            ->active()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($jenisName)])
            ->first();

        if ($jenis === null) {
            throw new \InvalidArgumentException('Jenis tagihan "'.$jenisName.'" tidak ditemukan di master data.');
        }

        $periodeRaw = trim($record['PERIODE'] ?? '');

        if ($periodeRaw === '') {
            throw new \InvalidArgumentException('Periode wajib diisi (mis. 2026-07 atau 202607).');
        }

        $periode = TagihanPeriode::normalize($periodeRaw);

        if ($periode === null) {
            throw new \InvalidArgumentException('Periode tidak valid. Gunakan bulan tagihan (mis. 2026-01 → 202601, 2026-07 → 202707).');
        }

        $tahunName = trim($record['TAHUN_AKADEMIK'] ?? '');

        if ($tahunName === '') {
            throw new \InvalidArgumentException('Tahun akademik wajib diisi (mis. 2025/2026).');
        }

        $tahun = TahunAkademik::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($tahunName)])
            ->first();

        if ($tahun === null) {
            throw new \InvalidArgumentException('Tahun akademik "'.$tahunName.'" tidak ditemukan di master data.');
        }

        $periodeLabel = TagihanPeriode::academicYearLabel($periode);

        if (strcasecmp($tahun->name, $periodeLabel) !== 0) {
            throw new \InvalidArgumentException(
                'Periode '.TagihanPeriode::display($periode, false).' tidak sesuai dengan tahun akademik '.$tahun->name.'.'
            );
        }

        return [
            'siswa' => $siswa,
            'jenis' => $jenis,
            'tahun' => $tahun,
            'periode' => $periode,
            'amount' => $amount,
        ];
    }

    private function findExistingTagihan(
        Siswa $siswa,
        JenisTagihan $jenis,
        int $periode,
        int $tahunAkademikId
    ): ?Tagihan {
        return Tagihan::query()
            ->where('siswa_id', $siswa->id)
            ->where('tahun_akademik_id', $tahunAkademikId)
            ->where('periode', $periode)
            ->where(function ($query) use ($jenis) {
                $query->where('jenis_tagihan_id', $jenis->id)
                    ->orWhere('jenis', $jenis->name);
            })
            ->first();
    }

    /**
     * @param  list<array<string, mixed>>  $previewRows
     * @return array{total: int, will_create: int, will_update: int, invalid: int}
     */
    private function summarizePreview(array $previewRows): array
    {
        $summary = [
            'total' => count($previewRows),
            'will_create' => 0,
            'will_update' => 0,
            'invalid' => 0,
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
}
