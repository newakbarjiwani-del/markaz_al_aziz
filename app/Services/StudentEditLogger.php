<?php

namespace App\Services;

use App\Models\LogSiswaEdit;
use App\Models\Siswa;
use Illuminate\Http\Request;

class StudentEditLogger
{
    /**
     * @param  array<string, mixed|null>  $before
     * @param  array<string, mixed|null>  $after
     */
    public function logChanges(Request $request, Siswa $siswa, array $before, array $after): void
    {
        foreach (array_keys($after) as $field) {
            $oldValue = $this->normalizeValue($before[$field] ?? null);
            $newValue = $this->normalizeValue($after[$field] ?? null);

            if ($oldValue === $newValue) {
                continue;
            }

            LogSiswaEdit::create([
                'siswa_id' => $siswa->id,
                'user_id' => $request->user()?->id,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }

    public function logField(Request $request, Siswa $siswa, string $field, mixed $oldValue, mixed $newValue): void
    {
        $this->logChanges($request, $siswa, [$field => $oldValue], [$field => $newValue]);
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            $float = (float) $value;

            if (floor($float) == $float) {
                return (string) (int) $float;
            }

            return rtrim(rtrim(number_format($float, 2, '.', ''), '0'), '.');
        }

        return (string) $value;
    }
}
