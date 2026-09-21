<?php

namespace App\Services\Finance\Qris;

use RuntimeException;

class QrisJwtCodec
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function encode(array $payload): string
    {
        $key = $this->key();
        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $key, true));

        return $header.'.'.$body.'.'.$signature;
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        $key = $this->key();
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWT structure.');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;
        $expected = hash_hmac('sha256', $headerB64.'.'.$payloadB64, $key, true);
        $actual = $this->base64UrlDecode($signatureB64);

        if (! hash_equals($expected, $actual)) {
            throw new RuntimeException('Invalid JWT signature.');
        }

        $payload = json_decode($this->base64UrlDecode($payloadB64), true);

        if (! is_array($payload)) {
            throw new RuntimeException('Invalid JWT payload.');
        }

        return $payload;
    }

    private function key(): string
    {
        $key = config('finance.qris.jwt_key');

        if (! filled($key)) {
            throw new RuntimeException('QRIS JWT key is not configured.');
        }

        return (string) $key;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid JWT encoding.');
        }

        return $decoded;
    }
}
