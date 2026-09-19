<?php

namespace App\Support;

class ImportPreviewStatus
{
    public const CANT_STORE = 0;

    public const WILL_CREATE = 1;

    public const WILL_UPDATE = 2;

    public static function label(int $status): string
    {
        return match ($status) {
            self::WILL_CREATE => 'Akan ditambahkan',
            self::WILL_UPDATE => 'Akan diperbarui',
            default => 'Tidak dapat disimpan',
        };
    }
}
