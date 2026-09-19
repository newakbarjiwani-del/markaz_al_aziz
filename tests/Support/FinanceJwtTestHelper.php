<?php

namespace Tests\Support;

final class FinanceJwtTestHelper
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function encode(array $payload, ?string $key = null): string
    {
        $key ??= (string) config('finance.jwt_key', 'finance-test-key');

        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $payload = self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = self::base64UrlEncode(hash_hmac('sha256', $header.'.'.$payload, $key, true));

        return $header.'.'.$payload.'.'.$signature;
    }

    /**
     * @return array<string, mixed>
     */
    public static function decode(string $token, ?string $key = null): array
    {
        $key ??= (string) config('finance.jwt_key', 'finance-test-key');

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \RuntimeException('Invalid JWT structure.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expected = hash_hmac('sha256', $headerB64.'.'.$payloadB64, $key, true);
        $actual = self::base64UrlDecode($signatureB64);

        if (! hash_equals($expected, $actual)) {
            throw new \RuntimeException('Invalid JWT signature.');
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);

        if (! is_array($payload)) {
            throw new \RuntimeException('Invalid JWT payload.');
        }

        return $payload;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new \RuntimeException('Invalid JWT encoding.');
        }

        return $decoded;
    }
}
