<?php

namespace App\Http\Requests\Akademik;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreKurikulumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('akademik.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'tahun_akademik_id' => ['required', SoftDeleteRules::exists('tahun_akademik')],
            'name' => ['required', 'string', 'max:255'],
            'jenjang' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
