<?php

namespace App\Support;

class VirtualAccountNumber
{
    public const NIS_MAX_LENGTH = 30;

    public const PREFIX_LENGTH = 6;

    public const VA_NIS_LENGTH = 10;

    public const TOTAL_LENGTH = self::PREFIX_LENGTH + self::VA_NIS_LENGTH;

    public static function prefix(): string
    {
        $digits = preg_replace('/\D/', '', (string) config('school.va_prefix', '000000')) ?? '';

        return str_pad(substr($digits, 0, self::PREFIX_LENGTH), self::PREFIX_LENGTH, '0', STR_PAD_LEFT);
    }

    public static function normalizeNis(?string $nis): ?string
    {
        if ($nis === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', trim($nis));

        return $digits === '' ? null : $digits;
    }

    /** True when NIS is non-empty digits within {@see NIS_MAX_LENGTH}. */
    public static function isValidNis(?string $nis): bool
    {
        $normalized = self::normalizeNis($nis);

        return $normalized !== null
            && strlen($normalized) <= self::NIS_MAX_LENGTH
            && preg_match('/^\d+$/', $normalized) === 1;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public static function requireValidNis(?string $nis, string $emptyMessage = 'NIS wajib diisi dan harus berupa angka.'): string
    {
        $normalized = self::normalizeNis($nis);

        if ($normalized === null) {
            throw new \InvalidArgumentException($emptyMessage);
        }

        if (strlen($normalized) > self::NIS_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                'NIS maksimal '.self::NIS_MAX_LENGTH.' digit (ditemukan '.strlen($normalized).' digit).'
            );
        }

        return $normalized;
    }

    public static function fromNis(?string $nis): ?string
    {
        $suffix = self::nisSuffix($nis);

        if ($suffix === null) {
            return null;
        }

        return self::prefix().$suffix;
    }

    /**
     * Last {@see VA_NIS_LENGTH} digits of NIS, zero-padded on the left.
     * This suffix (plus VA prefix) forms the virtual account number.
     */
    public static function nisSuffix(?string $nis): ?string
    {
        $normalized = self::normalizeNis($nis);

        if ($normalized === null) {
            return null;
        }

        return str_pad(substr($normalized, -self::VA_NIS_LENGTH), self::VA_NIS_LENGTH, '0', STR_PAD_LEFT);
    }

    public static function nisFromVano(?string $vano): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $vano) ?? '';

        if (strlen($digits) < self::PREFIX_LENGTH + 1) {
            return null;
        }

        $suffix = substr($digits, self::PREFIX_LENGTH, self::VA_NIS_LENGTH);
        $nis = ltrim($suffix, '0');

        return $nis === '' ? null : $nis;
    }

    /** @return list<mixed> */
    public static function nisLookupRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^\d{1,'.self::NIS_MAX_LENGTH.'}$/',
        ];
    }

    /** @return list<mixed> */
    public static function nisRules(?int $ignoreSiswaId = null): array
    {
        $ignoreId = ($ignoreSiswaId !== null && (int) $ignoreSiswaId > 0)
            ? (int) $ignoreSiswaId
            : null;

        return [
            ...self::nisLookupRules(),
            function (string $attribute, mixed $value, \Closure $fail) use ($ignoreId): void {
                $query = \App\Models\Siswa::withoutGlobalScopes()
                    ->whereNull('deleted_at')
                    ->where('nis', (string) $value);

                // Always exclude the student being updated.
                if ($ignoreId !== null) {
                    $query->where('id', '<>', $ignoreId);
                }

                $other = $query->orderBy('id')->first(['id', 'name']);

                if ($other === null) {
                    return;
                }

                $fail('NIS sudah digunakan oleh '.$other->name.' (ID '.$other->id.').');
            },
        ];
    }

    /** @return array<string, string> */
    public static function nisMessages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nis.regex' => 'NIS hanya boleh berisi angka (maksimal '.self::NIS_MAX_LENGTH.' digit).',
            'nis.unique' => 'NIS sudah digunakan.',
        ];
    }
}
