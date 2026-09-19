<?php

namespace App\Http\Requests\Tahfidz;

use App\Support\TahfidzProgressStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTahfidzProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tahfidz.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(TahfidzProgressStatus::values())],
            'note' => ['nullable', 'string', 'max:2000'],
            'verified' => ['nullable', 'boolean'],
        ];
    }
}
