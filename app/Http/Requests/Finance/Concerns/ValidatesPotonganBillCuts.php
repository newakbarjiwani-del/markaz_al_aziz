<?php

namespace App\Http\Requests\Finance\Concerns;

use App\Support\PotonganTipe;
use App\Support\SoftDeleteRules;

trait ValidatesPotonganBillCuts
{
    protected function prepareForValidation(): void
    {
        $billCuts = collect($this->input('bill_cuts', []))->map(function ($row) {
            if (! is_array($row)) {
                return $row;
            }

            if (array_key_exists('nilai', $row)) {
                $row['nilai'] = $this->unformatInteger($row['nilai']);
            }

            return $row;
        })->all();

        $this->merge([
            'nilai' => $this->unformatInteger($this->input('nilai')),
            'bill_cuts' => $billCuts,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function billCutRules(): array
    {
        return [
            'tipe' => PotonganTipe::rules(),
            'nilai' => ['required', 'integer', 'min:1'],
            'max_pemakaian' => ['required', 'integer', 'min:1', 'max:9999'],
            'bill_cuts' => ['required', 'array', 'min:1'],
            'bill_cuts.*.jenis_tagihan_id' => ['required', SoftDeleteRules::exists('jenis_tagihan')],
            'bill_cuts.*.enabled' => ['nullable', 'boolean'],
            'bill_cuts.*.use_default' => ['nullable', 'boolean'],
            'bill_cuts.*.tipe' => ['nullable', 'string', 'in:'.PotonganTipe::PERCENT.','.PotonganTipe::FIXED],
            'bill_cuts.*.nilai' => ['nullable', 'integer', 'min:1'],
            'bill_cuts.*.use_default_max' => ['nullable', 'boolean'],
            'bill_cuts.*.max_pemakaian' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ];
    }

    protected function validateBillCuts($validator): void
    {
        $validator->after(function ($validator) {
            if (PotonganTipe::normalize($this->input('tipe')) === PotonganTipe::PERCENT
                && (int) $this->input('nilai') > 100) {
                $validator->errors()->add('nilai', 'Nilai default persen maksimal 100.');
            }

            $enabled = collect($this->input('bill_cuts', []))
                ->filter(fn ($row) => $this->isOn(data_get($row, 'enabled')));

            if ($enabled->isEmpty()) {
                $validator->errors()->add('bill_cuts', 'Pilih minimal satu jenis tagihan.');

                return;
            }

            foreach ($this->input('bill_cuts', []) as $index => $row) {
                if (! $this->isOn(data_get($row, 'enabled'))) {
                    continue;
                }

                if (! $this->isOn(data_get($row, 'use_default'))) {
                    $tipe = PotonganTipe::normalize((string) data_get($row, 'tipe', ''));
                    $nilai = (int) data_get($row, 'nilai', 0);

                    if ($tipe === '' || $nilai <= 0) {
                        $validator->errors()->add(
                            "bill_cuts.{$index}.nilai",
                            'Isi nilai khusus atau centang Pakai default untuk jenis tagihan ini.'
                        );
                    } elseif ($tipe === PotonganTipe::PERCENT && $nilai > 100) {
                        $validator->errors()->add("bill_cuts.{$index}.nilai", 'Nilai persen maksimal 100.');
                    }
                }

                if (! $this->isOn(data_get($row, 'use_default_max'))) {
                    $max = (int) data_get($row, 'max_pemakaian', 0);
                    if ($max <= 0) {
                        $validator->errors()->add(
                            "bill_cuts.{$index}.max_pemakaian",
                            'Isi kuota khusus atau centang Pakai default kuota untuk jenis tagihan ini.'
                        );
                    }
                }
            }
        });
    }

    /**
     * @return list<array{jenis_tagihan_id: int, use_default: bool, tipe: string|null, nilai: int|null, use_default_max: bool, max_pemakaian: int|null}>
     */
    public function enabledBillCuts(): array
    {
        return collect($this->input('bill_cuts', []))
            ->filter(fn ($row) => $this->isOn(data_get($row, 'enabled')))
            ->map(function ($row) {
                $useDefault = $this->isOn(data_get($row, 'use_default'));
                $useDefaultMax = $this->isOn(data_get($row, 'use_default_max'));

                return [
                    'jenis_tagihan_id' => (int) data_get($row, 'jenis_tagihan_id'),
                    'use_default' => $useDefault,
                    'tipe' => $useDefault ? null : PotonganTipe::normalize((string) data_get($row, 'tipe')),
                    'nilai' => $useDefault ? null : (int) data_get($row, 'nilai'),
                    'use_default_max' => $useDefaultMax,
                    'max_pemakaian' => $useDefaultMax ? null : (int) data_get($row, 'max_pemakaian'),
                ];
            })
            ->values()
            ->all();
    }

    private function unformatInteger(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value;
        }

        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? $value : $digits;
    }

    private function isOn(mixed $value): bool
    {
        return $value === true
            || $value === 1
            || $value === '1'
            || $value === 'true'
            || $value === 'on'
            || $value === 'yes';
    }
}
