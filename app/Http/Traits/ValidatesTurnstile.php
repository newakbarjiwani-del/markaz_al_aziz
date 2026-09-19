<?php

namespace App\Http\Traits;

use App\Rules\Turnstile;
use Illuminate\Http\Request;

trait ValidatesTurnstile
{
    protected function turnstileRules(): array
    {
        if (! config('turnstile.enabled')) {
            return [];
        }

        return [
            'cf-turnstile-response' => ['required', new Turnstile],
        ];
    }

    protected function turnstileMessages(): array
    {
        return [
            'cf-turnstile-response.required' => 'Selesaikan verifikasi Turnstile terlebih dahulu.',
        ];
    }

    protected function validateTurnstile(Request $request): void
    {
        $rules = $this->turnstileRules();

        if ($rules !== []) {
            $request->validate($rules, $this->turnstileMessages());
        }
    }
}
