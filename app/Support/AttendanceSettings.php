<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class AttendanceSettings
{
    private const string PATH = 'settings/attendance.json';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'toleransi_menit' => 15,
            'enable_qr' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $stored = self::loadStored();

        return array_merge(self::defaults(), $stored);
    }

    public static function jamMasuk(): string
    {
        return (string) self::all()['jam_masuk'];
    }

    public static function jamPulang(): string
    {
        return (string) self::all()['jam_pulang'];
    }

    public static function toleransiMenit(): int
    {
        return max(0, (int) self::all()['toleransi_menit']);
    }

    /**
     * @param  array{jam_masuk: string, jam_pulang: string, toleransi_menit: int}  $data
     */
    public static function save(array $data): void
    {
        $current = self::all();

        $payload = [
            'jam_masuk' => $data['jam_masuk'],
            'jam_pulang' => $data['jam_pulang'],
            'toleransi_menit' => $data['toleransi_menit'],
            'enable_qr' => $current['enable_qr'] ?? true,
        ];

        Storage::disk('local')->put(self::PATH, json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadStored(): array
    {
        if (! Storage::disk('local')->exists(self::PATH)) {
            return [];
        }

        $stored = json_decode(Storage::disk('local')->get(self::PATH), true) ?? [];

        return self::normalizeLegacyKeys($stored);
    }

    /**
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    private static function normalizeLegacyKeys(array $stored): array
    {
        if (! isset($stored['jam_masuk']) && isset($stored['checkin_start'])) {
            $stored['jam_masuk'] = $stored['checkin_start'];
        }

        if (! isset($stored['toleransi_menit']) && isset($stored['checkin_late'], $stored['jam_masuk'])) {
            $stored['toleransi_menit'] = self::minutesBetween($stored['jam_masuk'], $stored['checkin_late']);
        }

        return $stored;
    }

    private static function minutesBetween(string $start, string $end): int
    {
        $startMinutes = self::timeToMinutes($start);
        $endMinutes = self::timeToMinutes($end);

        if ($endMinutes < $startMinutes) {
            return 0;
        }

        return $endMinutes - $startMinutes;
    }

    private static function timeToMinutes(string $time): int
    {
        [$hour, $minute] = array_pad(explode(':', $time), 2, 0);

        return ((int) $hour * 60) + (int) $minute;
    }
}
