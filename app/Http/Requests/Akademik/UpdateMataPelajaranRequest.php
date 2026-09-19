<?php

namespace App\Http\Requests\Akademik;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMataPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'kelompok' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
