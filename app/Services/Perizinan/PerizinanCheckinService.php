<?php

namespace App\Services\Perizinan;

use App\Models\JenisPelanggaran;
use App\Models\PelanggaranSiswa;
use App\Models\Perizinan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PerizinanCheckinService
{
    public function resolveReturnAt(Perizinan $perizinan, ?Carbon $returnAt = null): Carbon
    {
        return $returnAt ?? Carbon::now();
    }

    public function isLate(Perizinan $perizinan, Carbon $returnAt): bool
    {
        if (! $perizinan->tgl_sampai) {
            return false;
        }

        return $returnAt->greaterThan($perizinan->tgl_sampai);
    }

    public function defaultLateJenis(): ?JenisPelanggaran
    {
        $nama = (string) config('prestasi-pelanggaran.perizinan_late_return_jenis_nama', '');

        if ($nama === '') {
            return null;
        }

        return JenisPelanggaran::query()->active()->where('nama', $nama)->first();
    }

    /**
     * @return array{
     *     is_late: bool,
     *     return_at: string,
     *     tgl_sampai: string,
     *     minutes_late: int,
     *     siswa_name: string,
     *     default_pelanggaran: ?array{
     *         jenis_pelanggaran_id: int,
     *         level: string,
     *         judul: string,
     *         point: int,
     *         keterangan: string
     *     }
     * }
     */
    public function preview(Perizinan $perizinan, ?Carbon $returnAt = null): array
    {
        $perizinan->loadMissing('siswa');
        $returnAt = $this->resolveReturnAt($perizinan, $returnAt);
        $isLate = $this->isLate($perizinan, $returnAt);
        $minutesLate = $isLate && $perizinan->tgl_sampai
            ? max(0, (int) $perizinan->tgl_sampai->diffInMinutes($returnAt, false))
            : 0;

        $defaultJenis = $isLate ? $this->defaultLateJenis() : null;
        $defaultPelanggaran = null;

        if ($defaultJenis) {
            $defaultPelanggaran = [
                'jenis_pelanggaran_id' => $defaultJenis->id,
                'level' => $defaultJenis->level,
                'judul' => $defaultJenis->nama,
                'point' => (int) $defaultJenis->point,
                'keterangan' => $this->buildDefaultKeterangan($perizinan, $returnAt, $minutesLate),
            ];
        }

        return [
            'is_late' => $isLate,
            'return_at' => $returnAt->toIso8601String(),
            'tgl_sampai' => $perizinan->tgl_sampai?->toIso8601String() ?? '',
            'minutes_late' => $minutesLate,
            'siswa_name' => $perizinan->siswa?->name ?? '-',
            'default_pelanggaran' => $defaultPelanggaran,
        ];
    }

    /**
     * @param  array<string, mixed>  $pelanggaranInput
     * @return array{perizinan: Perizinan, pelanggaran: ?PelanggaranSiswa}
     */
    public function recordReturn(
        Perizinan $perizinan,
        User $user,
        ?Carbon $returnAt = null,
        array $pelanggaranInput = [],
        ?string $catatan = null,
    ): array {
        if ($perizinan->tgl_kembali_aktual) {
            throw ValidationException::withMessages([
                'perizinan' => 'Siswa sudah dicatat kembali sebelumnya.',
            ]);
        }

        $returnAt = $this->resolveReturnAt($perizinan, $returnAt);
        $isLate = $this->isLate($perizinan, $returnAt);

        return DB::transaction(function () use ($perizinan, $user, $returnAt, $isLate, $pelanggaranInput, $catatan) {
            $pelanggaran = null;

            if ($isLate) {
                $pelanggaran = $this->createPelanggaran($perizinan, $user, $returnAt, $pelanggaranInput);
            }

            $status = $isLate ? Perizinan::STATUS_TERLAMBAT : Perizinan::STATUS_KEMBALI;

            $perizinan->update([
                'status' => $status,
                'tgl_kembali_aktual' => $returnAt,
                'catatan' => $catatan ?? $perizinan->catatan,
            ]);

            return [
                'perizinan' => $perizinan->fresh(['siswa.kelas', 'sekolah']),
                'pelanggaran' => $pelanggaran,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function createPelanggaran(
        Perizinan $perizinan,
        User $user,
        Carbon $returnAt,
        array $input,
    ): PelanggaranSiswa {
        $perizinan->loadMissing('siswa');
        $siswa = $perizinan->siswa;
        abort_unless($siswa, 422, 'Data siswa perizinan tidak ditemukan.');

        $jenisId = (int) ($input['pelanggaran_jenis_pelanggaran_id'] ?? $input['jenis_pelanggaran_id'] ?? 0);
        $judul = trim((string) ($input['pelanggaran_judul'] ?? $input['judul'] ?? ''));
        $point = (int) ($input['pelanggaran_point'] ?? $input['point'] ?? 0);

        if ($jenisId <= 0 || $judul === '') {
            throw ValidationException::withMessages([
                'pelanggaran_jenis_pelanggaran_id' => 'Data pelanggaran wajib diisi untuk keterlambatan kembali.',
            ]);
        }

        $keterangan = trim((string) ($input['pelanggaran_keterangan'] ?? $input['keterangan'] ?? ''));
        if ($keterangan === '') {
            $minutesLate = $perizinan->tgl_sampai
                ? max(0, (int) $perizinan->tgl_sampai->diffInMinutes($returnAt, false))
                : 0;
            $keterangan = $this->buildDefaultKeterangan($perizinan, $returnAt, $minutesLate);
        }

        $tanggal = $input['pelanggaran_tanggal'] ?? $input['tanggal'] ?? $returnAt->toDateString();

        return PelanggaranSiswa::create([
            'siswa_id' => $siswa->id,
            'sekolah_id' => $siswa->sekolah_id,
            'jenis_pelanggaran_id' => $jenisId,
            'judul' => $judul,
            'keterangan' => $keterangan,
            'tanggal' => $tanggal,
            'point' => $point,
            'reported_by' => $user->id,
        ]);
    }

    private function buildDefaultKeterangan(Perizinan $perizinan, Carbon $returnAt, int $minutesLate): string
    {
        $batas = $perizinan->tgl_sampai?->isoFormat('D MMM YYYY, HH:mm') ?? '-';
        $aktual = $returnAt->isoFormat('D MMM YYYY, HH:mm');
        $jenis = $perizinan->jenis_label;
        $alasan = $perizinan->alasan;

        return "Keterlambatan kembali dari {$jenis}. Batas: {$batas}, kembali: {$aktual} (+{$minutesLate} menit). Alasan izin: {$alasan}.";
    }
}
