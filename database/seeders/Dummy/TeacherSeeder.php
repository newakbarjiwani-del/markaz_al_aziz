<?php

namespace Database\Seeders\Dummy;

use App\Models\Kelas;
use App\Models\Sekolah;
use Database\Seeders\Dummy\Concerns\SeedsTeacherRecords;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    use SeedsTeacherRecords;

    /** @var list<string> */
    private array $jabatanList = [
        'Guru Mapel',
        'Wali Kelas',
        'Guru BK',
        'Koordinator',
        'Kepala Lab',
        'Guru Pendamping',
        'Guru Tahfidz',
        'Guru Olahraga',
    ];

    public function run(): void
    {
        $faker = FakerFactory::create('id_ID');

        foreach (Sekolah::query()->orderBy('id')->get() as $sekolah) {
            $kelasList = Kelas::query()
                ->where('sekolah_id', $sekolah->id)
                ->orderBy('id')
                ->get();

            $schoolPrefix = strtoupper($sekolah->code);

            for ($i = 1; $i <= DummySchoolCatalog::TEACHERS_PER_SCHOOL; $i++) {
                $gender = rand(0, 1) ? 'male' : 'female';
                $firstName = $gender === 'male'
                    ? $faker->firstNameMale()
                    : $faker->firstNameFemale();
                $name = trim($firstName.' '.$faker->lastName());

                $this->seedTeacherWithRecords(
                    sekolah: $sekolah,
                    kelasList: $kelasList,
                    nip: sprintf('GR-%s-%03d', $schoolPrefix, $i),
                    name: $name,
                    jabatan: $this->jabatanList[($i - 1) % count($this->jabatanList)],
                    jenisGuru: $i <= 6 ? 'PNS' : 'Honorer',
                    phone: '62813'.str_pad((string) ($sekolah->id * 100 + $i), 8, '0', STR_PAD_LEFT),
                );
            }
        }
    }
}
