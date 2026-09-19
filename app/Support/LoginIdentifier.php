<?php

namespace App\Support;

class LoginIdentifier
{
    public static function field(string $login): string
    {
        return filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    /** @return array<string, string> */
    public static function credentials(string $login, string $password): array
    {
        return [
            self::field($login) => $login,
            'password' => $password,
        ];
    }
}
