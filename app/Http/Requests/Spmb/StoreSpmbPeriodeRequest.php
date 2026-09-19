<?php

namespace App\Http\Requests\Spmb;

use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpmbPeriodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('spmb.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sekolah_id' => ['nullable', SoftDeleteRules::exists('sekolah')],
            'tahun_akademik_id' => ['nullable', SoftDeleteRules::exists('tahun_akademik')],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:opens_at'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
