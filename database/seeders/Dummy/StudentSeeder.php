<?php

namespace Database\Seeders\Dummy;

use App\Models\Kelas;
use App\Models\Sekolah;
use App\Models\TahunAkademik;
use Database\Seeders\Dummy\Concerns\SeedsStudentRecords;
use App\Support\StudentSpreadsheetTemplate;
use App\Support\WhatsAppLink;
use Database\Seeders\Dummy\DummySchoolCatalog;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    use SeedsStudentRecords;

    public function run(): void
    {
        $faker = FakerFactory::create('id_ID');

        $rowsBySchoolCode = $this->spreadsheetRowsBySchoolCode();

        $tahunAktif = TahunAkademik::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->first()
            ?? TahunAkademik::query()->orderByDesc('id')->first();

        foreach (Sekolah::query()->orderBy('id')->get() as $sekolah) {
            if (in_array($sekolah->code, ['mts', 'ma'], true) && isset($rowsBySchoolCode[$sekolah->code])) {
                $this->seedFromSpreadsheetRows($sekolah, $tahunAktif, $rowsBySchoolCode[$sekolah->code]);
                continue;
            }

            $kelasList = Kelas::query()
                ->where('sekolah_id', $sekolah->id)
                ->orderBy('id')
                ->limit(DummySchoolCatalog::MAX_CLASSES_WITH_STUDENTS)
                ->get();

            $studentCounter = 1;

            foreach ($kelasList as $kelas) {
                for ($i = 1; $i <= DummySchoolCatalog::STUDENTS_PER_CLASS; $i++) {
                    $gender = rand(0, 1) ? 'L' : 'P';
                    $firstName = $gender === 'L'
                        ? $faker->firstNameMale()
                        : $faker->firstNameFemale();
                    $lastName = $faker->lastName();
                    $name = trim($firstName.' '.$lastName);
                    $fatherName = $lastName;
                    $motherName = $lastName;

                    $this->seedStudentWithRecords(
                        sekolah: $sekolah,
                        kelas: $kelas,
                        tahunAktif: $tahunAktif,
                        nis: sprintf('%d%06d', $sekolah->id, $studentCounter),
                        name: $name,
                        gender: $gender,
                        fatherName: $fatherName,
                        motherName: $motherName,
                    );

                    $studentCounter++;
                }
            }
        }
    }

    /** @return array<string, list<array<string, string>>> */
    private function spreadsheetRowsBySchoolCode(): array
    {
        $grouped = [];

        foreach (StudentSpreadsheetTemplate::parseRows() as $record) {
            $kelasNumber = (int) ($record['KELAS'] ?? 0);
            $schoolCode = StudentSpreadsheetTemplate::schoolCodeFromKelasNumber($kelasNumber);

            if ($schoolCode === null) {
                continue;
            }

            $grouped[$schoolCode][] = $record;
        }

        return $grouped;
    }

    /** @param list<array<string, string>> $rows */
    private function seedFromSpreadsheetRows(Sekolah $sekolah, TahunAkademik $tahunAktif, array $rows): void
    {
        foreach ($rows as $record) {
            $className = StudentSpreadsheetTemplate::classNameFromRow($record);
            $kelasKelompok = trim($record['KELOMPOK'] ?? '-');
            $kelasNumber = (int) ($record['KELAS'] ?? 0);
            $kelasValue = \App\Support\KelasLabel::kelasFromImportNumber($kelasNumber);
            $kelompokValue = $kelasKelompok !== '' && $kelasKelompok !== '-' ? $kelasKelompok : null;

            if ($kelasValue !== null && $kelompokValue !== null) {
                $kelasLookup['kelas'] = $kelasValue;
                $kelasLookup['kelompok'] = $kelompokValue;
            } else {
                $kelasLookup['name'] = $className;
            }

            $kelas = Kelas::firstOrCreate(
                $kelasLookup,
                [
                    'kelas' => $kelasValue,
                    'kelompok' => $kelompokValue,
                    'name' => $className,
                    'unit' => $sekolah->code === 'mts' ? 'MTs' : 'MA',
                    'jenjang' => null,
                    'is_active' => true,
                ]
            );

            $wali = trim($record['WALI'] ?? '');
            $guardianLastName = $wali !== '' ? $wali : collect(explode(' ', trim($record['NAMA'] ?? '')))
                ->last();

            $nis = StudentSpreadsheetTemplate::normalizedNis($record);
            if ($nis === '') {
                continue;
            }

            $fallbackName = $guardianLastName !== '' ? $guardianLastName : 'Wali';
            $familyPhone = WhatsAppLink::normalizePhone(trim($record['NOMOR_HP'] ?? ''));

            $this->seedStudentWithRecords(
                sekolah: $sekolah,
                kelas: $kelas,
                tahunAktif: $tahunAktif,
                nis: $nis,
                name: trim($record['NAMA'] ?? ''),
                gender: StudentSpreadsheetTemplate::normalizedGender($record),
                fatherName: $wali === '' ? $fallbackName : '',
                motherName: $wali === '' ? $fallbackName : '',
                familyPhone: $familyPhone,
                address: trim($record['ALAMAT'] ?? ''),
                registrationNumber: trim($record['NODAF'] ?? ''),
                kelasKelompok: $kelasKelompok,
                guardianName: $wali !== '' ? $wali : null,
            );
        }
    }
}
