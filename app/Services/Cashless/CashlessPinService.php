<?php

namespace App\Services\Cashless;

use App\Models\Siswa;
use App\Support\CashlessPin;
use Illuminate\Validation\ValidationException;

class CashlessPinService
{
    /**
     * Set or change a student's cashless PIN.
     *
     * @throws ValidationException
     */
    public function change(Siswa $siswa, string $pin, ?string $currentPin = null): void
    {
        $pin = trim($pin);

        if (! preg_match('/^\d{4}$/', $pin)) {
            throw ValidationException::withMessages([
                'pin' => 'PIN harus 4 digit angka.',
            ]);
        }

        if (CashlessPin::isSet($siswa->cashless_pin)) {
            if ($currentPin === null || $currentPin === '') {
                throw ValidationException::withMessages([
                    'current_pin' => 'PIN saat ini wajib diisi.',
                ]);
            }

            if (! CashlessPin::verify($currentPin, $siswa->cashless_pin)) {
                throw ValidationException::withMessages([
                    'current_pin' => 'PIN saat ini salah.',
                ]);
            }
        }

        $siswa->forceFill([
            'cashless_pin' => CashlessPin::hash($pin),
        ])->save();
    }

    public function reset(Siswa $siswa): void
    {
        $siswa->forceFill([
            'cashless_pin' => null,
        ])->save();
    }

    public function isSet(Siswa $siswa): bool
    {
        return CashlessPin::isSet($siswa->cashless_pin);
    }
}
