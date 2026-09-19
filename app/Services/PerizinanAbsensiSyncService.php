<?php

namespace App\Services;

use App\Models\JadwalAbsenSlot;
use App\Models\Perizinan;
use App\Support\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PerizinanAbsensiSyncService
{
    /**
     * Get active perizinan map keyed by student ID for a given date and optional slot.
     *
     * @param Collection<int, int>|array<int> $siswaIds
     * @return Collection<int, array<string, mixed>>
     */
    public static function activePerizinanMap(Collection|array $siswaIds, string $date, ?JadwalAbsenSlot $slot = null): Collection
    {
        $ids = is_array($siswaIds) ? $siswaIds : $siswaIds->toArray();
        if (empty($ids)) {
            return collect();
        }

        $dateCarbon = Carbon::parse($date);

        // Determine slot timeframe boundary for checking overlap
        if ($slot && $slot->time_start && $slot->time_end) {
            $slotStart = Carbon::parse($date . ' ' . $slot->time_start);
            $slotEnd = Carbon::parse($date . ' ' . $slot->time_end);
        } else {
            $slotStart = $dateCarbon->copy()->startOfDay();
            $slotEnd = $dateCarbon->copy()->endOfDay();
        }

        $permits = Perizinan::query()
            ->whereIn('siswa_id', $ids)
            ->whereIn('status', [Perizinan::STATUS_DISETUJUI, Perizinan::STATUS_TERLAMBAT])
            ->where('tgl_mulai', '<=', $dateCarbon->copy()->endOfDay())
            ->where(function ($query) use ($dateCarbon) {
                $query->whereNull('tgl_kembali_aktual')
                    ->orWhere('tgl_kembali_aktual', '>=', $dateCarbon->copy()->startOfDay());
            })
            ->where('tgl_sampai', '>=', $dateCarbon->copy()->startOfDay())
            ->get();

        $map = collect();

        foreach ($permits as $permit) {
            $siswaId = (int) $permit->siswa_id;

            $permitMulai = $permit->tgl_mulai;
            $permitSampai = $permit->tgl_kembali_aktual ?? $permit->tgl_sampai;

            // Check overlap with slot timeframe
            $isOverlapped = ($permitMulai <= $slotEnd) && ($permitSampai >= $slotStart);
            $isExpiredBeforeSlot = ($slotStart > $permit->tgl_sampai) && (! $permit->tgl_kembali_aktual);

            $suggestedStatus = self::resolveAttendanceStatus($permit);

            $payload = [
                'id' => $permit->id,
                'jenis_perizinan' => $permit->jenis_perizinan,
                'jenis_label' => $permit->jenis_label,
                'alasan' => $permit->alasan,
                'penanggung_jawab' => $permit->penanggung_jawab,
                'status' => $permit->status,
                'suggested_status' => $suggestedStatus,
                'suggested_status_label' => AttendanceStatus::label($suggestedStatus),
                'is_active' => $isOverlapped,
                'is_expired' => $isExpiredBeforeSlot,
                'tgl_mulai' => $permit->tgl_mulai?->format('Y-m-d H:i'),
                'tgl_sampai' => $permit->tgl_sampai?->format('Y-m-d H:i'),
                'tgl_kembali_aktual' => $permit->tgl_kembali_aktual?->format('Y-m-d H:i'),
                'time_sampai_label' => $permit->tgl_sampai?->format('H:i'),
                'date_range_label' => self::formatDateRange($permit),
            ];

            // Prioritize active overlapping permit per student
            if (! $map->has($siswaId) || $isOverlapped) {
                $map->put($siswaId, $payload);
            }
        }

        return $map;
    }

    /**
     * Determine attendance status (sakit, cuti, or izin) based on permit properties.
     */
    public static function resolveAttendanceStatus(Perizinan $permit): string
    {
        $text = mb_strtolower(trim($permit->alasan . ' ' . ($permit->catatan ?? '') . ' ' . $permit->jenis_perizinan));

        if (preg_match('/\b(sakit|berobat|klinik|puskesmas|rs|rumah sakit|dokter|periksa|opname)\b/i', $text)) {
            return AttendanceStatus::SAKIT;
        }

        if (preg_match('/\b(cuti)\b/i', $text)) {
            return AttendanceStatus::CUTI;
        }

        return AttendanceStatus::IZIN;
    }

    private static function formatDateRange(Perizinan $permit): string
    {
        if (! $permit->tgl_mulai || ! $permit->tgl_sampai) {
            return '-';
        }

        if ($permit->tgl_mulai->isSameDay($permit->tgl_sampai)) {
            return $permit->tgl_mulai->format('d M Y (H:i') . ' - ' . $permit->tgl_sampai->format('H:i)');
        }

        return $permit->tgl_mulai->format('d M') . ' - ' . $permit->tgl_sampai->format('d M Y');
    }
}
