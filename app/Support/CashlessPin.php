<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;

/**
 * 4-digit cashless PIN for over-limit withdraw (hashed on siswa.cashless_pin).
 */
final class CashlessPin
{
    /**
     * @return list<string|\Illuminate\Validation\Rules\Regex>
     */
    public static function rules(bool $required = true): array
    {
        $rules = ['digits:4'];

        if ($required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    public static function hash(string $pin): string
    {
        return Hash::make($pin);
    }

    public static function verify(?string $plain, ?string $hash): bool
    {
        if ($plain === null || $plain === '' || $hash === null || $hash === '') {
            return false;
        }

        return Hash::check($plain, $hash);
    }

    public static function isSet(?string $hash): bool
    {
        return $hash !== null && $hash !== '';
    }
}
