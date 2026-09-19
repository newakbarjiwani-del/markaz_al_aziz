<?php

namespace App\Support;

class ImportStoreMethod
{
    public const CREATE_ONLY = 'create_only';

    public const UPDATE_ONLY = 'update_only';

    public const CREATE_AND_UPDATE = 'create_and_update';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CREATE_ONLY,
            self::UPDATE_ONLY,
            self::CREATE_AND_UPDATE,
        ];
    }

    public static function label(string $method): string
    {
        return match ($method) {
            self::CREATE_ONLY => 'Hanya tambah data baru',
            self::UPDATE_ONLY => 'Hanya perbarui data existing',
            default => 'Tambah dan perbarui data',
        };
    }

    public static function allows(int $status, string $method): bool
    {
        if ($status === ImportPreviewStatus::CANT_STORE) {
            return false;
        }

        return match ($method) {
            self::CREATE_ONLY => $status === ImportPreviewStatus::WILL_CREATE,
            self::UPDATE_ONLY => $status === ImportPreviewStatus::WILL_UPDATE,
            self::CREATE_AND_UPDATE => in_array($status, [
                ImportPreviewStatus::WILL_CREATE,
                ImportPreviewStatus::WILL_UPDATE,
            ], true),
            default => false,
        };
    }

    public static function skipReason(int $status, string $method): string
    {
        if ($status === ImportPreviewStatus::CANT_STORE) {
            return 'Baris tidak valid.';
        }

        if ($method === self::CREATE_ONLY && $status === ImportPreviewStatus::WILL_UPDATE) {
            return 'Dilewati karena NIS/NIP sudah ada (mode hanya tambah baru).';
        }

        if ($method === self::UPDATE_ONLY && $status === ImportPreviewStatus::WILL_CREATE) {
            return 'Dilewati karena NIS/NIP belum ada (mode hanya perbarui).';
        }

        return 'Dilewati sesuai mode import.';
    }
}
