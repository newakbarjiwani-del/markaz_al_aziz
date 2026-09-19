<?php

namespace App\Services;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\HariLibur;
use App\Models\JadwalAbsensiGuru;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GuruJadwalAttendanceService
{
    public const METHOD_RFID = 'rfid';

    public const METHOD_MANUAL = 'manual';

    public const ACTION_MASUK = 'masuk';

    public const ACTION_PULANG = 'pulang';

    public const ACTION_MASUK_PULANG = 'masuk_pulang';

    public const ACTION_ABSEN = 'absen';

    public function __construct(
        private HariLiburService $hariLiburService
    ) {}

    /**
     * @return array{guru: Guru, attendance: AbsensiGuru, action: string}
     */
    public function record(
        JadwalAbsensiGuru $jadwal,
        Guru $guru,
        string $method,
        ?string $status = null,
        ?string $notes = null,
    ): array {
        if ((int) $guru->jadwal_absensi_guru_id !== (int) $jadwal->id) {
            throw new RuntimeException('Guru tidak terdaftar pada jadwal absensi ini.');
        }

        $today = now()->toDateString();
        $this->hariLiburService->assertNotLibur($today, $jadwal->sekolah_id, HariLibur::APPLIES_GURU);

        return DB::transaction(function () use ($jadwal, $guru, $method, $status, $notes, $today) {
            // Serialize writes per guru to prevent concurrent double-submit races/deadlocks.
            Guru::query()
                ->whereKey($guru->id)
                ->lockForUpdate()
                ->first();

            $existing = AbsensiGuru::query()
                ->where('guru_id', $guru->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->isAbsentOnly()) {
                    throw new RuntimeException('Guru sudah tercatat dengan status '.AttendanceStatus::label($existing->status).' hari ini.');
                }

                if ($existing->hasCheckedIn() && ! $existing->hasCheckedOut()) {
                    if ($method === self::METHOD_RFID && AttendanceStatus::isAbsent($status)) {
                        throw new RuntimeException('Status izin/sakit/cuti/alpha hanya dapat diinput lewat absensi manual.');
                    }

                    if ($method === self::METHOD_MANUAL) {
                        throw new RuntimeException('Guru sudah melakukan absensi masuk hari ini. Gunakan RFID untuk absensi pulang.');
                    }

                    return $this->checkOut($jadwal, $existing, $method);
                }

                throw new RuntimeException('Guru sudah menyelesaikan absensi hari ini.');
            }

            if ($this->isAbsentStatus($status)) {
                if ($method !== self::METHOD_MANUAL) {
                    throw new RuntimeException('Status izin/sakit/cuti/alpha hanya dapat diinput lewat absensi manual.');
                }

                return $this->recordAbsent($jadwal, $guru, $method, (string) $status, $notes, $today);
            }

            if ($method === self::METHOD_MANUAL && $this->shouldAutoCheckoutOnFirstManual($jadwal, now())) {
                return $this->checkInAndOut($jadwal, $guru, $method, $status, $notes, $today);
            }

            return $this->checkIn($jadwal, $guru, $method, $status, $notes, $today);
        }, 3);
    }

    /**
     * @return array{guru: Guru, attendance: AbsensiGuru, action: string}
     */
    private function checkIn(
        JadwalAbsensiGuru $jadwal,
        Guru $guru,
        string $method,
        ?string $status,
        ?string $notes,
        string $today,
    ): array {
        $at = now();
        $resolvedStatus = $status ?: $this->resolveStatusMasuk($jadwal, $at);

        $attendance = AbsensiGuru::create([
            'sekolah_id' => $guru->sekolah_id,
            'guru_id' => $guru->id,
            'jadwal_absensi_guru_id' => $jadwal->id,
            'date' => $today,
            'status' => $resolvedStatus,
            'method' => $method,
            'jam_masuk' => $at->format('H:i'),
            'notes' => $notes,
        ]);

        return [
            'guru' => $guru->fresh(),
            'attendance' => $attendance,
            'action' => self::ACTION_MASUK,
        ];
    }

    /**
     * @return array{guru: Guru, attendance: AbsensiGuru, action: string}
     */
    private function checkInAndOut(
        JadwalAbsensiGuru $jadwal,
        Guru $guru,
        string $method,
        ?string $status,
        ?string $notes,
        string $today,
    ): array {
        $at = now();
        $resolvedStatus = $status ?: $this->resolveStatusMasuk($jadwal, $at);

        $attendance = AbsensiGuru::create([
            'sekolah_id' => $guru->sekolah_id,
            'guru_id' => $guru->id,
            'jadwal_absensi_guru_id' => $jadwal->id,
            'date' => $today,
            'status' => $resolvedStatus,
            'status_pulang' => $this->resolveStatusPulang($jadwal, $at),
            'method' => $method,
            'method_keluar' => $method,
            'jam_masuk' => $at->format('H:i'),
            'jam_keluar' => $at->format('H:i'),
            'notes' => $notes,
        ]);

        return [
            'guru' => $guru->fresh(),
            'attendance' => $attendance,
            'action' => self::ACTION_MASUK_PULANG,
        ];
    }

    /**
     * @return array{guru: Guru, attendance: AbsensiGuru, action: string}
     */
    private function recordAbsent(
        JadwalAbsensiGuru $jadwal,
        Guru $guru,
        string $method,
        string $status,
        ?string $notes,
        string $today,
    ): array {
        $attendance = AbsensiGuru::create([
            'sekolah_id' => $guru->sekolah_id,
            'guru_id' => $guru->id,
            'jadwal_absensi_guru_id' => $jadwal->id,
            'date' => $today,
            'status' => $status,
            'method' => $method,
            'notes' => $notes,
        ]);

        return [
            'guru' => $guru->fresh(),
            'attendance' => $attendance,
            'action' => self::ACTION_ABSEN,
        ];
    }

    /**
     * @return array{guru: Guru, attendance: AbsensiGuru, action: string}
     */
    private function checkOut(JadwalAbsensiGuru $jadwal, AbsensiGuru $attendance, string $method): array
    {
        $at = now();

        $attendance->update([
            'jam_keluar' => $at->format('H:i'),
            'status_pulang' => $this->resolveStatusPulang($jadwal, $at),
            'method_keluar' => $method,
        ]);

        return [
            'guru' => $attendance->guru,
            'attendance' => $attendance->fresh(),
            'action' => self::ACTION_PULANG,
        ];
    }

    public function findGuruByRfid(JadwalAbsensiGuru $jadwal, string $rfidUid): ?Guru
    {
        $rfidUid = trim($rfidUid);

        if ($rfidUid === '') {
            return null;
        }

        return Guru::query()
            ->where('sekolah_id', $jadwal->sekolah_id)
            ->where('jadwal_absensi_guru_id', $jadwal->id)
            ->whereHas('rfid', fn ($query) => $query->where('uid', $rfidUid))
            ->first();
    }

    public function resolveStatusMasuk(JadwalAbsensiGuru $jadwal, Carbon $at): string
    {
        $batas = $this->timeOnDate($jadwal->jam_masuk, $at)
            ->addMinutes(max(0, (int) $jadwal->toleransi_menit));

        return $at->lte($batas)
            ? AbsensiGuru::STATUS_HADIR
            : AbsensiGuru::STATUS_TERLAMBAT;
    }

    public function resolveStatusPulang(JadwalAbsensiGuru $jadwal, Carbon $at): string
    {
        $jadwalPulang = $this->timeOnDate($jadwal->jam_pulang, $at);

        return $at->lt($jadwalPulang)
            ? AbsensiGuru::STATUS_PULANG_AWAL
            : AbsensiGuru::STATUS_PULANG_TEPAT;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function todayBoard(JadwalAbsensiGuru $jadwal): array
    {
        $today = now()->toDateString();

        $attendanceByGuru = AbsensiGuru::query()
            ->whereDate('date', $today)
            ->whereIn('guru_id', $jadwal->gurus()->pluck('id'))
            ->get()
            ->keyBy('guru_id');

        return $jadwal->gurus()
            ->with('rfid')
            ->orderBy('name')
            ->get()
            ->map(function (Guru $guru) use ($attendanceByGuru) {
                $row = $attendanceByGuru->get($guru->id);

                return [
                    'id' => $guru->id,
                    'nip' => $guru->nip,
                    'name' => $guru->name,
                    'jabatan' => $guru->jabatan,
                    'rfid_uid' => $guru->rfidUid(),
                    'has_masuk' => $row?->hasCheckedIn() ?? false,
                    'has_pulang' => $row?->hasCheckedOut() ?? false,
                    'has_record' => $row !== null,
                    'is_absent_only' => $row?->isAbsentOnly() ?? false,
                    'is_complete' => $row?->isDayComplete() ?? false,
                    'attended' => $row?->hasCheckedIn() ?? false,
                    'status' => $row?->status,
                    'status_pulang' => $row?->status_pulang,
                    'jam_masuk' => $row?->jam_masuk,
                    'jam_keluar' => $row?->jam_keluar,
                    'method' => $row?->method,
                    'method_keluar' => $row?->method_keluar,
                ];
            })
            ->values()
            ->all();
    }

    private function isAbsentStatus(?string $status): bool
    {
        return AttendanceStatus::isAbsent($status);
    }

    private function shouldAutoCheckoutOnFirstManual(JadwalAbsensiGuru $jadwal, Carbon $at): bool
    {
        return $at->gte($this->timeOnDate($jadwal->jam_pulang, $at));
    }

    private function timeOnDate(mixed $time, Carbon $date): Carbon
    {
        $value = (string) $time;

        if (strlen($value) === 5) {
            $value .= ':00';
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', $date->toDateString().' '.$value);
    }
}
