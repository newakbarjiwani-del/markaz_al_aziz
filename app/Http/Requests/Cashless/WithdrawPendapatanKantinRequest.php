<?php

namespace App\Http\Requests\Cashless;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawPendapatanKantinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('cashless.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.min' => 'Nominal penarikan minimal Rp 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $raw = preg_replace('/[^\d]/', '', (string) $this->input('amount'));
            $this->merge(['amount' => $raw !== '' && $raw !== null ? (int) $raw : null]);
        }
    }
}
