<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ImportSpreadsheetPreviewCache
{
    public const TTL_SECONDS = 7200;

    public static function key(int $userId, string $type): string
    {
        return 'spreadsheet_import_preview:'.$userId.':'.$type;
    }

    /** @param array<string, mixed> $payload */
    public static function put(int $userId, string $type, array $payload): void
    {
        Cache::put(self::key($userId, $type), $payload, self::TTL_SECONDS);
    }

    /** @return array<string, mixed>|null */
    public static function get(int $userId, string $type): ?array
    {
        $payload = Cache::get(self::key($userId, $type));

        return is_array($payload) ? $payload : null;
    }

    public static function forget(int $userId, string $type): bool
    {
        return Cache::forget(self::key($userId, $type));
    }

    public static function has(int $userId, string $type): bool
    {
        return Cache::has(self::key($userId, $type));
    }
}
