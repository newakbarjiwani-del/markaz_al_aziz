<?php

namespace App\Support;

final class TahfidzRekapStatus
{
    public const DRAFT = 'draft';

    public const SIAP = 'siap';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::DRAFT => 'Draft',
            self::SIAP => 'Siap dikirim',
        ];
    }

    public static function label(?string $value): string
    {
        return self::labels()[$value] ?? (string) $value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::labels());
    }
}
