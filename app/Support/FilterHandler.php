<?php

namespace App\Support;

class FilterHandler
{
    /**
     * Whitelist and normalize nested filter[...] request input.
     * Values "all" / empty are stripped. Keys remap via $allowedFilters.
     *
     * @param  array<string, mixed>|null  $filter
     * @param  array<int|string, string>  $allowedFilters  UI key => column (or list of keys)
     * @return array<string, mixed>
     */
    public static function resolveFilters(?array $filter, array $allowedFilters): array
    {
        $normalized = collect($allowedFilters)
            ->mapWithKeys(function ($value, $key) {
                return is_int($key)
                    ? [$value => $value]
                    : [$key => $value];
            })
            ->toArray();

        return collect($filter ?? [])
            ->only(array_keys($normalized))
            ->map(function ($value) {
                if (is_array($value)) {
                    return collect($value)
                        ->filter(fn ($v) => ! in_array(strtolower((string) $v), ['all', ''], true))
                        ->values()
                        ->all();
                }

                if (in_array(strtolower((string) $value), ['all', ''], true)) {
                    return null;
                }

                return $value;
            })
            ->reject(function ($value) {
                return $value === 'all'
                    || $value === null
                    || $value === ''
                    || (is_array($value) && $value === []);
            })
            ->mapWithKeys(fn ($value, $key) => [
                $normalized[$key] => $value,
            ])
            ->sortKeys()
            ->toArray();
    }

    /**
     * Force-scope by operator sekolah id when set (users.sekolah_id).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function applySekolahScope(array $filters, int|string|null $sekolahId, string $column = 'sekolah_id'): array
    {
        if ($sekolahId === null || $sekolahId === '') {
            return $filters;
        }

        return array_merge($filters, [$column => $sekolahId]);
    }
}
