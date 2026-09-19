<?php

namespace App\Http\Requests\Finance;

use App\Support\TagihanPeriode;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1000'],
            'periode' => TagihanPeriode::rules(false),
            'due_date' => ['nullable', 'date'],
            'urutan' => ['nullable', 'integer', 'min:0'],
            'enable_cicilan' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal tagihan wajib diisi.',
            'amount.numeric' => 'Nominal tagihan harus berupa angka.',
            'amount.min' => 'Nominal tagihan minimal :min.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => str_replace('.', '', $this->input('amount')),
            ]);
        }

        if ($this->has('enable_cicilan')) {
            $value = $this->input('enable_cicilan');
            if (is_array($value)) {
                $value = end($value);
            }

            $this->merge([
                'enable_cicilan' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\Tagihan|null $tagihan */
            $tagihan = $this->route('tagihan');

            if ($tagihan?->is_cicilan && $this->has('amount')) {
                $paid = (int) round((float) $tagihan->paid);
                $amount = (int) $this->input('amount');

                if ($amount < $paid) {
                    $validator->errors()->add(
                        'amount',
                        'Nominal tagihan tidak boleh kurang dari terbayar (Rp '.number_format($paid, 0, ',', '.').').'
                    );
                }
            }
        });
    }
}
