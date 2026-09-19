<?php

namespace App\Support;

use App\Models\AbsensiGuru;
use App\Models\AbsensiQr;
use App\Models\AbsensiSiswa;
use App\Models\Buku;
use App\Models\Guru;
use App\Models\JadwalAbsen;
use App\Models\JadwalAbsensiGuru;
use App\Models\JenisPelanggaran;
use App\Models\JenisPotongan;
use App\Models\JenisPrestasi;
use App\Models\JenisTagihan;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\LimitCashless;
use App\Models\MenuKantin;
use App\Models\OrangTua;
use App\Models\Pelajaran;
use App\Models\PengaturanCashless;
use App\Models\PindahKelas;
use App\Models\RekeningBank;
use App\Models\RiwayatAkademik;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\StatusSantri;
use App\Models\Tagihan;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MasterDataUsage
{
    public static function sekolahBlockedReason(Sekolah $sekolah): ?string
    {
        $checks = [
            [User::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki akun pengguna.'],
            [Siswa::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data siswa.'],
            [Guru::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data guru.'],
            [OrangTua::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data orang tua.'],
            [Kelas::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data kelas.'],
            [TahunAkademik::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki tahun akademik.'],
            [Tagihan::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data tagihan.'],
            [Buku::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data buku perpustakaan.'],
            [Pelajaran::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data pelajaran.'],
            [JadwalAbsen::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki jadwal absensi.'],
            [JadwalAbsensiGuru::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki jadwal absensi guru.'],
            [AbsensiSiswa::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data absensi siswa.'],
            [AbsensiGuru::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data absensi guru.'],
            [MenuKantin::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki menu kantin.'],
            [RekeningBank::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki rekening bank.'],
            [LimitCashless::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki limit cashless.'],
            [PengaturanCashless::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki pengaturan cashless.'],
            [AbsensiQr::query()->where('sekolah_id', $sekolah->id), 'Masih memiliki data QR absensi.'],
        ];

        foreach ($checks as [$query, $message]) {
            if ($query->exists()) {
                return $message;
            }
        }

        return null;
    }

    public static function kelasBlockedReason(Kelas $kelas): ?string
    {
        if (Siswa::query()->where('kelas_id', $kelas->id)->exists()) {
            return 'Masih memiliki siswa terdaftar di kelas ini.';
        }

        if (PindahKelas::query()->where('dari_kelas_id', $kelas->id)->orWhere('ke_kelas_id', $kelas->id)->exists()) {
            return 'Masih digunakan pada riwayat pindah kelas.';
        }

        if (DB::table('jadwal_absen_kelas')->where('kelas_id', $kelas->id)->exists()) {
            return 'Masih digunakan pada jadwal absensi.';
        }

        return null;
    }

    public static function tahunAkademikBlockedReason(TahunAkademik $tahunAkademik): ?string
    {
        if (RiwayatAkademik::query()->where('tahun_akademik_id', $tahunAkademik->id)->exists()) {
            return 'Masih digunakan pada riwayat akademik siswa.';
        }

        if (Tagihan::query()->where('tahun_akademik_id', $tahunAkademik->id)->exists()) {
            return 'Masih digunakan pada data tagihan.';
        }

        return null;
    }

    public static function jenisTagihanBlockedReason(JenisTagihan $jenisTagihan): ?string
    {
        if (Tagihan::query()->where('jenis_tagihan_id', $jenisTagihan->id)->exists()) {
            return 'Masih digunakan pada data tagihan.';
        }

        if (Tagihan::query()->where('jenis', $jenisTagihan->name)->exists()) {
            return 'Masih digunakan pada data tagihan (nama jenis).';
        }

        return null;
    }

    public static function jenisPelanggaranBlockedReason(JenisPelanggaran $jenisPelanggaran): ?string
    {
        if ($jenisPelanggaran->pelanggaranSiswa()->exists()) {
            return 'Masih dipakai pada data pelanggaran siswa.';
        }

        if ($jenisPelanggaran->pelanggaranGuru()->exists()) {
            return 'Masih dipakai pada data pelanggaran guru.';
        }

        return null;
    }

    public static function jenisPrestasiBlockedReason(JenisPrestasi $jenisPrestasi): ?string
    {
        if ($jenisPrestasi->prestasiSiswa()->exists()) {
            return 'Masih dipakai pada data prestasi siswa.';
        }

        if ($jenisPrestasi->prestasiGuru()->exists()) {
            return 'Masih dipakai pada data prestasi guru.';
        }

        return null;
    }

    public static function jenisPotonganBlockedReason(JenisPotongan $jenisPotongan): ?string
    {
        if ($jenisPotongan->potonganSiswa()->exists()) {
            return 'Masih dipakai pada penugasan potongan siswa.';
        }

        return null;
    }

    public static function kamarBlockedReason(Kamar $kamar): ?string
    {
        if (Siswa::query()->where('kamar_id', $kamar->id)->exists()) {
            return 'Masih dipakai pada data siswa.';
        }

        return null;
    }

    public static function statusSantriBlockedReason(StatusSantri $statusSantri): ?string
    {
        if (Siswa::query()->where('status_santri_id', $statusSantri->id)->exists()) {
            return 'Masih dipakai pada data siswa.';
        }

        return null;
    }
}
