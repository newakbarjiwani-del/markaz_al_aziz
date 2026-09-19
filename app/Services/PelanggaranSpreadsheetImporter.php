<?php

namespace App\Services;

use App\Models\JenisPelanggaran;
use App\Models\PelanggaranSiswa;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Support\SiswaCatatanSpreadsheetTemplate;
use App\Support\VirtualAccountNumber;

class PelanggaranSpreadsheetImporter extends SiswaCatatanSpreadsheetImporter
{
    protected function modelClass(): string
    {
        return PelanggaranSiswa::class;
    }

    public function label(): string
    {
        return 'Pelanggaran';
    }

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

        $tanggal = SiswaCatatanSpreadsheetTemplate::normalizedTanggal($record);

        if ($tanggal === null) {
            throw new \InvalidArgumentException('Tanggal tidak valid. Gunakan format YYYY-MM-DD atau DD/MM/YYYY.');
        }

        $jenis = null;
        $jenisNama = trim($record['JENIS_PELANGGARAN'] ?? '');

        if ($jenisNama !== '') {
            $jenis = JenisPelanggaran::query()
                ->active()
                ->where('nama', $jenisNama)
                ->first();

            if ($jenis === null) {
                throw new \InvalidArgumentException('Jenis pelanggaran "'.$jenisNama.'" tidak ditemukan di katalog aktif.');
            }
        }

        $judul = trim($record['JUDUL'] ?? '');

        if ($judul === '' && $jenis !== null) {
            $judul = $jenis->nama;
        }

        if ($judul === '') {
            throw new \InvalidArgumentException('Judul wajib diisi (atau isi JENIS_PELANGGARAN dari katalog).');
        }

        $point = SiswaCatatanSpreadsheetTemplate::normalizedPoint($record);

        if ($point === null && $jenis !== null) {
            $point = (int) $jenis->point;
        }

        $point ??= 0;

        if ($point < 0 || $point > 99999) {
            throw new \InvalidArgumentException('Point harus berupa angka 0–99999.');
        }

        return [
            'siswa' => $siswa,
            'judul' => $judul,
            'tanggal' => $tanggal,
            'point' => $point,
            'jenis_pelanggaran_id' => $jenis?->id,
        ];
    }

    protected function importRow(Sekolah $sekolah, array $record): void
    {
        $context = $this->resolveRowContext($sekolah, $record);

        PelanggaranSiswa::create([
            'siswa_id' => $context['siswa']->id,
            'sekolah_id' => $sekolah->id,
            'jenis_pelanggaran_id' => $context['jenis_pelanggaran_id'] ?? null,
            'judul' => $context['judul'],
            'keterangan' => trim($record['KETERANGAN'] ?? '') ?: null,
            'tanggal' => $context['tanggal'],
            'point' => $context['point'],
            'reported_by' => null,
        ]);
    }
}
