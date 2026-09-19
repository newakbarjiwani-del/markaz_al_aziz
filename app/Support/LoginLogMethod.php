<?php

namespace App\Support;

class LoginLogMethod
{
    public const CREDENTIALS_WEB = 'credentials_web';

    public const CREDENTIALS_API = 'credentials_api';

    public const PORTAL_TOKEN = 'portal_token';

    public static function label(string $method): string
    {
        return match ($method) {
            self::CREDENTIALS_WEB => 'Username / Email (Web)',
            self::CREDENTIALS_API => 'Username / Email (API)',
            self::PORTAL_TOKEN => 'Token Portal',
            default => $method,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::CREDENTIALS_WEB => self::label(self::CREDENTIALS_WEB),
            self::CREDENTIALS_API => self::label(self::CREDENTIALS_API),
            self::PORTAL_TOKEN => self::label(self::PORTAL_TOKEN),
        ];
    }
}
