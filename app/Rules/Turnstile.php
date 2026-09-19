<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! config('turnstile.enabled')) {
            return;
        }

        if (empty($value)) {
            $fail('Verifikasi Turnstile wajib diisi.');

            return;
        }

        $secret = config('turnstile.secret_key');

        if (empty($secret)) {
            $fail('Turnstile tidak dikonfigurasi dengan benar.');

            return;
        }

        $response = Http::asForm()->post(config('turnstile.verify_url'), [
            'secret' => $secret,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            $fail('Verifikasi Turnstile gagal. Silakan coba lagi.');
        }
    }
}
