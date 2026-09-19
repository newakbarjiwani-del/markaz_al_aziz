<?php

namespace Database\Seeders\Dummy;

use App\Models\Perizinan;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PerizinanSeeder extends Seeder
{
    public function run(): void
    {
        $siswas = Siswa::limit(15)->get();
        if ($siswas->isEmpty()) {
            return;
        }

        $adminUser = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->first();
        $adminId = $adminUser?->id;

        $sampleData = [
            [
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
                'alasan' => 'Pemeriksaan kesehatan di klinik terdekat',
                'offset_mulai' => -2, // hours ago
                'duration_hours' => 3,
                'penanggung_jawab' => 'Ibu Kandung (Wali)',
                'status' => Perizinan::STATUS_DISETUJUI,
                'catatan' => 'Kembali sebelum jam 15.00 WIB',
            ],
            [
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK,
                'alasan' => 'Pengurusan dokumen KTP ke Disdukcapil',
                'offset_mulai' => -24,
                'duration_hours' => 4,
                'penanggung_jawab' => 'Bapak Subandi',
                'status' => Perizinan::STATUS_KEMBALI,
                'catatan' => 'Kembali tepat waktu',
            ],
            [
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK_PONDOK,
                'alasan' => 'Acara hajatan/pernikahan keluarga di kota asal',
                'offset_mulai' => -48,
                'duration_hours' => 36,
                'penanggung_jawab' => 'Keluarga / Paman',
                'status' => Perizinan::STATUS_TERLAMBAT,
                'catatan' => 'Laporan terlambat 2 jam karena macet',
            ],
            [
                'jenis_perizinan' => Perizinan::JENIS_KELUAR_MASUK_PONDOK,
                'alasan' => 'Izin keluar belanja kebutuhan santri',
                'offset_mulai' => -1,
                'duration_hours' => 2,
                'penanggung_jawab' => 'Mandiri',
                'status' => Perizinan::STATUS_DISETUJUI,
                'catatan' => 'Area pasar terdekat',
            ],
            [
                'jenis_perizinan' => Perizinan::JENIS_PULANG_LIBUR,
                'alasan' => 'Libur Hari Raya Idul Adha & Kepulangan Santri',
                'offset_mulai' => -120,
                'duration_hours' => 144,
                'penanggung_jawab' => 'Ayah Kandung',
                'status' => Perizinan::STATUS_KEMBALI,
                'catatan' => 'Izin perpulangan massal',
            ],
            [
                'jenis_perizinan' => Perizinan::JENIS_PULANG_LIBUR,
                'alasan' => 'Izin Kepulangan Santri Akhir Semester',
                'offset_mulai' => 24,
                'duration_hours' => 168,
                'penanggung_jawab' => 'Orang Tua / Wali',
                'status' => Perizinan::STATUS_PENDING,
                'catatan' => 'Permohonan belum diverifikasi',
            ],
        ];

        foreach ($siswas as $idx => $siswa) {
            $spec = $sampleData[$idx % count($sampleData)];
            $tglMulai = Carbon::now()->addHours($spec['offset_mulai']);
            $tglSampai = (clone $tglMulai)->addHours($spec['duration_hours']);
            
            $tglKembali = null;
            if ($spec['status'] === Perizinan::STATUS_KEMBALI) {
                $tglKembali = (clone $tglSampai)->subMinutes(15);
            } elseif ($spec['status'] === Perizinan::STATUS_TERLAMBAT) {
                $tglKembali = (clone $tglSampai)->addHours(2);
            }

            Perizinan::create([
                'sekolah_id' => $siswa->sekolah_id,
                'siswa_id' => $siswa->id,
                'jenis_perizinan' => $spec['jenis_perizinan'],
                'alasan' => $spec['alasan'],
                'tgl_mulai' => $tglMulai,
                'tgl_sampai' => $tglSampai,
                'tgl_kembali_aktual' => $tglKembali,
                'penanggung_jawab' => $spec['penanggung_jawab'],
                'status' => $spec['status'],
                'catatan' => $spec['catatan'],
                'approved_by' => $spec['status'] !== Perizinan::STATUS_PENDING ? $adminId : null,
                'created_by' => $adminId,
            ]);
        }
    }
}
