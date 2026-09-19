<?php

namespace App\Support;

use App\Models\PeminjamanBuku;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class LibrarySettings
{
    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $defaults = self::defaults();
        $stored = self::loadStored();

        return array_merge($defaults, $stored);
    }

    public static function loanDays(): int
    {
        return max(1, (int) (self::all()['loan_days'] ?? 7));
    }

    public static function finePerDay(): int
    {
        return max(0, (int) (self::all()['fine_per_day'] ?? 2000));
    }

    public static function maxBooks(): int
    {
        return max(1, (int) (self::all()['max_books'] ?? 3));
    }

    public static function extensionDays(): int
    {
        return max(1, (int) (self::all()['extension_days'] ?? 7));
    }

    public static function maxExtensions(): int
    {
        return max(0, (int) (self::all()['max_extensions'] ?? 1));
    }

    public static function fineLostBook(): int
    {
        return self::fineForKondisi(PeminjamanBuku::KONDISI_HILANG);
    }

    public static function fineDamagedBook(): int
    {
        return self::fineForKondisi(PeminjamanBuku::KONDISI_RUSAK_BERAT);
    }

    /**
     * @return array<int, array{kondisi: string, label: string, amount: int}>
     */
    public static function kondisiFines(): array
    {
        $stored = self::all()['kondisi_fines'] ?? null;

        if (is_array($stored) && $stored !== []) {
            return collect($stored)
                ->map(fn (array $item) => self::normalizeKondisiFine($item))
                ->filter(fn (array $item) => $item['kondisi'] !== '' && $item['kondisi'] !== PeminjamanBuku::KONDISI_BAIK)
                ->values()
                ->all();
        }

        return self::legacyKondisiFines();
    }

    public static function fineForKondisi(?string $kondisi): int
    {
        if (! filled($kondisi) || $kondisi === PeminjamanBuku::KONDISI_BAIK) {
            return 0;
        }

        foreach (self::kondisiFines() as $fine) {
            if ($fine['kondisi'] === $kondisi) {
                return $fine['amount'];
            }
        }

        return 0;
    }

    public static function fineLabelForKondisi(?string $kondisi): ?string
    {
        if (! filled($kondisi)) {
            return null;
        }

        foreach (self::kondisiFines() as $fine) {
            if ($fine['kondisi'] === $kondisi) {
                return $fine['label'];
            }
        }

        return PeminjamanBuku::kondisiOptions()[$kondisi] ?? null;
    }

    /**
     * @return array<int, array{value: string, label: string, amount: int}>
     */
    public static function kondisiOptionsForReturn(): array
    {
        $fines = collect(self::kondisiFines())->keyBy('kondisi');

        return collect(PeminjamanBuku::kondisiOptions())
            ->map(function (string $label, string $value) use ($fines) {
                $fine = $fines->get($value);
                $amount = $fine['amount'] ?? 0;
                $display = $amount > 0
                    ? $label.' (+ Rp '.number_format($amount, 0, ',', '.').')'
                    : $label;

                return [
                    'value' => $value,
                    'label' => $display,
                    'amount' => $amount,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function fineSettingsFormData(): array
    {
        return [
            'fine_per_day' => self::finePerDay(),
            'kondisi_fines' => self::kondisiFines(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function validateFineSettings(array $input): array
    {
        $kondisiKeys = array_keys(PeminjamanBuku::kondisiOptions());

        return validator($input, [
            'fine_per_day' => ['required', 'integer', 'min:0'],
            'kondisi_fines' => ['nullable', 'array'],
            'kondisi_fines.*.kondisi' => ['required', 'string', Rule::in($kondisiKeys)],
            'kondisi_fines.*.label' => ['required', 'string', 'max:100'],
            'kondisi_fines.*.amount' => ['required', 'integer', 'min:1'],
        ], [
            'kondisi_fines.*.amount.min' => 'Nominal denda kondisi harus lebih dari 0.',
        ])->after(function ($validator) use ($input) {
            $kondisiList = collect($input['kondisi_fines'] ?? [])->pluck('kondisi')->filter()->values();
            if ($kondisiList->count() !== $kondisiList->unique()->count()) {
                $validator->errors()->add('kondisi_fines', 'Setiap kondisi buku hanya boleh punya satu aturan denda.');
            }
        })->validate();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function saveFineSettings(array $validated): void
    {
        $path = 'settings/library.json';
        $existing = self::loadStored();

        $kondisiFines = collect($validated['kondisi_fines'] ?? [])
            ->map(fn (array $item) => self::normalizeKondisiFine($item))
            ->filter(fn (array $item) => $item['kondisi'] !== '' && $item['kondisi'] !== PeminjamanBuku::KONDISI_BAIK)
            ->reverse()
            ->unique('kondisi')
            ->reverse()
            ->values()
            ->all();

        Storage::disk('local')->put($path, json_encode(array_merge($existing, [
            'fine_per_day' => (int) $validated['fine_per_day'],
            'kondisi_fines' => $kondisiFines,
            'fine_damaged_book' => collect($kondisiFines)->firstWhere('kondisi', PeminjamanBuku::KONDISI_RUSAK_BERAT)['amount'] ?? ($existing['fine_damaged_book'] ?? 25000),
            'fine_lost_book' => collect($kondisiFines)->firstWhere('kondisi', PeminjamanBuku::KONDISI_HILANG)['amount'] ?? ($existing['fine_lost_book'] ?? 50000),
        ]), JSON_PRETTY_PRINT));
    }

    /**
     * @return array<int, array{kondisi: string, label: string, amount: int}>
     */
    private static function legacyKondisiFines(): array
    {
        $all = self::all();

        return [
            [
                'kondisi' => PeminjamanBuku::KONDISI_RUSAK_BERAT,
                'label' => 'Rusak berat',
                'amount' => max(0, (int) ($all['fine_damaged_book'] ?? 25000)),
            ],
            [
                'kondisi' => PeminjamanBuku::KONDISI_HILANG,
                'label' => 'Hilang',
                'amount' => max(0, (int) ($all['fine_lost_book'] ?? 50000)),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{kondisi: string, label: string, amount: int}
     */
    private static function normalizeKondisiFine(array $item): array
    {
        $kondisi = (string) ($item['kondisi'] ?? '');

        return [
            'kondisi' => $kondisi,
            'label' => filled($item['label'] ?? null)
                ? (string) $item['label']
                : (PeminjamanBuku::kondisiOptions()[$kondisi] ?? ucfirst(str_replace('_', ' ', $kondisi))),
            'amount' => max(0, (int) ($item['amount'] ?? 0)),
        ];
    }

    /**
     * @return array<string, int>
     */
    private static function defaults(): array
    {
        $fields = config('module-settings.library.fields', []);

        return collect($fields)
            ->mapWithKeys(fn (array $field) => [$field['key'] => $field['default'] ?? null])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function loadStored(): array
    {
        $path = 'settings/library.json';
        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get($path), true) ?? [];
    }
}
