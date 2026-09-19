<?php

namespace App\Support;

class WhatsAppLink
{
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    public static function url(?string $phone, string $message): ?string
    {
        $normalized = self::normalizePhone($phone);

        if ($normalized === null) {
            return null;
        }

        return 'https://wa.me/'.$normalized.'?text='.rawurlencode($message);
    }
}
