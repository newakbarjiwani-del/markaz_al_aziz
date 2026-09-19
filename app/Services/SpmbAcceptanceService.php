<?php

namespace App\Services;

use App\Models\Dompet;
use App\Models\KartuSiswa;
use App\Models\ProfilSiswa;
use App\Models\SaldoKeuangan;
use App\Models\Siswa;
use App\Models\SpmbPendaftar;
use App\Support\SpmbRegistrationNumber;
use App\Support\VirtualAccountNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpmbAcceptanceService
{
    public function generateNomorPendaftaran(): string
    {
        return SpmbRegistrationNumber::generate();
    }

    public function accept(SpmbPendaftar $pendaftar, string $nis, ?int $kelasId = null): Siswa
    {
        if ($pendaftar->status === SpmbPendaftar::STATUS_ACCEPTED) {
            throw ValidationException::withMessages([
                'status' => 'Pendaftar ini sudah diterima.',
            ]);
        }

        if ($pendaftar->status === SpmbPendaftar::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'status' => 'Pendaftar yang ditolak tidak dapat diterima.',
            ]);
        }

        if ($pendaftar->status !== SpmbPendaftar::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'status' => 'Pendaftar harus diverifikasi terlebih dahulu.',
            ]);
        }

        $nis = VirtualAccountNumber::requireValidNis($nis);

        $existing = Siswa::query()->where('nis', $nis)->first(['id', 'name']);
        if ($existing !== null) {
            throw ValidationException::withMessages([
                'nis' => 'NIS sudah digunakan oleh '.$existing->name.' (ID '.$existing->id.').',
            ]);
        }

        return DB::transaction(function () use ($pendaftar, $nis, $kelasId) {
            $sekolahId = $pendaftar->sekolah_id
                ?? $pendaftar->periode?->sekolah_id
                ?? $pendaftar->loadMissing('periode')->periode?->sekolah_id;

            if ($sekolahId === null) {
                throw ValidationException::withMessages([
                    'sekolah_id' => 'Pendaftar belum terikat sekolah. Set sekolah pada periode atau pendaftar terlebih dahulu.',
                ]);
            }

            $siswa = Siswa::create([
                'sekolah_id' => $sekolahId,
                'kelas_id' => $kelasId,
                'nis' => $nis,
                'nomor_pendaftaran' => $pendaftar->nomor_pendaftaran,
                'name' => $pendaftar->name,
                'gender' => $pendaftar->gender,
                'birth_place' => $pendaftar->birth_place,
                'birth_date' => $pendaftar->birth_date,
                'address' => $pendaftar->address,
                'status' => Siswa::STATUS_ACTIVE,
            ]);

            Dompet::create([
                'siswa_id' => $siswa->id,
                'saldo_us' => 0,
                'saldo_kantin' => 0,
                'saldo_tabungan' => 0,
            ]);
            SaldoKeuangan::create(['siswa_id' => $siswa->id, 'balance' => 0]);
            KartuSiswa::provisionFor($siswa);
            ProfilSiswa::create(['siswa_id' => $siswa->id]);

            $pendaftar->update([
                'siswa_id' => $siswa->id,
                'sekolah_id' => $sekolahId,
                'status' => SpmbPendaftar::STATUS_ACCEPTED,
            ]);

            return $siswa->fresh(['kelas', 'profil']);
        });
    }

    public function reject(SpmbPendaftar $pendaftar, ?string $notes = null): void
    {
        if ($pendaftar->status === SpmbPendaftar::STATUS_ACCEPTED) {
            throw ValidationException::withMessages([
                'status' => 'Pendaftar yang sudah diterima tidak dapat ditolak.',
            ]);
        }

        if ($pendaftar->status === SpmbPendaftar::STATUS_REJECTED) {
            throw ValidationException::withMessages([
                'status' => 'Pendaftar ini sudah ditolak.',
            ]);
        }

        $pendaftar->update([
            'status' => SpmbPendaftar::STATUS_REJECTED,
            'notes' => $notes,
        ]);
    }
}
