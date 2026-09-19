<?php

namespace App\Http\Requests\MasterData;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSekolahRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('master_data.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', SoftDeleteRules::unique('sekolah', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
