<?php

namespace Database\Seeders\Dummy\Concerns;

use App\Models\DokumenSiswa;
use App\Models\Dompet;
use App\Models\KartuSiswa;
use App\Models\Kelas;
use App\Models\OrangTua;
use App\Models\ProfilSiswa;
use App\Models\RiwayatAkademik;
use App\Models\SaldoKeuangan;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\TahunAkademik;

trait SeedsStudentRecords
{
    /** @var array<string, OrangTua> */
    protected array $parentsByPhone = [];

    protected function seedStudentWithRecords(
        Sekolah $sekolah,
        Kelas $kelas,
        TahunAkademik $tahunAktif,
        string $nis,
        string $name,
        string $gender,
        string $fatherName,
        string $motherName,
        ?string $familyPhone = null,
        ?string $address = null,
        ?string $registrationNumber = null,
        ?string $kelasKelompok = null,
        ?string $guardianName = null,
    ): Siswa {
        $siswa = Siswa::create([
            'sekolah_id' => $sekolah->id,
            'kelas_id' => $kelas->id,
            'nis' => $nis,
            'nomor_pendaftaran' => $registrationNumber !== '' ? $registrationNumber : null,
            'name' => $name,
            'gender' => $gender,
            'birth_date' => now()->subYears(rand(6, 18))->subDays(rand(0, 365)),
            'birth_place' => 'Pekanbaru',
            'address' => $address,
            'status' => Siswa::STATUS_ACTIVE,
        ]);

        if (! isset($this->parentsByPhone[$parentKey = $this->parentCacheKey($sekolah, $familyPhone, $guardianName, $fatherName, $address)])) {
            $parentData = [
                'sekolah_id' => $sekolah->id,
                'alamat' => $address,
                'status' => 'aktif',
            ];

            if (trim((string) $guardianName) !== '') {
                $parentData['nama_wali'] = $guardianName;
                if ($familyPhone !== null && $familyPhone !== '') {
                    $parentData['telepon_wali'] = $familyPhone;
                }
            } else {
                $parentData['nama_ayah'] = $fatherName;
                $parentData['nama_ibu'] = $motherName;
                if ($familyPhone !== null && $familyPhone !== '') {
                    $parentData['telepon_ayah'] = $familyPhone;
                }
            }

            $this->parentsByPhone[$parentKey] = OrangTua::create($parentData);
        }

        $siswa->orangTua()->attach($this->parentsByPhone[$parentKey]->id);

        Dompet::create([
            'siswa_id' => $siswa->id,
            'saldo_us' => 0,
            'saldo_kantin' => 0,
            'saldo_tabungan' => 0,
        ]);

        SaldoKeuangan::create([
            'siswa_id' => $siswa->id,
            'balance' => 0,
        ]);

        ProfilSiswa::create([
            'siswa_id' => $siswa->id,
            'extra_fields' => [
                'nama_panggilan' => explode(' ', $name)[0],
                'agama' => 'Islam',
                'golongan_darah' => ['A', 'B', 'O', 'AB'][rand(0, 3)],
                'kelas_kelompok' => $kelasKelompok,
            ],
        ]);

        KartuSiswa::create([
            'siswa_id' => $siswa->id,
            'qr_code' => 'QR-'.$nis,
            'status' => 'aktif',
        ]);

        if (rand(0, 2) === 0) {
            DokumenSiswa::create([
                'siswa_id' => $siswa->id,
                'title' => ['KTP', 'Akta Kelahiran', 'Ijazah'][rand(0, 2)],
                'file_type' => 'pdf',
                'file_path' => 'dokumen/sample.pdf',
            ]);
        }

        RiwayatAkademik::create([
            'siswa_id' => $siswa->id,
            'tahun_akademik_id' => $tahunAktif->id,
            'class_name' => $kelas->name,
            'gpa' => rand(280, 395) / 100,
        ]);

        return $siswa;
    }

    private function parentCacheKey(
        Sekolah $sekolah,
        ?string $familyPhone,
        ?string $guardianName,
        string $fatherName,
        ?string $address,
    ): string {
        if ($familyPhone !== null && $familyPhone !== '') {
            return $sekolah->id.':'.$familyPhone;
        }

        $nameKey = trim((string) $guardianName) !== '' ? $guardianName : $fatherName;

        return $sekolah->id.':wali:'.md5(strtolower(trim($nameKey)).'|'.($address ?? ''));
    }
}
