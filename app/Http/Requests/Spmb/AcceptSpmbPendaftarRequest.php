<?php

namespace App\Http\Requests\Spmb;

use App\Support\SoftDeleteRules;
use App\Support\VirtualAccountNumber;
use Illuminate\Foundation\Http\FormRequest;

class AcceptSpmbPendaftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spmb.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'nis' => VirtualAccountNumber::nisRules(),
            'kelas_id' => ['nullable', SoftDeleteRules::exists('kelas')],
        ];
    }

    public function messages(): array
    {
        return VirtualAccountNumber::nisMessages();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nis')) {
            $this->merge([
                'nis' => VirtualAccountNumber::normalizeNis($this->input('nis')),
            ]);
        }
    }
}
