<?php

namespace App\Services;

use App\Models\Dompet;
use App\Models\KartuSiswa;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\ProfilSiswa;
use App\Models\SaldoKeuangan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Services\Finance\FinanceVaResolver;
use App\Support\ImportPreviewStatus;
use App\Support\ImportStoreMethod;
use App\Support\KelasLabel;
use App\Support\SiswaStatus;
use App\Support\StudentSpreadsheetTemplate;
use App\Support\VirtualAccountNumber;
use App\Support\WhatsAppLink;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class StudentSpreadsheetImporter
{
    /** @var array<string, OrangTua> */
    private array $parentsByPhone = [];

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

        $previewRows = $this->appendVaSuffixWarnings($previewRows);

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

        $this->parentsByPhone = [];

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
            'nis' => trim($record['NIS'] ?? '') ?: '-',
            'nama' => trim($record['NAMA'] ?? '') ?: '-',
            'kelas' => StudentSpreadsheetTemplate::classNameFromRow($record),
            'tanggal_lahir' => trim($record['TANGGAL_LAHIR'] ?? '') ?: '-',
        ];

        try {
            $nis = VirtualAccountNumber::requireValidNis(
                StudentSpreadsheetTemplate::normalizedNis($record)
            );

            $name = trim($record['NAMA'] ?? '');
            if ($name === '') {
                throw new \InvalidArgumentException('Nama wajib diisi.');
            }

            $display['nis'] = $nis;
            $display['nama'] = $name;
            $display['tanggal_lahir'] = $this->normalizeBirthDate($record['TANGGAL_LAHIR'] ?? null) ?? '-';

            $this->resolveKelas($sekolah, $record);

            $existing = Siswa::query()
                ->with(['kelas', 'profil'])
                ->where('sekolah_id', $sekolah->id)
                ->where('nis', $nis)
                ->first();

            $nextValues = $this->buildComparableValues($record);
            $description = $existing
                ? 'Data siswa dengan NIS ini sudah ada dan akan diperbarui.'
                : 'Data siswa baru akan ditambahkan.';

            $vaWarning = app(FinanceVaResolver::class)->vaSuffixCollisionWarning(
                $nis,
                $existing?->id
            );

            if ($vaWarning !== null) {
                $description .= ' '.$vaWarning;
            }

            return [
                'row_number' => $rowNumber,
                'status' => $existing ? ImportPreviewStatus::WILL_UPDATE : ImportPreviewStatus::WILL_CREATE,
                'description' => $description,
                'display' => $display,
                'comparison' => [
                    'before' => $existing ? $this->existingComparableValues($existing) : null,
                    'after' => $nextValues,
                ],
                'record' => $record,
                'va_suffix' => VirtualAccountNumber::nisSuffix($nis),
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
     * Flag rows in the same import file that share a VA suffix with each other.
     *
     * @param  list<array<string, mixed>>  $previewRows
     * @return list<array<string, mixed>>
     */
    private function appendVaSuffixWarnings(array $previewRows): array
    {
        $suffixOwners = [];

        foreach ($previewRows as $index => $row) {
            $suffix = $row['va_suffix'] ?? null;
            if (! is_string($suffix) || $suffix === '') {
                continue;
            }
            if ((int) ($row['status'] ?? ImportPreviewStatus::CANT_STORE) === ImportPreviewStatus::CANT_STORE) {
                continue;
            }
            $suffixOwners[$suffix][] = $index;
        }

        foreach ($suffixOwners as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            $labels = [];
            foreach ($indexes as $index) {
                $display = $previewRows[$index]['display'] ?? [];
                $labels[] = 'baris '.$previewRows[$index]['row_number']
                    .' ('.($display['nama'] ?? '-').' · NIS '.($display['nis'] ?? '-').')';
            }

            $batchWarning = 'Peringatan: beberapa baris dalam file ini memakai 10 digit terakhir NIS yang sama'
                .' ('.$labels[0].' dan '.implode(', ', array_slice($labels, 1)).').'
                .' No. VA akan bentrok — pembayaran VA online bisa gagal.';

            foreach ($indexes as $index) {
                $existing = (string) ($previewRows[$index]['description'] ?? '');
                if (! str_contains($existing, '10 digit terakhir NIS')) {
                    $previewRows[$index]['description'] = trim($existing.' '.$batchWarning);
                } else {
                    $previewRows[$index]['description'] = trim($existing.' Juga bentrok dengan baris lain dalam file ini.');
                }
            }
        }

        return $previewRows;
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
        $nis = VirtualAccountNumber::requireValidNis(
            StudentSpreadsheetTemplate::normalizedNis($record)
        );

        $name = trim($record['NAMA'] ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('Nama wajib diisi.');
        }

        $kelas = $this->resolveKelas($sekolah, $record);
        $gender = StudentSpreadsheetTemplate::normalizedGender($record);
        $birthPlace = trim($record['TEMPAT_LAHIR'] ?? '') ?: null;
        $birthDate = $this->normalizeBirthDate($record['TANGGAL_LAHIR'] ?? null);
        $address = trim($record['ALAMAT'] ?? '') ?: null;
        $wali = trim($record['WALI'] ?? '');
        $status = SiswaStatus::normalize($record['STATUS'] ?? null)
            ?? SiswaStatus::ACTIVE;
        $nomorPendaftaran = trim($record['NODAF'] ?? '') ?: null;
        $extraFields = array_filter([
            'unit' => trim($record['UNIT'] ?? '') ?: null,
            'kelas_kelompok' => trim($record['KELOMPOK'] ?? '') ?: null,
            'angkatan' => trim($record['ANGKATAN'] ?? '') ?: null,
            'nama_wali' => $wali ?: null,
        ]);

        $siswa = Siswa::query()
            ->where('sekolah_id', $sekolah->id)
            ->where('nis', $nis)
            ->first();

        if ($nomorPendaftaran !== null) {
            $taken = Siswa::query()
                ->where('nomor_pendaftaran', $nomorPendaftaran)
                ->when($siswa !== null, fn ($q) => $q->where('id', '!=', $siswa->id))
                ->exists();

            if ($taken) {
                throw new \InvalidArgumentException(
                    'Nomor pendaftaran «'.$nomorPendaftaran.'» sudah dipakai siswa lain.'
                );
            }
        }

        if ($siswa) {
            $siswa->update([
                'kelas_id' => $kelas->id,
                'nomor_pendaftaran' => $nomorPendaftaran ?? $siswa->nomor_pendaftaran,
                'name' => $name,
                'gender' => $gender,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'address' => $address,
                'status' => $status,
            ]);

            $profil = ProfilSiswa::firstOrCreate(['siswa_id' => $siswa->id]);
            $profil->update([
                'extra_fields' => array_merge($profil->extra_fields ?? [], $extraFields),
            ]);

            $this->linkGuardian($sekolah, $siswa, $wali, $address, $record);

            return 'updated';
        }

        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => $nis,
            'nomor_pendaftaran' => $nomorPendaftaran,
            'name' => $name,
            'gender' => $gender,
            'birth_place' => $birthPlace,
            'birth_date' => $birthDate,
            'address' => $address,
            'status' => $status,
        ]);

        ProfilSiswa::create([
            'siswa_id' => $siswa->id,
            'extra_fields' => array_merge([
                'nama_panggilan' => explode(' ', $name)[0] ?? $name,
            ], $extraFields),
        ]);

        Dompet::create(['siswa_id' => $siswa->id]);
        SaldoKeuangan::create(['siswa_id' => $siswa->id, 'balance' => 0]);
        KartuSiswa::provisionFor($siswa);

        $this->linkGuardian($sekolah, $siswa, $wali, $address, $record);

        return 'created';
    }

    /** @param array<string, string> $record */
    private function resolveKelas(Sekolah $sekolah, array $record): Kelas
    {
        $criteria = $this->kelasLookupCriteria($sekolah, $record);

        $kelas = Kelas::query()->where($criteria['lookup'])->first();

        if ($kelas === null) {
            throw new \InvalidArgumentException(
                'Kelas «'.$criteria['display'].'» tidak ditemukan di Master Data. Tambahkan kelas terlebih dahulu.'
            );
        }

        return $kelas;
    }

    /**
     * @param  array<string, string>  $record
     * @return array{lookup: array<string, mixed>, display: string}
     */
    private function kelasLookupCriteria(Sekolah $sekolah, array $record): array
    {
        $kelasNumber = (int) ($record['KELAS'] ?? 0);
        $kelompok = trim($record['KELOMPOK'] ?? '');
        $kelasValue = KelasLabel::kelasFromImportNumber($kelasNumber);
        $displayName = StudentSpreadsheetTemplate::classNameFromRow($record);

        $lookup = [
            'sekolah_id' => $sekolah->id,
        ];

        if ($kelasValue !== null && $kelompok !== '') {
            $lookup['kelas'] = $kelasValue;
            $lookup['kelompok'] = $kelompok;
        } else {
            $lookup['name'] = $displayName;
        }

        return [
            'lookup' => $lookup,
            'display' => $displayName,
        ];
    }

    /** @param array<string, string> $record */
    private function linkGuardian(Sekolah $sekolah, Siswa $siswa, string $wali, ?string $address, array $record): void
    {
        $guardianLastName = $wali !== ''
            ? $wali
            : (collect(explode(' ', $siswa->name))->last() ?: 'Wali');

        $normalizedPhone = WhatsAppLink::normalizePhone(trim($record['NOMOR_HP'] ?? ''));
        $existingParent = $siswa->orangTua()->first();

        if ($existingParent) {
            $updates = array_filter([
                'alamat' => $address,
            ], fn ($value) => $value !== null && $value !== '');

            if ($wali !== '') {
                $updates['nama_wali'] = $wali;
            }

            if ($normalizedPhone !== null) {
                if ($wali !== '') {
                    $updates['telepon_wali'] = $normalizedPhone;
                } elseif (! filled($existingParent->telepon_ayah)) {
                    $updates['telepon_ayah'] = $normalizedPhone;
                } elseif (! filled($existingParent->telepon_ibu)
                    && $existingParent->telepon_ayah !== $normalizedPhone) {
                    $updates['telepon_ibu'] = $normalizedPhone;
                }
            }

            if ($updates !== []) {
                $existingParent->update($updates);
            }

            return;
        }

        $parentKey = $normalizedPhone !== null
            ? $sekolah->id.':'.$normalizedPhone
            : $sekolah->id.':wali:'.md5(strtolower(trim($wali !== '' ? $wali : $guardianLastName)).'|'.($address ?? ''));

        if (! isset($this->parentsByPhone[$parentKey])) {
            $payload = [
                'sekolah_id' => $sekolah->id,
                'alamat' => $address,
                'status' => 'aktif',
            ];

            if ($wali !== '') {
                $payload['nama_wali'] = $wali;
                if ($normalizedPhone !== null) {
                    $payload['telepon_wali'] = $normalizedPhone;
                }
            } else {
                $payload['nama_ayah'] = $guardianLastName;
                $payload['nama_ibu'] = $guardianLastName;
                if ($normalizedPhone !== null) {
                    $payload['telepon_ayah'] = $normalizedPhone;
                }
            }

            $this->parentsByPhone[$parentKey] = OrangTua::create($payload);
        }

        $targetParent = $this->parentsByPhone[$parentKey];
        $existingLink = $siswa->orangTua()->first();

        if ($existingLink && (int) $existingLink->id !== (int) $targetParent->id) {
            return;
        }

        if (! $existingLink) {
            $siswa->orangTua()->attach($targetParent->id);
        }
    }

    /** @param array<string, string> $record */
    private function buildComparableValues(array $record): array
    {
        return [
            'name' => trim($record['NAMA'] ?? '') ?: '-',
            'kelas' => StudentSpreadsheetTemplate::classNameFromRow($record),
            'gender' => StudentSpreadsheetTemplate::normalizedGender($record),
            'birth_place' => trim($record['TEMPAT_LAHIR'] ?? '') ?: '-',
            'birth_date' => $this->normalizeBirthDate($record['TANGGAL_LAHIR'] ?? null) ?? '-',
            'address' => trim($record['ALAMAT'] ?? '') ?: '-',
            'status' => SiswaStatus::label(
                SiswaStatus::normalize($record['STATUS'] ?? null) ?? SiswaStatus::ACTIVE
            ),
            'wali' => trim($record['WALI'] ?? '') ?: '-',
        ];
    }

    private function existingComparableValues(Siswa $siswa): array
    {
        return [
            'name' => $siswa->name ?: '-',
            'kelas' => $siswa->kelas?->name ?: '-',
            'gender' => $siswa->gender ?: '-',
            'birth_place' => $siswa->birth_place ?: '-',
            'birth_date' => $siswa->birth_date?->format('Y-m-d') ?: '-',
            'address' => $siswa->address ?: '-',
            'status' => $siswa->statusLabel(),
            'wali' => data_get($siswa->profil?->extra_fields, 'nama_wali', '-'),
        ];
    }

    private function normalizeBirthDate(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            try {
                return Carbon::instance(Date::excelToDateTimeObject((float) $raw))
                    ->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
